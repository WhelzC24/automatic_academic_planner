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
        $chk->execute([':oid'=>$offeringId, ':uid'=>$uid]);
        if (!$chk->fetch()) jsonResponse(false, 'Unauthorized.');

        $stmt = $db->prepare(
            "INSERT INTO assignments (offering_id, created_by, title, description, due_at, max_score)
             VALUES (:oid, :uid, :t, :d, :due, :ms)"
        );
        $stmt->execute([':oid'=>$offeringId,':uid'=>$uid,':t'=>$title,':d'=>$desc,':due'=>$dueAt,':ms'=>$maxScore]);
        $asgId = $db->lastInsertId();

        // Notify enrolled students
        $enrolled = $db->prepare(
            "SELECT student_id FROM enrollments WHERE offering_id=:oid"
        );
        $enrolled->execute([':oid'=>$offeringId]);
        $notifStmt = $db->prepare(
            "INSERT INTO notifications (user_id, assignment_id, type, message)
             VALUES (:uid, :aid, 'Assignment Posted', :msg)"
        );
        foreach ($enrolled->fetchAll() as $row) {
            $notifStmt->execute([
                ':uid' => $row['student_id'],
                ':aid' => $asgId,
                ':msg' => "New assignment posted: \"$title\" — Due " . date('M d, Y h:i A', strtotime($dueAt))
            ]);
        }

        logAction('CREATE_ASSIGNMENT', "Assignment '$title' created.");
        jsonResponse(true, 'Assignment created and students notified.', ['assignment_id' => $asgId]);
        break;

    case 'update_assignment':
        $asgId    = (int)($_POST['assignment_id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $dueAt    = $_POST['due_at'] ?? '';
        $maxScore = (float)($_POST['max_score'] ?? 100);

        $stmt = $db->prepare(
            "UPDATE assignments a
             JOIN course_offerings co ON co.offering_id = a.offering_id
             JOIN teaching_assignments ta ON ta.offering_id = co.offering_id AND ta.instructor_id = :uid
             SET a.title=:t, a.description=:d, a.due_at=:due, a.max_score=:ms
             WHERE a.assignment_id=:aid"
        );
        $stmt->execute([':uid'=>$uid,':t'=>$title,':d'=>$desc,':due'=>$dueAt,':ms'=>$maxScore,':aid'=>$asgId]);
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
        $stmt->execute([':uid'=>$uid, ':aid'=>$asgId]);
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
        $stmt->execute([':uid'=>$uid, ':aid'=>$asgId]);
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
        $stmt->execute([':uid'=>$uid, ':g'=>$grade, ':fb'=>$feedback, ':sid'=>$subId]);
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

    default:
        jsonResponse(false, 'Invalid action.');
}
