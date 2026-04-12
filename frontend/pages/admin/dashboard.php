<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('admin');
require_once __DIR__ . '/../layout.php';
layout_header('Admin Dashboard', 'admin');
?>
<div style="display:flex;min-height:100vh;">
<?php layout_sidebar('admin','dashboard'); ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><h1>Admin Dashboard</h1><p>BISU Calape Campus — System Overview</p></div>
  </div>
  <div class="page-content">
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info"><div class="value" id="st-students">–</div><div class="label">Active Students</div></div></div>
      <div class="stat-card"><div class="stat-icon gold"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-info"><div class="value" id="st-instructors">–</div><div class="label">Instructors</div></div></div>
      <div class="stat-card"><div class="stat-icon green"><i class="fas fa-book"></i></div>
        <div class="stat-info"><div class="value" id="st-courses">–</div><div class="label">Courses</div></div></div>
      <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-clipboard-check"></i></div>
        <div class="stat-info"><div class="value" id="st-asgs">–</div><div class="label">Active Assignments</div></div></div>
      <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-upload"></i></div>
        <div class="stat-info"><div class="value" id="st-subs">–</div><div class="label">Today's Submissions</div></div></div>
    </div>

    <div class="admin-dash-grid">
      <div class="admin-dash-main">
        <!-- Recent Activity -->
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-history"></i> Recent System Activity</div>
            <a href="logs.php" class="btn btn-sm btn-outline">View All Logs</a>
          </div>
          <div class="card-body" style="padding:0" id="recent-logs">
            <div style="padding:2rem;text-align:center"><span class="spinner"></span></div>
          </div>
        </div>
      </div>

      <aside class="admin-dash-side">
        <div class="card" style="margin-bottom:1.25rem">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div>
          </div>
          <div class="card-body admin-quick-actions">
            <a href="users.php" class="btn btn-outline"><i class="fas fa-users"></i> Manage Users</a>
            <a href="courses.php" class="btn btn-outline"><i class="fas fa-graduation-cap"></i> Manage Courses</a>
            <a href="enrollments.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Manage Enrollments</a>
            <a href="logs.php" class="btn btn-outline"><i class="fas fa-list"></i> Open Logs</a>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-chart-pie"></i> System Snapshot</div>
          </div>
          <div class="card-body" id="system-snapshot">
            <div class="snapshot-row"><span>Active Students</span><strong id="snap-students">–</strong></div>
            <div class="snapshot-row"><span>Instructors</span><strong id="snap-instructors">–</strong></div>
            <div class="snapshot-row"><span>Courses</span><strong id="snap-courses">–</strong></div>
            <div class="snapshot-row"><span>Active Assignments</span><strong id="snap-asgs">–</strong></div>
            <div class="snapshot-row"><span>Today's Submissions</span><strong id="snap-subs">–</strong></div>
          </div>
        </div>
      </aside>
    </div>

    <link rel="stylesheet" href="<?= APP_URL ?>/frontend/assets/css/pages/admin/dashboard.css">
  </div>
</div>
</div>
<script src="<?= APP_URL ?>/frontend/assets/js/pages/admin/dashboard.js"></script>
<?php layout_footer(); ?>
