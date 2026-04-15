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
// 1. AUTO-GENERATE TASKS FROM ASSIGNMENTS
// ──────────────────────────────────────────────────────────
$generatedTasks = $db->exec(
    "INSERT INTO tasks (user_id, assignment_id, task_name, description, due_at, priority, status)
     SELECT e.student_id,
            a.assignment_id,
            CONCAT('Submit: ', a.title),
            a.description,
            a.due_at,
            CASE
                WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 24 THEN 'Urgent'
                WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 72 THEN 'High'
                WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 168 THEN 'Medium'
                ELSE 'Low'
            END,
            CASE WHEN a.due_at < NOW() THEN 'Overdue' ELSE 'Pending' END
     FROM assignments a
     JOIN course_offerings co ON co.offering_id = a.offering_id
     JOIN enrollments e ON e.offering_id = co.offering_id
     LEFT JOIN tasks t ON t.user_id = e.student_id AND t.assignment_id = a.assignment_id
     WHERE t.task_id IS NULL"
);
echo "[TASK] Auto-generated assignment tasks: created = $generatedTasks\n";

// ──────────────────────────────────────────────────────────
// 2. SYNC LINKED TASK DETAILS WITH ASSIGNMENTS
// ──────────────────────────────────────────────────────────
$syncedTasks = $db->exec(
    "UPDATE tasks t
     JOIN assignments a ON a.assignment_id = t.assignment_id
     SET t.task_name = CONCAT('Submit: ', a.title),
         t.description = a.description,
         t.due_at = a.due_at,
         t.priority = CASE
             WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 24 THEN 'Urgent'
             WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 72 THEN 'High'
             WHEN TIMESTAMPDIFF(HOUR, NOW(), a.due_at) <= 168 THEN 'Medium'
             ELSE 'Low'
         END,
         t.status = CASE
             WHEN t.status = 'Completed' THEN t.status
             WHEN a.due_at < NOW() THEN 'Overdue'
             WHEN t.status = 'Overdue' AND a.due_at >= NOW() THEN 'Pending'
             ELSE t.status
         END
     WHERE t.assignment_id IS NOT NULL"
);
echo "[TASK] Synced linked assignment tasks: updated = $syncedTasks\n";

// ──────────────────────────────────────────────────────────
// 3. AUTO-MARK OVERDUE TASKS
// ──────────────────────────────────────────────────────────
$stmt = $db->exec(
    "UPDATE tasks SET status = 'Overdue'
     WHERE due_at < NOW() AND status NOT IN ('Completed','Overdue')"
);
echo "[TASK] Marked overdue tasks: affected rows = $stmt\n";

// ──────────────────────────────────────────────────────────
// 4. DEADLINE REMINDER NOTIFICATIONS
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
// 5. SCHEDULE REMINDERS (1 hour before)
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
// 6. LOG CRON EXECUTION
// ──────────────────────────────────────────────────────────
$db->prepare(
    "INSERT INTO system_logs (user_id, action, description, ip_address)
     VALUES (NULL, 'CRON_RUN', :desc, 'cron')"
)->execute([':desc' => "Cron completed at $now"]);

echo "[DONE] Cron job completed at " . date('Y-m-d H:i:s') . "\n";
