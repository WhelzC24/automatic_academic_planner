<?php
// ============================================================
// BISU Planner - Instructor API Handler
// backend/instructor/instructor_api.php
// ============================================================

require_once __DIR__ . '/../config/helpers.php';
requireAuth('instructor');

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';
$db     = getDB();
$uid    = (int)$_SESSION['user_id'];

switch ($action) {

    // ── DASHBOARD ──────────────────────────────────────────
    case 'get_dashboard':
        // My courses/offerings
        $stmt = $db->prepare(
            "SELECT co.*, c.title, c.code,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.offering_id = co.offering_id) as student_count,
                    (SELECT COUNT(*) FROM assignments a WHERE a.offering_id = co.offering_id) as assignment_count
             FROM course_offerings co
             JOIN courses c ON c.course_id = co.course_id
             JOIN teaching_assignments ta ON ta.offering_id = co.offering_id AND ta.instructor_id = :uid
             ORDER BY co.term DESC, c.code"
        );
        $stmt->execute([':uid' => $uid]);
        $offerings = $stmt->fetchAll();

        // Recent submissions
        $stmt2 = $db->prepare(
            "SELECT sub.*, u.first_name, u.last_name, s.student_number,
                    a.title as assignment_title, c.code as course_code
             FROM submissions sub
             JOIN students s ON s.user_id = sub.student_id
             JOIN users u ON u.user_id = sub.student_id
             JOIN assignments a ON a.assignment_id = sub.assignment_id
             JOIN course_offerings co ON co.offering_id = a.offering_id
             JOIN courses c ON c.course_id = co.course_id
             JOIN teaching_assignments ta ON ta.offering_id = co.offering_id AND ta.instructor_id = :uid
             ORDER BY sub.submitted_at DESC LIMIT 10"
        );
        $stmt2->execute([':uid' => $uid]);
        $submissions = $stmt2->fetchAll();

        jsonResponse(true, 'OK', ['offerings' => $offerings, 'recent_submissions' => $submissions]);
        break;

    // ── ASSIGNMENTS ────────────────────────────────────────
    case 'get_my_assignments':
        $stmt = $db->prepare(
            "SELECT a.*, c.title as course_title, c.code, co.section, co.term,
                    (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.assignment_id) as submission_count,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.offering_id = a.offering_id) as enrolled_count
             FROM assignments a
             JOIN course_offerings co ON co.offering_id = a.offering_id
             JOIN courses c ON c.course_id = co.course_id
             JOIN teaching_assignments ta ON ta.offering_id = co.offering_id AND ta.instructor_id = :uid
             ORDER BY a.due_at DESC"
        );
        $stmt->execute([':uid' => $uid]);
        jsonResponse(true, 'OK', ['assignments' => $stmt->fetchAll()]);
        break;

    case 'create_assignment':
        $offeringId = (int)($_POST['offering_id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $dueAt      = $_POST['due_at'] ?? '';
        $maxScore   = (float)($_POST['max_score'] ?? 100);

        if (!$offeringId || empty($title) || empty($dueAt)) {
            jsonResponse(false, 'Required fields missing.');
        }

        // Verify instructor owns this offering
        $chk = $db->prepare(
            "SELECT ta_id FROM teaching_assignments
             WHERE offering_id=:oid AND instructor_id=:uid"
        );
        $chk->execute([':oid' => $offeringId, ':uid' => $uid]);
        if (!$chk->fetch()) jsonResponse(false, 'Unauthorized.');

        $stmt = $db->prepare(
            "INSERT INTO assignments (offering_id, created_by, title, description, due_at, max_score)
             VALUES (:oid, :uid, :t, :d, :due, :ms)"
        );
        $stmt->execute([':oid' => $offeringId, ':uid' => $uid, ':t' => $title, ':d' => $desc, ':due' => $dueAt, ':ms' => $maxScore]);
        $asgId = $db->lastInsertId();

        $hoursUntilDue = (strtotime($dueAt) - time()) / 3600;
        if ($hoursUntilDue <= 24) {
            $priority = 'Urgent';
        } elseif ($hoursUntilDue <= 72) {
            $priority = 'High';
        } elseif ($hoursUntilDue <= 168) {
            $priority = 'Medium';
        } else {
            $priority = 'Low';
        }

        // Notify enrolled students
        $enrolled = $db->prepare(
            "SELECT student_id FROM enrollments WHERE offering_id=:oid"
        );
        $enrolled->execute([':oid' => $offeringId]);
        $students = $enrolled->fetchAll();

        // Auto-generate planner tasks for enrolled students
        $taskExistsStmt = $db->prepare(
            "SELECT task_id FROM tasks WHERE user_id=:uid AND assignment_id=:aid LIMIT 1"
        );
        $taskInsertStmt = $db->prepare(
            "INSERT INTO tasks (user_id, assignment_id, task_name, description, due_at, priority, status)
             VALUES (:uid, :aid, :task_name, :task_desc, :due_at, :prio, :status)"
        );

        foreach ($students as $row) {
            $studentId = (int)$row['student_id'];
            $taskExistsStmt->execute([':uid' => $studentId, ':aid' => $asgId]);
            if (!$taskExistsStmt->fetch()) {
                $taskInsertStmt->execute([
                    ':uid' => $studentId,
                    ':aid' => $asgId,
                    ':task_name' => "Submit: $title",
                    ':task_desc' => $desc,
                    ':due_at' => $dueAt,
                    ':prio' => $priority,
                    ':status' => (strtotime($dueAt) < time()) ? 'Overdue' : 'Pending',
                ]);
            }
        }

        $notifStmt = $db->prepare(
            "INSERT INTO notifications (user_id, assignment_id, type, message)
             VALUES (:uid, :aid, 'Assignment Posted', :msg)"
        );
        foreach ($students as $row) {
            $notifStmt->execute([
                ':uid' => $row['student_id'],
                ':aid' => $asgId,
                ':msg' => "New assignment posted: \"$title\" — Due " . date('M d, Y h:i A', strtotime($dueAt))
            ]);
        }

        logAction('CREATE_ASSIGNMENT', "Assignment '$title' created.");
        jsonResponse(true, 'Assignment created, students notified, and planner tasks generated.', ['assignment_id' => $asgId]);
        break;

    case 'update_assignment':
        $asgId    = (int)($_POST['assignment_id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $dueAt    = $_POST['due_at'] ?? '';
        $maxScore = (float)($_POST['max_score'] ?? 100);

        $hoursUntilDue = (strtotime($dueAt) - time()) / 3600;
        if ($hoursUntilDue <= 24) {
            $priority = 'Urgent';
        } elseif ($hoursUntilDue <= 72) {
            $priority = 'High';
        } elseif ($hoursUntilDue <= 168) {
            $priority = 'Medium';
        } else {
            $priority = 'Low';
        }

        $stmt = $db->prepare(
            "UPDATE assignments a
             JOIN course_offerings co ON co.offering_id = a.offering_id
             JOIN teaching_assignments ta ON ta.offering_id = co.offering_id AND ta.instructor_id = :uid
             SET a.title=:t, a.description=:d, a.due_at=:due, a.max_score=:ms
             WHERE a.assignment_id=:aid"
        );
        $stmt->execute([':uid' => $uid, ':t' => $title, ':d' => $desc, ':due' => $dueAt, ':ms' => $maxScore, ':aid' => $asgId]);

        // Keep auto-generated planner tasks in sync with assignment changes
        $syncTasks = $db->prepare(
            "UPDATE tasks
             SET task_name=:task_name,
                 description=:task_desc,
                 due_at=:task_due,
                 priority=:task_prio,
                 status = CASE
                     WHEN status = 'Completed' THEN status
                     WHEN :due_for_status < NOW() THEN 'Overdue'
                     WHEN status = 'Overdue' AND :due_for_reopen >= NOW() THEN 'Pending'
                     ELSE status
                 END
             WHERE assignment_id=:aid"
        );
        $syncTasks->execute([
            ':task_name' => "Submit: $title",
            ':task_desc' => $desc,
            ':task_due' => $dueAt,
            ':task_prio' => $priority,
            ':due_for_status' => $dueAt,
            ':due_for_reopen' => $dueAt,
            ':aid' => $asgId,
        ]);

        jsonResponse(true, 'Assignment updated.');
        break;

    case 'delete_assignment':
        $asgId = (int)($_POST['assignment_id'] ?? 0);
        $stmt  = $db->prepare(
            "DELETE a FROM assignments a
             JOIN course_offerings co ON co.offering_id = a.offering_id
             JOIN teaching_assignments ta ON ta.offering_id = co.offering_id AND ta.instructor_id = :uid
             WHERE a.assignment_id = :aid"
        );
        $stmt->execute([':uid' => $uid, ':aid' => $asgId]);
        jsonResponse(true, 'Assignment deleted.');
        break;

    // ── SUBMISSIONS ────────────────────────────────────────
    case 'get_submissions':
        $asgId = (int)($_GET['assignment_id'] ?? 0);
        $stmt  = $db->prepare(
            "SELECT sub.*, u.first_name, u.last_name, s.student_number, s.program, s.year_level
             FROM submissions sub
             JOIN students s ON s.user_id = sub.student_id
             JOIN users u ON u.user_id = sub.student_id
             JOIN assignments a ON a.assignment_id = sub.assignment_id
             JOIN course_offerings co ON co.offering_id = a.offering_id
             JOIN teaching_assignments ta ON ta.offering_id = co.offering_id AND ta.instructor_id = :uid
             WHERE sub.assignment_id = :aid
             ORDER BY sub.submitted_at"
        );
        $stmt->execute([':uid' => $uid, ':aid' => $asgId]);
        jsonResponse(true, 'OK', ['submissions' => $stmt->fetchAll()]);
        break;

    case 'grade_submission':
        $subId    = (int)($_POST['submission_id'] ?? 0);
        $grade    = (float)($_POST['grade'] ?? 0);
        $feedback = trim($_POST['feedback'] ?? '');

        $stmt = $db->prepare(
            "UPDATE submissions sub
             JOIN assignments a ON a.assignment_id = sub.assignment_id
             JOIN course_offerings co ON co.offering_id = a.offering_id
             JOIN teaching_assignments ta ON ta.offering_id = co.offering_id AND ta.instructor_id = :uid
             SET sub.grade=:g, sub.feedback=:fb, sub.status='graded'
             WHERE sub.submission_id = :sid"
        );
        $stmt->execute([':uid' => $uid, ':g' => $grade, ':fb' => $feedback, ':sid' => $subId]);
        jsonResponse(true, 'Grade saved.');
        break;

    // ── MY OFFERINGS ───────────────────────────────────────
    case 'get_my_offerings':
        $stmt = $db->prepare(
            "SELECT co.*, c.title, c.code, c.units
             FROM course_offerings co
             JOIN courses c ON c.course_id = co.course_id
             JOIN teaching_assignments ta ON ta.offering_id = co.offering_id AND ta.instructor_id = :uid
             ORDER BY co.term DESC, c.code"
        );
        $stmt->execute([':uid' => $uid]);
        jsonResponse(true, 'OK', ['offerings' => $stmt->fetchAll()]);
        break;

    case 'update_offering_schedule':
        $offeringId = (int)($_POST['offering_id'] ?? 0);
        $schedule = trim($_POST['schedule'] ?? '');
        $room = trim($_POST['room'] ?? '');

        if (!$offeringId) {
            jsonResponse(false, 'Invalid offering.');
        }

        $owning = $db->prepare(
            "SELECT ta_id
             FROM teaching_assignments
             WHERE offering_id = :oid AND instructor_id = :uid
             LIMIT 1"
        );
        $owning->execute([':oid' => $offeringId, ':uid' => $uid]);
        if (!$owning->fetch()) {
            jsonResponse(false, 'Unauthorized.');
        }

        $upd = $db->prepare(
            "UPDATE course_offerings
             SET schedule = :schedule,
                 room = :room
             WHERE offering_id = :oid"
        );
        $upd->execute([
            ':schedule' => $schedule,
            ':room' => $room,
            ':oid' => $offeringId,
        ]);

        jsonResponse(true, 'Offering schedule updated.');
        break;

    case 'create_offering_event':
        $offeringId = (int)($_POST['offering_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startsAtInput = trim($_POST['starts_at'] ?? '');
        $endsAtInput = trim($_POST['ends_at'] ?? '');
        $type = trim($_POST['type'] ?? 'Exam');
        $color = trim($_POST['color'] ?? '#1e3a5f');

        if (!$offeringId || $title === '' || $startsAtInput === '' || $endsAtInput === '') {
            jsonResponse(false, 'Required fields missing.');
        }

        $validTypes = ['Exam', 'Activity', 'Quiz', 'Presentation', 'Meeting'];
        if (!in_array($type, $validTypes, true)) {
            $type = 'Exam';
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#1e3a5f';
        }

        $startsAtTs = strtotime($startsAtInput);
        $endsAtTs = strtotime($endsAtInput);
        if (!$startsAtTs || !$endsAtTs || $endsAtTs <= $startsAtTs) {
            jsonResponse(false, 'Please provide a valid time range.');
        }

        $ownsOffering = $db->prepare(
            "SELECT ta_id
             FROM teaching_assignments
             WHERE offering_id = :oid AND instructor_id = :uid
             LIMIT 1"
        );
        $ownsOffering->execute([':oid' => $offeringId, ':uid' => $uid]);
        if (!$ownsOffering->fetch()) {
            jsonResponse(false, 'Unauthorized.');
        }

        $studentsStmt = $db->prepare(
            "SELECT e.student_id
             FROM enrollments e
             WHERE e.offering_id = :oid"
        );
        $studentsStmt->execute([':oid' => $offeringId]);
        $students = $studentsStmt->fetchAll();

        if (!$students) {
            jsonResponse(false, 'No enrolled students found for this offering.');
        }

        $startsAt = date('Y-m-d H:i:s', $startsAtTs);
        $endsAt = date('Y-m-d H:i:s', $endsAtTs);

        $scheduleInsert = $db->prepare(
            "INSERT INTO schedules (user_id, title, description, starts_at, ends_at, type, color)
             VALUES (:uid, :title, :description, :starts_at, :ends_at, :type, :color)"
        );
        $notifInsert = $db->prepare(
            "INSERT INTO notifications (user_id, schedule_id, type, message)
             VALUES (:uid, :sid, 'Schedule Reminder', :msg)"
        );

        $created = 0;
        foreach ($students as $student) {
            $scheduleInsert->execute([
                ':uid' => (int)$student['student_id'],
                ':title' => $title,
                ':description' => $description !== '' ? $description : null,
                ':starts_at' => $startsAt,
                ':ends_at' => $endsAt,
                ':type' => $type,
                ':color' => $color,
            ]);
            $scheduleId = (int)$db->lastInsertId();
            $notifInsert->execute([
                ':uid' => (int)$student['student_id'],
                ':sid' => $scheduleId,
                ':msg' => "New $type scheduled: $title on " . date('M d, Y h:i A', $startsAtTs),
            ]);
            $created++;
        }

        logAction('CREATE_SCHEDULE_EVENT', "$type '$title' scheduled for offering ID $offeringId");
        jsonResponse(true, "Schedule event created for $created students.");
        break;

    case 'get_offering_events':
        $stmt = $db->prepare(
            "SELECT MIN(s.schedule_id) as schedule_id,
                    s.title,
                    s.description,
                    s.starts_at,
                    s.ends_at,
                    s.type,
                    s.color,
                    COUNT(*) as student_count
             FROM schedules s
             JOIN enrollments e ON e.student_id = s.user_id
             JOIN course_offerings co ON co.offering_id = e.offering_id
             JOIN teaching_assignments ta ON ta.offering_id = co.offering_id AND ta.instructor_id = :uid
             WHERE s.type IN ('Exam','Activity','Quiz','Presentation','Meeting')
             GROUP BY s.title, s.description, s.starts_at, s.ends_at, s.type, s.color
             ORDER BY s.starts_at DESC"
        );
        $stmt->execute([':uid' => $uid]);
        jsonResponse(true, 'OK', ['events' => $stmt->fetchAll()]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
