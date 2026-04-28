<?php
// backend/config/helpers.php must be required before including this
// Usage: include layout_header('Student Dashboard', 'student');
function layout_header(string $title, string $role, array $extraCss = [], array $extraHeadScripts = []): void
{ ?>
  <!DOCTYPE html>
  <html lang="en" data-role="<?= htmlspecialchars($role) ?>">

  <head>
    <?php $layoutCssVersion = @filemtime(__DIR__ . '/../assets/css/pages/layout.css') ?: time(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — BISU Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/frontend/assets/css/pages/layout.css?v=<?= $layoutCssVersion ?>">
    <script>
      const BASE_URL = '<?= APP_URL ?>';
    </script>
    <script src="<?= APP_URL ?>/frontend/assets/js/pages/layout.js"></script>
    <?php foreach ($extraCss as $css): ?>
      <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>
    <?php foreach ($extraHeadScripts as $script): ?>
      <script src="<?= htmlspecialchars($script) ?>"></script>
    <?php endforeach; ?>
  <?php } // end layout_header

function layout_sidebar(string $role, string $activePage = ''): void
{
  $name    = $_SESSION['full_name'] ?? 'User';
  $initial = strtoupper(substr($name, 0, 1));
  $navItems = [];

  if ($role === 'student') {
    $navItems = [
      ['icon' => 'tachometer-alt', 'label' => 'Dashboard',    'page' => 'dashboard',    'url' => APP_URL . '/frontend/pages/student/dashboard.php'],
      ['icon' => 'calendar-alt',   'label' => 'Schedule',     'page' => 'schedule',     'url' => APP_URL . '/frontend/pages/student/schedule.php'],
      ['icon' => 'file-alt',      'label' => 'Coursework',   'page' => 'coursework',  'url' => APP_URL . '/frontend/pages/student/coursework.php'],
      ['icon' => 'bell',          'label' => 'Notifications', 'page' => 'notifications', 'url' => APP_URL . '/frontend/pages/student/notifications.php'],
      ['icon' => 'user-circle',   'label' => 'My Profile',   'page' => 'profile',      'url' => APP_URL . '/frontend/pages/profile.php'],
    ];
  } elseif ($role === 'instructor') {
$navItems = [
      ['icon' => 'tachometer-alt', 'label' => 'Dashboard',  'page' => 'dashboard',  'url' => APP_URL . '/frontend/pages/instructor/dashboard.php'],
      ['icon' => 'book-open',      'label' => 'My Courses', 'page' => 'courses',    'url' => APP_URL . '/frontend/pages/instructor/courses.php'],
      ['icon' => 'calendar-alt',   'label' => 'Schedules',  'page' => 'schedules',  'url' => APP_URL . '/frontend/pages/instructor/schedules.php'],
      ['icon' => 'clipboard-list', 'label' => 'Assignments', 'page' => 'assignments', 'url' => APP_URL . '/frontend/pages/instructor/assignments.php'],
      ['icon' => 'upload',         'label' => 'Submissions', 'page' => 'submissions', 'url' => APP_URL . '/frontend/pages/instructor/submissions.php'],
      ['icon' => 'bell',           'label' => 'Notifications', 'page' => 'notifications', 'url' => APP_URL . '/frontend/pages/instructor/notifications.php'],
      ['icon' => 'user-circle',   'label' => 'My Profile', 'page' => 'profile',    'url' => APP_URL . '/frontend/pages/profile.php'],
    ];
  } else {
    $navItems = [
      ['icon' => 'tachometer-alt', 'label' => 'Dashboard',   'page' => 'dashboard',   'url' => APP_URL . '/frontend/pages/admin/dashboard.php'],
      ['icon' => 'users',          'label' => 'Users',        'page' => 'users',       'url' => APP_URL . '/frontend/pages/admin/users.php'],
      ['icon' => 'graduation-cap', 'label' => 'Courses',      'page' => 'courses',     'url' => APP_URL . '/frontend/pages/admin/courses.php'],
      ['icon' => 'user-plus',      'label' => 'Enrollments',  'page' => 'enrollments', 'url' => APP_URL . '/frontend/pages/admin/enrollments.php'],
      ['icon' => 'list',           'label' => 'System Logs',  'page' => 'logs',        'url' => APP_URL . '/frontend/pages/admin/logs.php'],
      ['icon' => 'user-circle',    'label' => 'My Profile',   'page' => 'profile',     'url' => APP_URL . '/frontend/pages/profile.php'],
    ];
  }
  ?>
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-brand">
        <div class="logo">
          <div class="logo-icon">
            <img src="<?= APP_URL ?>/frontend/img/bisu_logo.png" alt="BISU Logo">
          </div>
          <div class="logo-text">
            <h3>BISU Planner</h3>
            <p>Calape Campus</p>
          </div>
        </div>
      </div>
      <div class="sidebar-user">
        <div class="user-info">
          <div class="user-avatar"><?= $initial ?></div>
          <div>
            <div class="name"><?= htmlspecialchars($name) ?></div>
            <div class="role-badge"><?= ucfirst($role) ?></div>
          </div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section-label">Main Menu</div>
        <?php foreach ($navItems as $item): ?>
          <a href="<?= $item['url'] ?>" class="nav-item <?= ($activePage === $item['page']) ? 'active' : '' ?>">
            <i class="fas fa-<?= $item['icon'] ?>"></i>
            <?= $item['label'] ?>
            <?php if ($item['page'] === 'notifications'): ?>
              <span class="badge" id="nav-notif-count" style="display:none">0</span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="sidebar-footer">
        <button class="btn-logout" onclick="doLogout()">
          <i class="fas fa-sign-out-alt"></i> Sign Out
        </button>
      </div>
    </aside>
  <?php
}

function layout_footer(): void
{ ?>
    <div class="toast-container" id="toast-container"></div>
    <script>
      const ROLE = '<?= $role ?? '' ?>';
    </script>
    <script src="<?= APP_URL ?>/frontend/assets/js/pages/layout.js"></script>
  </body>

</html>
<?php }
