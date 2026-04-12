<?php
// Root index.php — redirect to login or role dashboard
require_once __DIR__ . '/backend/config/helpers.php';
startSession();

if (!empty($_SESSION['user_id'])) {
    $redirects = [
        'student'    => APP_URL . '/frontend/pages/student/dashboard.php',
        'instructor' => APP_URL . '/frontend/pages/instructor/dashboard.php',
        'admin'      => APP_URL . '/frontend/pages/admin/dashboard.php',
    ];
    header('Location: ' . ($redirects[$_SESSION['role']] ?? APP_URL . '/frontend/pages/login.php'));
} else {
    header('Location: ' . APP_URL . '/frontend/pages/login.php');
}
exit;
