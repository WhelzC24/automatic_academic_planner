<?php
// ============================================================
// BISU Planner - Student API Handler
// backend/student/student_api.php
// ============================================================

require_once __DIR__ . '/../config/helpers.php';
requireAuth('student');

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';
$db = getDB();
$uid = (int)$_SESSION['user_id'];

function getTaskPriorityFromDueAt(string $dueAt): string
{
    $hoursUntilDue = (strtotime($dueAt) - time()) / 3600;
    if ($hoursUntilDue <= 24) {
        return 'Urgent';
    }
    if ($hoursUntilDue <= 72) {
        return 'High';
    }
    if ($hoursUntilDue <= 168) {
        return 'Medium';
    }
    return 'Low';
}

function syncAssignmentTasksForStudent(PDO $db, int $studentId): void
{
    // Create missing assignment-linked tasks for this student.
    $insertMissing = $db->prepare(
        "INSERT INTO tasks (user_id, assignment_id, task_name, description, due_at, priority, status)
         SELECT e.student_id,
                a.assignment_id,
                CONCAT('Submit: ', a.title),
                a.description,
                a.due_at,
                CASE
                    WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 24 THEN 'Urgent'
                    WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 72 THEN 'High'
                    WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 168 THEN 'Medium'
                    ELSE 'Low'
                END,
                CASE WHEN a.due_at < NOW() THEN 'Overdue' ELSE 'Pending' END
         FROM assignments a
         JOIN course_offerings co ON co.offering_id = a.offering_id
         JOIN enrollments e ON e.offering_id = co.offering_id
         LEFT JOIN tasks t ON t.user_id = e.student_id AND t.assignment_id = a.assignment_id
         WHERE e.student_id = :uid AND t.task_id IS NULL"
    );
    $insertMissing->execute([':uid' => $studentId]);

    // Keep linked task metadata in sync with assignment updates.
    $syncLinked = $db->prepare(
        "UPDATE tasks t
         JOIN assignments a ON a.assignment_id = t.assignment_id
         JOIN course_offerings co ON co.offering_id = a.offering_id
         JOIN enrollments e ON e.offering_id = co.offering_id AND e.student_id = t.user_id
         SET t.task_name = CONCAT('Submit: ', a.title),
             t.description = a.description,
             t.due_at = a.due_at,
             t.priority = CASE
                 WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 24 THEN 'Urgent'
                 WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 72 THEN 'High'
                 WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 168 THEN 'Medium'
                 ELSE 'Low'
             END,
             t.status = CASE
                 WHEN t.status = 'Completed' THEN t.status
                 WHEN a.due_at < NOW() THEN 'Overdue'
                 WHEN t.status = 'Overdue' AND a.due_at >= NOW() THEN 'Pending'
                 ELSE t.status
             END
         WHERE t.user_id = :uid AND t.assignment_id IS NOT NULL"
    );
    $syncLinked->execute([':uid' => $studentId]);
}

