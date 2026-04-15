<?php
// ============================================================
// BISU Planner - Authentication Handler
// backend/auth/auth_handler.php
// ============================================================

require_once __DIR__ . '/../config/helpers.php';

startSession();
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------------
    case 'login':
        // ----------------------------------------------------------
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            jsonResponse(false, 'Username and password are required.');
        }

        $db = getDB();
        ensureForcePasswordChangeColumn($db);
        $stmt = $db->prepare(
            "SELECT user_id, username, email, password_hash, first_name, last_name, role, is_active, must_change_password
               FROM users WHERE username = :username OR email = :email LIMIT 1"
        );
        $stmt->execute([':username' => $username, ':email' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            logAction('LOGIN_FAILED', "Attempted login: $username");
            jsonResponse(false, 'Invalid username or password.');
        }

        if (!$user['is_active']) {
            jsonResponse(false, 'Your account has been deactivated. Contact the administrator.');
        }

        // Regenerate session
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['full_name']  = $user['first_name'] . ' ' . $user['last_name'];

        logAction('LOGIN_SUCCESS', "User {$user['username']} logged in.");

        // Auto-run maintenance tasks
        autoMarkOverdue();
        generateDeadlineNotifications();

        $redirects = [
            'student'    => APP_URL . '/frontend/pages/student/dashboard.php',
            'instructor' => APP_URL . '/frontend/pages/instructor/dashboard.php',
            'admin'      => APP_URL . '/frontend/pages/admin/dashboard.php',
        ];

        if ((int)$user['must_change_password'] === 1) {
            jsonResponse(true, 'Password reset detected. Please set a new password to continue.', [
                'redirect' => $redirects[$user['role']],
                'must_change_password' => true,
            ]);
        }

        jsonResponse(true, 'Login successful.', ['redirect' => $redirects[$user['role']]]);
        break;

    // ----------------------------------------------------------
    case 'change_password_required':
        // ----------------------------------------------------------
        if (empty($_SESSION['user_id'])) {
            jsonResponse(false, 'Session expired. Please log in again.');
        }

        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword === '' || $confirmPassword === '') {
            jsonResponse(false, 'Both password fields are required.');
        }
        if ($newPassword !== $confirmPassword) {
            jsonResponse(false, 'Passwords do not match.');
        }

        $db = getDB();
        ensureForcePasswordChangeColumn($db);

        $userId = (int)$_SESSION['user_id'];
        $stmt = $db->prepare("SELECT must_change_password, role, username FROM users WHERE user_id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonResponse(false, 'User not found.');
        }
        if ((int)$user['must_change_password'] !== 1) {
            jsonResponse(false, 'Password change is not required for this account.');
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare(
            "UPDATE users
             SET password_hash = :hash,
                 must_change_password = 0,
                 updated_at = NOW()
             WHERE user_id = :id"
        )->execute([':hash' => $hash, ':id' => $userId]);

        logAction('PASSWORD_CHANGED_REQUIRED', "Forced password updated for {$user['username']}");

        $redirects = [
            'student'    => APP_URL . '/frontend/pages/student/dashboard.php',
            'instructor' => APP_URL . '/frontend/pages/instructor/dashboard.php',
            'admin'      => APP_URL . '/frontend/pages/admin/dashboard.php',
        ];

        jsonResponse(true, 'Password updated successfully.', ['redirect' => $redirects[$user['role']] ?? APP_URL]);
        break;

    // ----------------------------------------------------------
    case 'register':
        // ----------------------------------------------------------
        $fields = [
            'first_name',
            'last_name',
            'username',
            'email',
            'password',
            'confirm_password',
            'phone',
            'student_number',
            'program',
            'year_level'
        ];
        $data = [];
        foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');

        // Validate
        if (
            empty($data['first_name']) || empty($data['last_name']) ||
            empty($data['username'])   || empty($data['email'])     ||
            empty($data['password'])   || empty($data['student_number'])
        ) {
            jsonResponse(false, 'All required fields must be filled.');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'Invalid email address.');
        }

        if ($data['password'] !== $data['confirm_password']) {
            jsonResponse(false, 'Passwords do not match.');
        }

        if (strlen($data['password']) < 8) {
            jsonResponse(false, 'Password must be at least 8 characters.');
        }

        $db = getDB();

        // Check uniqueness
        $check = $db->prepare("SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1");
        $check->execute([':u' => $data['username'], ':e' => $data['email']]);
        if ($check->fetch()) {
            jsonResponse(false, 'Username or email is already taken.');
        }

        $check2 = $db->prepare("SELECT user_id FROM students WHERE student_number = :sn LIMIT 1");
        $check2->execute([':sn' => $data['student_number']]);
        if ($check2->fetch()) {
            jsonResponse(false, 'Student number is already registered.');
        }

        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $db->beginTransaction();
        try {
            $ins = $db->prepare(
                "INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role)
                 VALUES (:u, :e, :h, :fn, :ln, :ph, 'student')"
            );
            $ins->execute([
                ':u' => $data['username'],
                ':e' => $data['email'],
                ':h' => $hash,
                ':fn' => $data['first_name'],
                ':ln' => $data['last_name'],
                ':ph' => $data['phone'],
            ]);
            $userId = $db->lastInsertId();

            $ins2 = $db->prepare(
                "INSERT INTO students (user_id, student_number, program, year_level)
                 VALUES (:id, :sn, :prog, :yr)"
            );
            $ins2->execute([
                ':id' => $userId,
                ':sn' => $data['student_number'],
                ':prog' => $data['program'],
                ':yr' => (int)$data['year_level'],
            ]);

            $db->commit();
            logAction('REGISTER', "New student registered: {$data['username']}");
            jsonResponse(true, 'Registration successful! You can now log in.');
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(false, 'Registration failed. Please try again.');
        }
        break;

    // ----------------------------------------------------------
    case 'logout':
        // ----------------------------------------------------------
        logAction('LOGOUT', "User {$_SESSION['username']} logged out.");
        $_SESSION = [];
        session_destroy();
        jsonResponse(true, 'Logged out successfully.', ['redirect' => APP_URL . '/frontend/pages/login.php']);
        break;

    default:
        jsonResponse(false, 'Invalid action.');
}
