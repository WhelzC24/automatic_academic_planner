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

switch ($action) {

    // ── DASHBOARD DATA ─────────────────────────────────────
    case 'get_dashboard':
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

    // Schedules are auto-generated from course offerings
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
            "SELECT a.due_at
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
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['pdf', 'doc', 'docx', 'txt', 'zip', 'png', 'jpg', 'jpeg'];
        if (!in_array($ext, $allowed)) jsonResponse(false, 'File type not allowed.');

        if ($file['size'] > 10 * 1024 * 1024) jsonResponse(false, 'File size must not exceed 10MB.');

        $dir = UPLOAD_DIR . "submissions/$uid/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = uniqid("sub_{$asgId}_") . ".$ext";
        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            jsonResponse(false, 'Failed to upload file. Please try again.');
        }
        $filePath = "uploads/submissions/$uid/$filename";

        // Check if late
        $status = (strtotime($asg['due_at']) < time()) ? 'late' : 'submitted';

        $ins = $db->prepare(
            "INSERT INTO submissions (assignment_id, student_id, file_path, status)
             VALUES (:aid, :sid, :fp, :st)
             ON DUPLICATE KEY UPDATE file_path=:fp, status=:st, submitted_at=NOW()"
        );
        $ins->execute([':aid' => $asgId, ':sid' => $uid, ':fp' => $filePath, ':st' => $status]);

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
