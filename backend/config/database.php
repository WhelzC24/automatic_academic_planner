<?php
// ============================================================
// BISU Planner - Database Configuration
// backend/config/database.php
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'bisu_planner');
define('DB_USER', 'root');          // Change to your MySQL username
define('DB_PASS', '2005');              // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'BISU Academic Planner');
define('APP_URL', 'http://localhost/bisu_planner');
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('SESSION_LIFETIME', 3600);  // 1 hour

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'We can’t sign you in right now. Please try again in a moment.']));
        }
    }
    return $pdo;
}
