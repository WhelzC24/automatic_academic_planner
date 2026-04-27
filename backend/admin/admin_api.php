<?php
// ============================================================
// BISU Planner - Admin API Handler
// backend/admin/admin_api.php
// ============================================================

require_once __DIR__ . '/../config/helpers.php';
requireAuth('admin');

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';
$db     = getDB();
$uid    = (int)$_SESSION['user_id'];

ensureForcePasswordChangeColumn($db);

switch ($action) {

    // ── DASHBOARD STATS ────────────────────────────────────
    case 'get_stats':
        $stats = [];
        foreach (['student', 'instructor', 'admin'] as $role) {
            $s = $db->prepare("SELECT COUNT(*) as c FROM users WHERE role=:r AND is_active=1");
            $s->execute([':r' => $role]);
            $stats[$role . '_count'] = (int)$s->fetch()['c'];
        }
        $s = $db->prepare("SELECT COUNT(*) as c FROM courses");
        $stats['course_count'] = (int)$s->fetch()['c'];
        $s = $db->prepare("SELECT COUNT(*) as c FROM assignments WHERE due_at >= NOW()");
        $s->execute();
        $stats['active_assignments'] = (int)$s->fetch()['c'];
        $s = $db->prepare("SELECT COUNT(*) as c FROM submissions WHERE DATE(submitted_at) = CURDATE()");
        $s->execute();
        $stats['today_submissions'] = (int)$s->fetch()['c'];

        // Recent logs
        $logs = $db->query(
            "SELECT l.*, u.username FROM system_logs l
             LEFT JOIN users u ON u.user_id = l.user_id
             ORDER BY l.created_at DESC LIMIT 20"
        )->fetchAll();

        jsonResponse(true, 'OK', ['stats' => $stats, 'recent_logs' => $logs]);
        break;

    // ── USER MANAGEMENT ────────────────────────────────────
    case 'get_users':
        $role = $_GET['role'] ?? '';
        $sql  = "SELECT u.*, 
                    CASE u.role
                      WHEN 'student' THEN s.student_number
                      ELSE NULL
                    END as student_number,
                    CASE u.role
                      WHEN 'student' THEN s.program
                      WHEN 'instructor' THEN i.department
                      ELSE NULL
                    END as extra_info
                 FROM users u
                 LEFT JOIN students s ON s.user_id = u.user_id
                 LEFT JOIN instructors i ON i.user_id = u.user_id";
        if ($role) $sql .= " WHERE u.role = " . $db->quote($role);
        $sql .= " ORDER BY u.created_at DESC";
        $users = $db->query($sql)->fetchAll();
        jsonResponse(true, 'OK', ['users' => $users]);
        break;

    case 'add_student':
        $data = [
            'first_name'     => trim($_POST['first_name'] ?? ''),
            'last_name'      => trim($_POST['last_name'] ?? ''),
            'username'       => trim($_POST['username'] ?? ''),
            'email'          => trim($_POST['email'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'student_number' => trim($_POST['student_number'] ?? ''),
            'program'        => trim($_POST['program'] ?? ''),
            'year_level'     => (int)($_POST['year_level'] ?? 1),
        ];
        if (!$data['username'] || !$data['email'] || !$data['student_number']) {
            jsonResponse(false, 'Required fields missing.');
        }
        $defaultPassword = '12345';
        $hash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->beginTransaction();
        try {
            $ins = $db->prepare(
                "INSERT INTO users (username,email,password_hash,first_name,last_name,phone,role,must_change_password)
                 VALUES (:u,:e,:h,:fn,:ln,:ph,'student',1)"
            );
            $ins->execute([
                ':u' => $data['username'],
                ':e' => $data['email'],
                ':h' => $hash,
                ':fn' => $data['first_name'],
                ':ln' => $data['last_name'],
                ':ph' => $data['phone']
            ]);
            $newId = $db->lastInsertId();
            $db->prepare("INSERT INTO students (user_id,student_number,program,year_level) VALUES(:id,:sn,:pr,:yr)")
                ->execute([':id' => $newId, ':sn' => $data['student_number'], ':pr' => $data['program'], ':yr' => $data['year_level']]);
            $db->commit();
            logAction('ADD_STUDENT', "Added student {$data['username']}");
            jsonResponse(true, 'Student account created. Default password is 12345 and must be changed on first login.', ['user_id' => $newId]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(false, 'Failed to create student. Username or email may already exist.');
        }
        break;

    case 'add_instructor':
        $data = [
            'first_name'     => trim($_POST['first_name'] ?? ''),
            'last_name'      => trim($_POST['last_name'] ?? ''),
            'username'       => trim($_POST['username'] ?? ''),
            'email'          => trim($_POST['email'] ?? ''),
            'phone'          => trim($_POST['phone'] ?? ''),
            'department'     => trim($_POST['department'] ?? ''),
            'designation'    => trim($_POST['designation'] ?? ''),
            'office_location' => trim($_POST['office_location'] ?? ''),
        ];
        $defaultPassword = '12345';
        $hash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->beginTransaction();
        try {
            $ins = $db->prepare(
                "INSERT INTO users (username,email,password_hash,first_name,last_name,phone,role,must_change_password)
                 VALUES (:u,:e,:h,:fn,:ln,:ph,'instructor',1)"
            );
            $ins->execute([
                ':u' => $data['username'],
                ':e' => $data['email'],
                ':h' => $hash,
                ':fn' => $data['first_name'],
                ':ln' => $data['last_name'],
                ':ph' => $data['phone']
            ]);
            $newId = $db->lastInsertId();
            $db->prepare(
                "INSERT INTO instructors (user_id,department,designation,office_location)
                 VALUES(:id,:dep,:des,:off)"
            )->execute([':id' => $newId, ':dep' => $data['department'], ':des' => $data['designation'], ':off' => $data['office_location']]);
            $db->commit();
            logAction('ADD_INSTRUCTOR', "Added instructor {$data['username']}");
            jsonResponse(true, 'Instructor account created. Default password is 12345 and must be changed on first login.', ['user_id' => $newId]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(false, 'Failed to create instructor.');
        }
        break;

    case 'toggle_user':
        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) jsonResponse(false, 'Invalid user ID.');
        if ($userId === $uid) jsonResponse(false, 'You cannot deactivate or activate your own account.');
        $db->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id=:id")->execute([':id' => $userId]);
        logAction('TOGGLE_USER', "Toggled user $userId");
        jsonResponse(true, 'User status updated.');
        break;

    case 'delete_user':
        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) jsonResponse(false, 'Invalid user ID.');
        if ($userId === $uid) jsonResponse(false, 'Cannot delete yourself.');
        $db->prepare("DELETE FROM users WHERE user_id=:id")->execute([':id' => $userId]);
        logAction('DELETE_USER', "Deleted user $userId");
        jsonResponse(true, 'User deleted.');
        break;

    case 'reset_user_password':
        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) jsonResponse(false, 'Invalid user ID.');
        if ($userId === $uid) jsonResponse(false, 'You cannot reset your own password from this action.');

        $stmt = $db->prepare("SELECT user_id, username, role FROM users WHERE user_id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $target = $stmt->fetch();

        if (!$target) jsonResponse(false, 'User not found.');
        if (!in_array($target['role'], ['student', 'instructor'], true)) {
            jsonResponse(false, 'Only student and instructor passwords can be reset.');
        }

        $defaultPassword = '12345';
        $hash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $db->prepare(
            "UPDATE users
             SET password_hash = :hash,
                 must_change_password = 1,
                 updated_at = NOW()
             WHERE user_id = :id"
        )->execute([':hash' => $hash, ':id' => $userId]);

        logAction('RESET_USER_PASSWORD', "Reset password for {$target['role']} {$target['username']} (ID {$target['user_id']})");
        jsonResponse(true, 'Password reset to default (12345). User must change password on next login.');
        break;

    // ── COURSE MANAGEMENT ──────────────────────────────────
    case 'get_courses':
        $courses = $db->query(
            "SELECT c.*, COUNT(co.offering_id) as offering_count
             FROM courses c LEFT JOIN course_offerings co ON co.course_id = c.course_id
             GROUP BY c.course_id ORDER BY c.code"
        )->fetchAll();
        jsonResponse(true, 'OK', ['courses' => $courses]);
        break;

    case 'add_course':
        $code  = strtoupper(trim($_POST['code'] ?? ''));
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $units = (int)($_POST['units'] ?? 3);
        if (!$code || !$title) jsonResponse(false, 'Code and title are required.');
        if ($units < 1 || $units > 6) jsonResponse(false, 'Units must be between 1 and 6.');
        try {
            $db->prepare("INSERT INTO courses (code,title,description,units) VALUES(:c,:t,:d,:u)")
                ->execute([':c' => $code, ':t' => $title, ':d' => $desc, ':u' => $units]);
            jsonResponse(true, 'Course added.', ['course_id' => $db->lastInsertId()]);
        } catch (Exception $e) {
            jsonResponse(false, 'Course code already exists.');
        }
        break;

    case 'update_course':
        $courseId = (int)($_POST['course_id'] ?? 0);
        $code     = strtoupper(trim($_POST['code'] ?? ''));
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $units    = (int)($_POST['units'] ?? 3);

        if (!$courseId) jsonResponse(false, 'Invalid course ID.');
        if (!$code || !$title) jsonResponse(false, 'Code and title are required.');
        if ($units < 1 || $units > 6) jsonResponse(false, 'Units must be between 1 and 6.');

        $exists = $db->prepare("SELECT course_id FROM courses WHERE course_id = :id LIMIT 1");
        $exists->execute([':id' => $courseId]);
        if (!$exists->fetch()) jsonResponse(false, 'Course not found.');

        try {
            $db->prepare(
                "UPDATE courses
                 SET code = :c, title = :t, description = :d, units = :u
                 WHERE course_id = :id"
            )->execute([
                ':c' => $code,
                ':t' => $title,
                ':d' => $desc,
                ':u' => $units,
                ':id' => $courseId,
            ]);
            logAction('UPDATE_COURSE', "Updated course ID $courseId ($code)");
            jsonResponse(true, 'Course updated successfully.');
        } catch (Exception $e) {
            jsonResponse(false, 'Course code already exists.');
        }
        break;

    case 'add_offering':
        $courseId  = (int)($_POST['course_id'] ?? 0);
        $term      = trim($_POST['term'] ?? '');
        $section   = trim($_POST['section'] ?? '');
        $schedule  = trim($_POST['schedule'] ?? '');
        $room      = trim($_POST['room'] ?? '');
        $instrId   = (int)($_POST['instructor_id'] ?? 0);
        if (!$courseId || !$term || !$section) jsonResponse(false, 'Required fields missing.');
        $db->prepare("INSERT INTO course_offerings (course_id,term,section,schedule,room) VALUES(:cid,:t,:s,:sch,:r)")
            ->execute([':cid' => $courseId, ':t' => $term, ':s' => $section, ':sch' => $schedule, ':r' => $room]);
        $offeringId = $db->lastInsertId();
        if ($instrId) {
            $db->prepare("INSERT INTO teaching_assignments (offering_id,instructor_id) VALUES(:oid,:iid)")
                ->execute([':oid' => $offeringId, ':iid' => $instrId]);
        }
        jsonResponse(true, 'Course offering created.', ['offering_id' => $offeringId]);
        break;

    case 'update_offering':
        $offeringId = (int)($_POST['offering_id'] ?? 0);
        $courseId   = (int)($_POST['course_id'] ?? 0);
        $term       = trim($_POST['term'] ?? '');
        $section    = trim($_POST['section'] ?? '');
        $schedule   = trim($_POST['schedule'] ?? '');
        $room       = trim($_POST['room'] ?? '');
        $instrId    = (int)($_POST['instructor_id'] ?? 0);

        if (!$offeringId) jsonResponse(false, 'Invalid offering ID.');
        if (!$courseId || !$term || !$section) jsonResponse(false, 'Required fields missing.');

        $stmt = $db->prepare("SELECT offering_id FROM course_offerings WHERE offering_id = :id LIMIT 1");
        $stmt->execute([':id' => $offeringId]);
        if (!$stmt->fetch()) jsonResponse(false, 'Offering not found.');

        if ($instrId > 0) {
            $ins = $db->prepare("SELECT user_id FROM instructors WHERE user_id = :id LIMIT 1");
            $ins->execute([':id' => $instrId]);
            if (!$ins->fetch()) jsonResponse(false, 'Selected instructor is invalid.');
        }

        $db->beginTransaction();
        try {
            $db->prepare(
                "UPDATE course_offerings
                 SET course_id = :cid,
                     term = :t,
                     section = :s,
                     schedule = :sch,
                     room = :r
                 WHERE offering_id = :oid"
            )->execute([
                ':cid' => $courseId,
                ':t' => $term,
                ':s' => $section,
                ':sch' => $schedule,
                ':r' => $room,
                ':oid' => $offeringId,
            ]);

            $db->prepare("DELETE FROM teaching_assignments WHERE offering_id = :oid")
                ->execute([':oid' => $offeringId]);
            if ($instrId > 0) {
                $db->prepare("INSERT INTO teaching_assignments (offering_id, instructor_id) VALUES(:oid, :iid)")
                    ->execute([':oid' => $offeringId, ':iid' => $instrId]);
            }

            $db->commit();
            logAction('UPDATE_OFFERING', "Updated offering ID $offeringId");
            jsonResponse(true, 'Offering updated successfully.');
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(false, 'Failed to update offering.');
        }
        break;

    case 'enroll_student':
        $studentId  = (int)($_POST['student_id'] ?? 0);
        $offeringId = (int)($_POST['offering_id'] ?? 0);
        try {
            $db->prepare("INSERT INTO enrollments (offering_id,student_id) VALUES(:oid,:sid)")
                ->execute([':oid' => $offeringId, ':sid' => $studentId]);
            jsonResponse(true, 'Student enrolled.');
        } catch (Exception $e) {
            jsonResponse(false, 'Student already enrolled in this offering.');
        }
        break;

    // ── ENROLLMENTS ────────────────────────────────────────
    case 'get_enrollments':
        $offeringId = (int)($_GET['offering_id'] ?? 0);
        $sql = "SELECT e.enrollment_id, e.enrolled_at,
                       u.user_id as student_id, u.first_name, u.last_name,
                       s.student_number, s.program,
                       c.code as course_code, c.title as course_title,
                       co.section, co.term, co.offering_id
                FROM enrollments e
                JOIN students s ON s.user_id = e.student_id
                JOIN users u ON u.user_id = e.student_id
                JOIN course_offerings co ON co.offering_id = e.offering_id
                JOIN courses c ON c.course_id = co.course_id";
        if ($offeringId) {
            $sql .= " WHERE e.offering_id = :oid";
            $stmt = $db->prepare($sql . " ORDER BY u.last_name, u.first_name");
            $stmt->execute([':oid' => $offeringId]);
        } else {
            $stmt = $db->query($sql . " ORDER BY co.term DESC, c.code, u.last_name");
        }
        jsonResponse(true, 'OK', ['enrollments' => $stmt->fetchAll()]);
        break;

    case 'unenroll_student':
        $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
        if (!$enrollmentId) jsonResponse(false, 'Invalid enrollment ID.');
        $db->prepare("DELETE FROM enrollments WHERE enrollment_id = :id")->execute([':id' => $enrollmentId]);
        logAction('UNENROLL', "Removed enrollment ID $enrollmentId");
        jsonResponse(true, 'Student unenrolled successfully.');
        break;

    case 'get_offering_list':
        $offerings = $db->query(
            "SELECT co.offering_id, co.course_id, co.term, co.section, co.schedule, co.room,
                    c.code, c.title,
                    ta.instructor_id,
                    u.first_name, u.last_name,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.offering_id = co.offering_id) as student_count
             FROM course_offerings co
             JOIN courses c ON c.course_id = co.course_id
             LEFT JOIN teaching_assignments ta ON ta.offering_id = co.offering_id
             LEFT JOIN users u ON u.user_id = ta.instructor_id
             ORDER BY co.term DESC, c.code"
        )->fetchAll();
        jsonResponse(true, 'OK', ['offerings' => $offerings]);
        break;

    case 'delete_course':
        $courseId = (int)($_POST['course_id'] ?? 0);
        if (!$courseId) jsonResponse(false, 'Invalid course.');

        $offeringCountStmt = $db->prepare("SELECT COUNT(*) AS c FROM course_offerings WHERE course_id = :id");
        $offeringCountStmt->execute([':id' => $courseId]);
        $offeringCount = (int)$offeringCountStmt->fetch()['c'];

        $enrollmentCountStmt = $db->prepare(
            "SELECT COUNT(*) AS c
             FROM enrollments e
             JOIN course_offerings co ON co.offering_id = e.offering_id
             WHERE co.course_id = :id"
        );
        $enrollmentCountStmt->execute([':id' => $courseId]);
        $enrollmentCount = (int)$enrollmentCountStmt->fetch()['c'];

        if ($offeringCount > 0 || $enrollmentCount > 0) {
            logAction('DELETE_COURSE_BLOCKED', "Blocked deletion for course ID $courseId due to dependencies.");
            jsonResponse(false, 'Cannot delete course with existing offerings or enrollments.');
        }

        $db->prepare("DELETE FROM courses WHERE course_id = :id")->execute([':id' => $courseId]);
        logAction('DELETE_COURSE', "Deleted course ID $courseId");
        jsonResponse(true, 'Course deleted.');
        break;

    case 'delete_offering':
        $offeringId = (int)($_POST['offering_id'] ?? 0);
        if (!$offeringId) jsonResponse(false, 'Invalid offering.');

        $db->prepare("DELETE FROM enrollments WHERE offering_id = :id")->execute([':id' => $offeringId]);
        $db->prepare("DELETE FROM teaching_assignments WHERE offering_id = :id")->execute([':id' => $offeringId]);
        $db->prepare("DELETE FROM course_offerings WHERE offering_id = :id")->execute([':id' => $offeringId]);
        logAction('DELETE_OFFERING', "Deleted offering ID $offeringId");
        jsonResponse(true, 'Offering deleted.');
        break;

    case 'assign_instructor':
        $offeringId  = (int)($_POST['offering_id']  ?? 0);
        $instructorId = (int)($_POST['instructor_id'] ?? 0);
        if (!$offeringId || !$instructorId) jsonResponse(false, 'Missing data.');
        try {
            $db->prepare("INSERT INTO teaching_assignments (offering_id, instructor_id) VALUES(:oid,:iid)")
                ->execute([':oid' => $offeringId, ':iid' => $instructorId]);
            jsonResponse(true, 'Instructor assigned.');
        } catch (Exception $e) {
            // Already assigned — update
            $db->prepare("UPDATE teaching_assignments SET instructor_id=:iid WHERE offering_id=:oid")
                ->execute([':iid' => $instructorId, ':oid' => $offeringId]);
            jsonResponse(true, 'Instructor assignment updated.');
        }
        break;

    case 'get_system_summary':
        $summary = [];
        $tables  = ['users', 'students', 'courses', 'course_offerings', 'enrollments', 'assignments', 'submissions', 'tasks', 'notifications', 'system_logs'];
        foreach ($tables as $t) {
            $cnt = $db->query("SELECT COUNT(*) as c FROM `$t`")->fetch()['c'];
            $summary[$t] = (int)$cnt;
        }
        jsonResponse(true, 'OK', ['summary' => $summary]);
        break;

    // ── SYSTEM LOGS ────────────────────────────────────────
    case 'get_logs':
        $limit = (int)($_GET['limit'] ?? 50);
        $logs  = $db->query(
            "SELECT l.*, u.username, u.role FROM system_logs l
             LEFT JOIN users u ON u.user_id = l.user_id
             ORDER BY l.created_at DESC LIMIT $limit"
        )->fetchAll();
        jsonResponse(true, 'OK', ['logs' => $logs]);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