switch ($action) {

    // ── DASHBOARD DATA ─────────────────────────────────────
    case 'get_dashboard':
        syncAssignmentTasksForStudent($db, $uid);
        autoMarkOverdue();
        generateDeadlineNotifications();

        // Today's tasks
        $stmt = $db->prepare(
            "SELECT t.*, a.title as assignment_title
             FROM tasks t LEFT JOIN assignments a ON a.assignment_id = t.assignment_id
             WHERE t.user_id = :uid AND DATE(t.due_at) = CURDATE()
             ORDER BY FIELD(t.priority,'Urgent','High','Medium','Low'), t.due_at"
        );
        $stmt->execute([':uid' => $uid]);
        $todayTasks = $stmt->fetchAll();

        // Upcoming deadlines (next 7 days)
        $stmt2 = $db->prepare(
            "SELECT a.assignment_id, a.title, a.due_at, c.title as course_title, c.code,
                    co.section, co.term,
                (SELECT submission_id FROM submissions s
                 WHERE s.assignment_id = a.assignment_id AND s.student_id = :uid_sub) as submitted
             FROM assignments a
             JOIN course_offerings co ON co.offering_id = a.offering_id
             JOIN courses c ON c.course_id = co.course_id
             JOIN enrollments e ON e.offering_id = co.offering_id AND e.student_id = :uid_enroll
             WHERE a.due_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
             ORDER BY a.due_at"
        );
        $stmt2->execute([':uid_sub' => $uid, ':uid_enroll' => $uid]);
        $upcoming = $stmt2->fetchAll();

        // Unread notification count
        $stmt3 = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id=:uid AND read_at IS NULL");
        $stmt3->execute([':uid' => $uid]);
        $unread = $stmt3->fetch()['cnt'];

        // Task stats
        $stmt4 = $db->prepare(
            "SELECT status, COUNT(*) as cnt FROM tasks WHERE user_id = :uid GROUP BY status"
        );
        $stmt4->execute([':uid' => $uid]);
        $taskStats = [];
        foreach ($stmt4->fetchAll() as $row) $taskStats[$row['status']] = $row['cnt'];

        jsonResponse(true, 'OK', [
            'today_tasks' => $todayTasks,
            'upcoming'    => $upcoming,
            'unread_notif' => (int)$unread,
            'task_stats'  => $taskStats,
        ]);
        break;

    case 'get_unread_notif_count':
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id=:uid AND read_at IS NULL");
        $stmt->execute([':uid' => $uid]);
        jsonResponse(true, 'OK', ['unread_notif' => (int)$stmt->fetch()['cnt']]);
        break;

    // ── TASKS ─────────────────────────────────────────────
    // Tasks are auto-generated from assignments via cron jobs
    case 'get_tasks':
        syncAssignmentTasksForStudent($db, $uid);
        $sql = "SELECT t.*, a.title as assignment_title
                FROM tasks t LEFT JOIN assignments a ON a.assignment_id = t.assignment_id
                WHERE t.user_id = :uid AND t.assignment_id IS NOT NULL
                ORDER BY FIELD(t.priority,'Urgent','High','Medium','Low'), t.due_at";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uid' => $uid]);
        jsonResponse(true, 'OK', ['tasks' => $stmt->fetchAll()]);
        break;

    case 'mark_task_status':
        $taskId = (int)($_POST['task_id'] ?? 0);
        $status = $_POST['status'] ?? 'Completed';
        $stmt = $db->prepare("UPDATE tasks SET status=:s WHERE task_id=:id AND user_id=:uid AND assignment_id IS NOT NULL");
        $stmt->execute([':s' => $status, ':id' => $taskId, ':uid' => $uid]);
        jsonResponse(true, 'Status updated.');
        break;

    // ── SCHEDULES ──────────────────────────────────────────
    case 'get_schedules':
        $start = $_GET['start'] ?? date('Y-m-01');
        $end   = $_GET['end']   ?? date('Y-m-t');
        $stmt  = $db->prepare(
            "SELECT * FROM schedules
             WHERE user_id=:uid AND starts_at <= :e AND ends_at >= :s
             ORDER BY starts_at"
        );
        $stmt->execute([':uid' => $uid, ':s' => $start, ':e' => $end]);
        jsonResponse(true, 'OK', ['schedules' => $stmt->fetchAll()]);
        break;

    case 'get_readonly_schedules':
        $offeringsStmt = $db->prepare(
            "SELECT co.offering_id,
                    co.schedule,
                    co.room,
                    co.section,
                    co.term,
                    c.code as course_code,
                    c.title as course_title,
                    u.first_name,
                    u.last_name
             FROM enrollments e
             JOIN course_offerings co ON co.offering_id = e.offering_id
             JOIN courses c ON c.course_id = co.course_id
             LEFT JOIN teaching_assignments ta ON ta.offering_id = co.offering_id
             LEFT JOIN users u ON u.user_id = ta.instructor_id
             WHERE e.student_id = :uid
             ORDER BY co.term DESC, c.code"
        );
        $offeringsStmt->execute([':uid' => $uid]);

        $eventsStmt = $db->prepare(
            "SELECT schedule_id,
                    title,
                    description,
                    starts_at,
                    ends_at,
                    type,
                    color
             FROM schedules
             WHERE user_id = :uid
               AND type IN ('Exam','Activity','Quiz','Presentation')
             ORDER BY starts_at"
        );
        $eventsStmt->execute([':uid' => $uid]);

        jsonResponse(true, 'OK', [
            'offerings' => $offeringsStmt->fetchAll(),
            'events' => $eventsStmt->fetchAll(),
        ]);
        break;

    case 'add_schedule':
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $startsAtInput = trim($_POST['starts_at'] ?? '');
        $endsAtInput = trim($_POST['ends_at'] ?? '');
        $type = trim($_POST['type'] ?? 'Personal');
        $color = trim($_POST['color'] ?? '#4f46e5');

        if ($title === '' || $startsAtInput === '' || $endsAtInput === '') {
            jsonResponse(false, 'Title, start time, and end time are required.');
        }

        $allowedTypes = ['Class', 'Study', 'Personal', 'Meeting'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'Personal';
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#4f46e5';
        }

        $startsAtTs = strtotime($startsAtInput);
        $endsAtTs = strtotime($endsAtInput);
        if (!$startsAtTs || !$endsAtTs) {
            jsonResponse(false, 'Invalid date/time format.');
        }
        if ($endsAtTs <= $startsAtTs) {
            jsonResponse(false, 'End time must be later than start time.');
        }

        $startsAt = date('Y-m-d H:i:s', $startsAtTs);
        $endsAt = date('Y-m-d H:i:s', $endsAtTs);

        $ins = $db->prepare(
            "INSERT INTO schedules (user_id, title, description, starts_at, ends_at, type, color)
             VALUES (:uid, :title, :description, :starts_at, :ends_at, :type, :color)"
        );
        $ins->execute([
            ':uid' => $uid,
            ':title' => $title,
            ':description' => $desc !== '' ? $desc : null,
            ':starts_at' => $startsAt,
            ':ends_at' => $endsAt,
            ':type' => $type,
            ':color' => $color,
        ]);

        jsonResponse(true, 'Schedule added successfully.');
        break;

    // Class schedules may be generated from course offerings
    // ── ASSIGNMENTS ────────────────────────────────────────
    case 'get_assignments':
        $stmt = $db->prepare(
            "SELECT a.*, c.title as course_title, c.code,
                    co.section, co.term,
                    u.first_name, u.last_name,
                    sub.submission_id, sub.status as sub_status, sub.grade, sub.feedback, sub.submitted_at
             FROM assignments a
             JOIN course_offerings co ON co.offering_id = a.offering_id
             JOIN courses c ON c.course_id = co.course_id
             JOIN enrollments e ON e.offering_id = co.offering_id AND e.student_id = :uid_enroll
             JOIN users u ON u.user_id = a.created_by
             LEFT JOIN submissions sub ON sub.assignment_id = a.assignment_id AND sub.student_id = :uid_sub
             ORDER BY a.due_at"
        );
        $stmt->execute([':uid_enroll' => $uid, ':uid_sub' => $uid]);
        jsonResponse(true, 'OK', ['assignments' => $stmt->fetchAll()]);
        break;

    case 'submit_assignment':
        $asgId = (int)($_POST['assignment_id'] ?? 0);
        if (!$asgId) jsonResponse(false, 'Invalid assignment.');
        if (empty($_FILES['submission_file'])) jsonResponse(false, 'No file uploaded.');

        // Student can only submit assignments from offerings they are enrolled in
        $asgChk = $db->prepare(
            "SELECT a.title, a.description, a.due_at
             FROM assignments a
             JOIN course_offerings co ON co.offering_id = a.offering_id
             JOIN enrollments e ON e.offering_id = co.offering_id
             WHERE a.assignment_id = :aid AND e.student_id = :uid
             LIMIT 1"
        );
        $asgChk->execute([':aid' => $asgId, ':uid' => $uid]);
        $asg = $asgChk->fetch();
        if (!$asg) {
            jsonResponse(false, 'You are not authorized to submit this assignment.');
        }

        $file     = $_FILES['submission_file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'Upload failed before saving the file.');
        }
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['pdf', 'doc', 'docx', 'txt', 'zip', 'png', 'jpg', 'jpeg'];
        if (!in_array($ext, $allowed)) jsonResponse(false, 'File type not allowed.');

        if ($file['size'] > 10 * 1024 * 1024) jsonResponse(false, 'File size must not exceed 10MB.');

        $dir = UPLOAD_DIR . "submissions/$uid/";
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            jsonResponse(false, 'Unable to create the submission folder.');
        }
        if (!is_writable($dir)) {
            jsonResponse(false, 'Submission folder is not writable.');
        }
        $filename = uniqid("sub_{$asgId}_") . ".$ext";
        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            jsonResponse(false, 'Failed to upload file. Please try again.');
        }
        $filePath = "uploads/submissions/$uid/$filename";

        // Check if late
        $status = (strtotime($asg['due_at']) < time()) ? 'late' : 'submitted';

        $ins = $db->prepare(
            "INSERT INTO submissions (assignment_id, student_id, file_path, status)
             VALUES (:aid, :sid, :fp_insert, :st_insert)
             ON DUPLICATE KEY UPDATE file_path=:fp_update, status=:st_update, submitted_at=NOW()"
        );
        $ins->execute([
            ':aid' => $asgId,
            ':sid' => $uid,
            ':fp_insert' => $filePath,
            ':st_insert' => $status,
            ':fp_update' => $filePath,
            ':st_update' => $status,
        ]);

        // Keep linked planner task aligned with submission status.
        $taskStatus = ($status === 'late') ? 'Overdue' : 'Completed';
        $taskUpdate = $db->prepare(
            "UPDATE tasks
             SET status = :task_status
             WHERE user_id = :uid AND assignment_id = :aid"
        );
        $taskUpdate->execute([':task_status' => $taskStatus, ':uid' => $uid, ':aid' => $asgId]);

        if ($taskUpdate->rowCount() === 0) {
            $taskInsert = $db->prepare(
                "INSERT INTO tasks (user_id, assignment_id, task_name, description, due_at, priority, status)
                 VALUES (:uid, :aid, :task_name, :task_desc, :due_at, :prio, :task_status)"
            );
            $taskInsert->execute([
                ':uid' => $uid,
                ':aid' => $asgId,
                ':task_name' => 'Submit: ' . $asg['title'],
                ':task_desc' => $asg['description'],
                ':due_at' => $asg['due_at'],
                ':prio' => getTaskPriorityFromDueAt($asg['due_at']),
                ':task_status' => $taskStatus,
            ]);
        }

        // Confirmation notification
        $notif = $db->prepare(
            "INSERT INTO notifications (user_id, assignment_id, type, message)
             VALUES (:uid, :aid, 'Submission Confirmation', :msg)"
        );
        $notif->execute([
            ':uid' => $uid,
            ':aid' => $asgId,
            ':msg' => "Your submission for assignment ID $asgId has been received ($status)."
        ]);
        jsonResponse(true, 'Assignment submitted successfully.');
        break;

    // ── NOTIFICATIONS ──────────────────────────────────────
    case 'get_notifications':
        $stmt = $db->prepare(
            "SELECT * FROM notifications WHERE user_id=:uid ORDER BY sent_at DESC LIMIT 50"
        );
        $stmt->execute([':uid' => $uid]);
        jsonResponse(true, 'OK', ['notifications' => $stmt->fetchAll()]);
        break;

    case 'mark_notification_read':
        $nid = (int)($_POST['notification_id'] ?? 0);
        $db->prepare("UPDATE notifications SET read_at=NOW() WHERE notification_id=:id AND user_id=:uid")
            ->execute([':id' => $nid, ':uid' => $uid]);
        jsonResponse(true, 'Marked as read.');
        break;

    case 'mark_all_read':
        $db->prepare("UPDATE notifications SET read_at=NOW() WHERE user_id=:uid AND read_at IS NULL")
            ->execute([':uid' => $uid]);
        jsonResponse(true, 'All notifications marked as read.');
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
