<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('instructor');
require_once __DIR__ . '/../layout.php';
layout_header('Instructor Dashboard', 'instructor', [APP_URL . '/frontend/assets/css/pages/instructor/dashboard.css']);
?>
<div class="instructor-page-shell">
  <?php layout_sidebar('instructor', 'dashboard'); ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1>Instructor Dashboard</h1>
        <p id="curr-date"></p>
      </div>
      <div class="topbar-actions">
        <a class="btn btn-outline" href="courses.php"><i class="fas fa-book-open"></i> My Courses</a>
        <a class="btn btn-outline" href="schedules.php"><i class="fas fa-calendar-alt"></i> Schedules</a>
        <button class="notif-btn" id="notif-btn" onclick="location.href='notifications.php'">
          <i class="fas fa-bell"></i>
          <span class="notif-dot" id="notif-dot">0</span>
        </button>
        <a class="btn btn-primary" href="assignments.php"><i class="fas fa-plus"></i> New Assignment</a>
      </div>
    </div>
    <div class="page-content">
      <div class="instructor-content-grid">
        <div>
          <div class="stats-grid" id="stats-row">
            <div class="stat-card">
              <div class="stat-icon blue"><i class="fas fa-book-open"></i></div>
              <div class="stat-info">
                <div class="value" id="s-courses">–</div>
                <div class="label">My Courses</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon orange"><i class="fas fa-clipboard-list"></i></div>
              <div class="stat-info">
                <div class="value" id="s-assignments">–</div>
                <div class="label">Assignments</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon green"><i class="fas fa-upload"></i></div>
              <div class="stat-info">
                <div class="value" id="s-subs">–</div>
                <div class="label">Recent Submissions</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon gold"><i class="fas fa-users"></i></div>
              <div class="stat-info">
                <div class="value" id="s-students">–</div>
                <div class="label">Total Students</div>
              </div>
            </div>
          </div>

          <div class="instructor-dashboard-panels">
            <div class="card">
              <div class="card-header">
                <div class="card-title"><i class="fas fa-chalkboard-teacher"></i> My Course Offerings</div>
              </div>
              <div class="card-body" style="padding:0" id="offerings-list">
                <div class="panel-loading"><span class="spinner"></span></div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <div class="card-title"><i class="fas fa-inbox"></i> Recent Submissions</div>
                <a href="submissions.php" class="btn btn-sm btn-outline">View All</a>
              </div>
              <div class="card-body" style="padding:0" id="submissions-list">
                <div class="panel-loading"><span class="spinner"></span></div>
              </div>
            </div>
          </div>
        </div>

        <aside class="instructor-side">
          <div class="card">
            <div class="card-header">
              <div class="card-title"><i class="fas fa-chart-pie"></i> Teaching Snapshot</div>
            </div>
            <div class="card-body">
              <div class="snapshot-row"><span>Active Courses</span><strong id="d-courses">0</strong></div>
              <div class="snapshot-row"><span>Assignments</span><strong id="d-assignments">0</strong></div>
              <div class="snapshot-row"><span>Recent Submissions</span><strong id="d-subs">0</strong></div>
              <div class="snapshot-row"><span>Total Students</span><strong id="d-students">0</strong></div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div>
            </div>
            <div class="card-body quick-links">
              <a class="btn btn-primary" href="assignments.php"><i class="fas fa-plus"></i> Create Assignment</a>
              <a class="btn btn-outline" href="courses.php"><i class="fas fa-book-open"></i> View Courses</a>
              <a class="btn btn-outline" href="submissions.php"><i class="fas fa-inbox"></i> Review Submissions</a>
              <a class="btn btn-outline" href="../profile.php"><i class="fas fa-user-circle"></i> Update Profile</a>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</div>
<script src="<?= APP_URL ?>/frontend/assets/js/pages/instructor/dashboard.js"></script>
<?php layout_footer(); ?>