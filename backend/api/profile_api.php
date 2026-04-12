<?php
// ============================================================
// BISU Planner — Profile & Settings API
// backend/api/profile_api.php
// ============================================================

require_once __DIR__ . '/../config/helpers.php';
requireAuth(); // any authenticated user

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';
$db     = getDB();
$uid    = (int)$_SESSION['user_id'];
$role   = $_SESSION['role'];

switch ($action) {

    // ── GET PROFILE ────────────────────────────────────────
    case 'get_profile':
        $stmt = $db->prepare(
            "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name,
                    u.phone, u.role, u.created_at,
                    s.student_number, s.program, s.year_level, s.gpa,
                    i.department, i.designation, i.office_location
             FROM users u
             LEFT JOIN students s ON s.user_id = u.user_id
             LEFT JOIN instructors i ON i.user_id = u.user_id
             WHERE u.user_id = :id"
        );
        $stmt->execute([':id' => $uid]);
        $profile = $stmt->fetch();
        if (!$profile) jsonResponse(false, 'User not found.');
        unset($profile['password_hash']);
        jsonResponse(true, 'OK', ['profile' => $profile]);
        break;

    // ── UPDATE PROFILE ─────────────────────────────────────
    case 'update_profile':
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $phone     = trim($_POST['phone']      ?? '');
        $email     = trim($_POST['email']      ?? '');

        if (empty($firstName) || empty($lastName) || empty($email)) {
            jsonResponse(false, 'First name, last name, and email are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'Invalid email address.');
        }

        // Check email uniqueness (excluding self)
        $chk = $db->prepare("SELECT user_id FROM users WHERE email = :e AND user_id != :id");
        $chk->execute([':e' => $email, ':id' => $uid]);
        if ($chk->fetch()) jsonResponse(false, 'Email is already used by another account.');

        $db->prepare(
            "UPDATE users SET first_name=:fn, last_name=:ln, phone=:ph, email=:em WHERE user_id=:id"
        )->execute([':fn'=>$firstName, ':ln'=>$lastName, ':ph'=>$phone, ':em'=>$email, ':id'=>$uid]);

        // Update role-specific fields
        if ($role === 'student') {
            $program   = trim($_POST['program']    ?? '');
            $yearLevel = (int)($_POST['year_level'] ?? 1);
            $db->prepare("UPDATE students SET program=:p, year_level=:y WHERE user_id=:id")
               ->execute([':p'=>$program, ':y'=>$yearLevel, ':id'=>$uid]);
        } elseif ($role === 'instructor') {
            $dept  = trim($_POST['department']     ?? '');
            $desig = trim($_POST['designation']    ?? '');
            $office= trim($_POST['office_location']?? '');
            $db->prepare("UPDATE instructors SET department=:d, designation=:des, office_location=:o WHERE user_id=:id")
               ->execute([':d'=>$dept, ':des'=>$desig, ':o'=>$office, ':id'=>$uid]);
        }

        // Update session name
        $_SESSION['full_name'] = $firstName . ' ' . $lastName;
        logAction('UPDATE_PROFILE', "User $uid updated profile.");
        jsonResponse(true, 'Profile updated successfully.');
        break;

    // ── CHANGE PASSWORD ────────────────────────────────────
    case 'change_password':
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($current) || empty($newPass) || empty($confirm)) {
            jsonResponse(false, 'All password fields are required.');
        }
        if ($newPass !== $confirm) {
            jsonResponse(false, 'New passwords do not match.');
        }
        if (strlen($newPass) < 8) {
            jsonResponse(false, 'New password must be at least 8 characters.');
        }

        // Verify current password
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE user_id = :id");
        $stmt->execute([':id' => $uid]);
        $user = $stmt->fetch();

        if (!password_verify($current, $user['password_hash'])) {
            jsonResponse(false, 'Current password is incorrect.');
        }

        $newHash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("UPDATE users SET password_hash = :h WHERE user_id = :id")
           ->execute([':h' => $newHash, ':id' => $uid]);

        logAction('CHANGE_PASSWORD', "User $uid changed password.");
        jsonResponse(true, 'Password changed successfully.');
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
