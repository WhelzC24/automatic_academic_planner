<?php
// ============================================================
// BISU Planner — Admin Enrollment Management
// frontend/pages/admin/enrollments.php
// ============================================================
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('admin');
require_once __DIR__ . '/../layout.php';
layout_header('Enrollment Management', 'admin');
?>
<div style="display:flex;min-height:100vh;">
<?php layout_sidebar('admin','enrollments'); ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">
      <h1>Enrollment Management</h1>
      <p>Manage student enrollments per course offering</p>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openEnrollModal()">
        <i class="fas fa-user-plus"></i> Enroll Student
      </button>
    </div>
  </div>

  <div class="page-content">
    <div class="admin-enroll-grid">
      <div class="admin-enroll-main">
        <div class="card">
          <div class="card-body">
            <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
              <div class="form-group" style="margin:0;flex:1;min-width:200px">
                <label>Filter by Course Offering</label>
                <select class="form-control" id="filter-offering" onchange="loadEnrollments()">
                  <option value="">All Offerings</option>
                </select>
              </div>
              <div class="form-group" style="margin:0;flex:1;min-width:160px">
                <label>Search Student</label>
                <input type="text" class="form-control" id="filter-search" placeholder="Name or student no." oninput="filterTable()">
              </div>
            </div>
          </div>
        </div>

        <div class="stats-grid admin-enroll-stats">
          <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="stat-info"><div class="value" id="stat-total">–</div><div class="label">Total Enrollments</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-book-open"></i></div>
            <div class="stat-info"><div class="value" id="stat-offerings">–</div><div class="label">Active Offerings</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon gold"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-info"><div class="value" id="stat-students">–</div><div class="label">Enrolled Students</div></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-list"></i> Enrollment List</div>
            <button class="btn btn-sm btn-outline" onclick="exportCSV()"><i class="fas fa-file-csv"></i> Export CSV</button>
          </div>
          <div class="card-body" style="padding:0">
            <div id="enrollment-list"><div style="padding:2rem;text-align:center"><span class="spinner"></span></div></div>
          </div>
        </div>
      </div>

      <aside class="admin-enroll-side">
        <div class="card" style="margin-bottom:1.25rem">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Enrollment Overview</div></div>
          <div class="card-body">
            <div class="snapshot-row"><span>Visible Rows</span><strong id="enr-visible">0</strong></div>
            <div class="snapshot-row"><span>All Enrollments</span><strong id="enr-total">0</strong></div>
            <div class="snapshot-row"><span>Unique Students</span><strong id="enr-students">0</strong></div>
            <div class="snapshot-row"><span>Offerings</span><strong id="enr-offerings">0</strong></div>
            <div class="snapshot-row"><span>Filter</span><strong id="enr-filter">All</strong></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-bolt"></i> Quick Shortcuts</div></div>
          <div class="card-body admin-enroll-actions">
            <button class="btn btn-outline" onclick="openEnrollModal()"><i class="fas fa-user-plus"></i> Enroll Student</button>
            <a href="users.php" class="btn btn-outline"><i class="fas fa-users"></i> Manage Users</a>
            <a href="courses.php" class="btn btn-outline"><i class="fas fa-graduation-cap"></i> Manage Courses</a>
            <a href="logs.php" class="btn btn-outline"><i class="fas fa-list"></i> Open Logs</a>
          </div>
        </div>
      </aside>
    </div>

    <link rel="stylesheet" href="<?= APP_URL ?>/frontend/assets/css/pages/admin/enrollments.css">
  </div>
</div>
</div>

<!-- Enroll Modal -->
<div class="modal-overlay" id="enroll-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Enroll Student</div>
      <button class="modal-close" onclick="closeEnroll()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Course Offering *</label>
        <select class="form-control" id="e-offering"><option value="">Select offering...</option></select>
      </div>
      <div class="form-group">
        <label>Student *</label>
        <select class="form-control" id="e-student"><option value="">Select student...</option></select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeEnroll()">Cancel</button>
      <button class="btn btn-primary" onclick="doEnroll()"><i class="fas fa-user-plus"></i> Enroll</button>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/frontend/assets/js/pages/admin/enrollments.js"></script>
<?php layout_footer(); ?>
