<?php
// ============================================================
// BISU Planner - Session & Helper Utilities
// backend/config/helpers.php
// ============================================================

require_once __DIR__ . '/database.php';

// Start session securely
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

// Require authenticated user; redirect if not
function requireAuth(string ...$roles): void
{
    startSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/frontend/pages/login.php');
        exit;
    }
    if (!empty($roles) && !in_array($_SESSION['role'], $roles, true)) {
        header('Location: ' . APP_URL . '/frontend/pages/unauthorized.php');
        exit;
    }
}

// JSON response helper
function jsonResponse(bool $success, string $message, array $data = []): void
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// Sanitize output
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Log system action
function logAction(string $action, string $description = ''): void
{
    try {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO system_logs (user_id, action, description, ip_address)
             VALUES (:uid, :action, :desc, :ip)"
        );
        $stmt->execute([
            ':uid'    => $_SESSION['user_id'] ?? null,
            ':action' => $action,
            ':desc'   => $description,
            ':ip'     => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    } catch (Exception $e) {
        // Non-fatal
    }
}

// Get current user full info
function getCurrentUser(): ?array
{
    startSession();
    if (empty($_SESSION['user_id'])) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// Auto-mark overdue tasks
function autoMarkOverdue(): void
{
    $db = getDB();
    $db->exec(
        "UPDATE tasks SET status = 'Overdue'
         WHERE due_at < NOW() AND status NOT IN ('Completed','Overdue')"
    );
}

// Generate deadline reminder notifications
function generateDeadlineNotifications(): void
{
    $db = getDB();

    // Assignments due in 3 days, 1 day, or today — avoid duplicates
    $intervals = ['3 DAY', '1 DAY', '0 DAY'];
    foreach ($intervals as $interval) {
        $window = ($interval === '0 DAY') ? 'DATE(due_at) = CURDATE()' : "DATE(due_at) = DATE(NOW() + INTERVAL $interval)";
        $sql = "
            INSERT IGNORE INTO notifications (user_id, assignment_id, type, message)
            SELECT e.student_id, a.assignment_id, 'Deadline Reminder',
                   CONCAT('Deadline Reminder: \"', a.title, '\" is due on ', DATE_FORMAT(a.due_at,'%M %d, %Y %h:%i %p'))
            FROM assignments a
            JOIN course_offerings co ON co.offering_id = a.offering_id
            JOIN enrollments e ON e.offering_id = co.offering_id
            WHERE $window
              AND NOT EXISTS (
                  SELECT 1 FROM notifications n
                  WHERE n.user_id = e.student_id
                    AND n.assignment_id = a.assignment_id
                    AND DATE(n.sent_at) = CURDATE()
                    AND n.type = 'Deadline Reminder'
              )
        ";
        $db->exec($sql);
    }
}


// Ensure users table supports forced password change flow
function ensureForcePasswordChangeColumn(PDO $db): void
{
    static $checked = false;
    if ($checked) return;

    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'must_change_password'");
    $exists = $stmt && $stmt->fetch();

    if (!$exists) {
        $db->exec("ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");
    }

    $checked = true;
}
