<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('instructor');
require_once __DIR__ . '/../layout.php';
layout_header('My Courses', 'instructor', [APP_URL . '/frontend/assets/css/pages/instructor/courses.css']);
?>
<div class="instructor-page-shell">
<?php layout_sidebar('instructor','courses'); ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><h1>My Courses</h1><p>Your assigned course offerings</p></div>
    <div class="topbar-actions">
      <a class="btn btn-outline" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a class="btn btn-primary" href="assignments.php"><i class="fas fa-clipboard-list"></i> Manage Assignments</a>
    </div>
  </div>
  <div class="page-content">
    <div class="instructor-content-grid">
      <div id="courses-grid">
        <div class="courses-loading"><span class="spinner"></span></div>
      </div>

      <aside class="instructor-side">
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Course Snapshot</div></div>
          <div class="card-body">
            <div class="snapshot-row"><span>Total Offerings</span><strong id="c-total">0</strong></div>
            <div class="snapshot-row"><span>Total Units</span><strong id="c-units">0</strong></div>
            <div class="snapshot-row"><span>Unique Sections</span><strong id="c-sections">0</strong></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div></div>
          <div class="card-body quick-links">
            <a class="btn btn-outline" href="assignments.php"><i class="fas fa-clipboard-list"></i> Manage Assignments</a>
            <a class="btn btn-outline" href="submissions.php"><i class="fas fa-inbox"></i> Check Submissions</a>
            <a class="btn btn-outline" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Back to Dashboard</a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>
</div>
<script src="<?= APP_URL ?>/frontend/assets/js/pages/instructor/courses.js"></script>
<?php layout_footer(); ?>
