#!/usr/bin/env php
<?php
/**
 * BISU Planner — Automated Task Scheduler (Cron Job)
 * backend/cron/scheduler.php
 *
 * Run this script every hour via cron:
 *   0 * * * * php /var/www/html/bisu_planner/backend/cron/scheduler.php >> /var/log/bisu_cron.log 2>&1
 *
 * On Windows (XAMPP Task Scheduler):
 *   Program: C:\xampp\php\php.exe
 *   Arguments: C:\xampp\htdocs\bisu_planner\backend\cron\scheduler.php
 *   Schedule: Every 1 hour
 */

// Bootstrap without HTTP context
define('CRON_MODE', true);
require_once __DIR__ . '/../config/database.php';

$db   = getDB();
$now  = date('Y-m-d H:i:s');
$log  = [];

echo "\n[" . $now . "] BISU Planner Cron Scheduler Running...\n";

// ──────────────────────────────────────────────────────────
// 1. AUTO-MARK OVERDUE TASKS
// ──────────────────────────────────────────────────────────
$stmt = $db->exec(
    "UPDATE tasks SET status = 'Overdue'
     WHERE due_at < NOW() AND status NOT IN ('Completed','Overdue')"
);
echo "[TASK] Marked overdue tasks: affected rows = $stmt\n";

// ──────────────────────────────────────────────────────────
// 2. DEADLINE REMINDER NOTIFICATIONS
// ──────────────────────────────────────────────────────────
$intervals = [
    '3 days'   => 'DATE(due_at) = DATE(NOW() + INTERVAL 3 DAY)',
    '1 day'    => 'DATE(due_at) = DATE(NOW() + INTERVAL 1 DAY)',
    'today'    => 'DATE(due_at) = CURDATE()',
];

foreach ($intervals as $label => $condition) {
    $sql = "
        INSERT IGNORE INTO notifications (user_id, assignment_id, type, message)
        SELECT e.student_id, a.assignment_id, 'Deadline Reminder',
               CONCAT('Deadline Reminder: \"', a.title, '\" is due on ',
                      DATE_FORMAT(a.due_at,'%M %d, %Y at %h:%i %p'))
        FROM assignments a
        JOIN course_offerings co ON co.offering_id = a.offering_id
        JOIN enrollments e ON e.offering_id = co.offering_id
        WHERE $condition
          AND NOT EXISTS (
              SELECT 1 FROM notifications n
              WHERE n.user_id = e.student_id
                AND n.assignment_id = a.assignment_id
                AND DATE(n.sent_at) = CURDATE()
                AND n.type = 'Deadline Reminder'
          )
    ";
    $count = $db->exec($sql);
    echo "[NOTIF] Deadline reminders ($label): sent = $count\n";
}

// ──────────────────────────────────────────────────────────
// 3. SCHEDULE REMINDERS (1 hour before)
// ──────────────────────────────────────────────────────────
$schedRem = $db->exec("
    INSERT IGNORE INTO notifications (user_id, schedule_id, type, message)
    SELECT s.user_id, s.schedule_id, 'Schedule Reminder',
           CONCAT('Reminder: \"', s.title, '\" starts in 1 hour at ',
                  DATE_FORMAT(s.starts_at, '%h:%i %p'))
    FROM schedules s
    WHERE s.starts_at BETWEEN NOW() + INTERVAL 50 MINUTE
                          AND NOW() + INTERVAL 70 MINUTE
      AND NOT EXISTS (
          SELECT 1 FROM notifications n
          WHERE n.user_id = s.user_id
            AND n.schedule_id = s.schedule_id
            AND DATE(n.sent_at) = CURDATE()
            AND n.type = 'Schedule Reminder'
      )
");
echo "[NOTIF] Schedule reminders (1hr): sent = $schedRem\n";

// ──────────────────────────────────────────────────────────
// 4. LOG CRON EXECUTION
// ──────────────────────────────────────────────────────────
$db->prepare(
    "INSERT INTO system_logs (user_id, action, description, ip_address)
     VALUES (NULL, 'CRON_RUN', :desc, 'cron')"
)->execute([':desc' => "Cron completed at $now"]);

echo "[DONE] Cron job completed at " . date('Y-m-d H:i:s') . "\n";
