<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('admin');
require_once __DIR__ . '/../layout.php';
$usersJsVersion = @filemtime(__DIR__ . '/../../assets/js/pages/admin/users.js') ?: time();
layout_header('User Management', 'admin');
?>
<div style="display:flex;min-height:100vh;width:100%;">
<?php layout_sidebar('admin','users'); ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><h1>User Management</h1><p>Manage students, instructors and administrators</p></div>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openModal('student')"><i class="fas fa-user-graduate"></i> Add Student</button>
      <button class="btn btn-outline" onclick="openModal('instructor')"><i class="fas fa-chalkboard-teacher"></i> Add Instructor</button>
    </div>
  </div>
  <div class="page-content">
    <div class="admin-users-grid">
      <div class="admin-users-main">
        <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
          <button class="btn btn-primary btn-sm role-btn" onclick="setRole('',this)">All Users</button>
          <button class="btn btn-outline btn-sm role-btn" onclick="setRole('student',this)">Students</button>
          <button class="btn btn-outline btn-sm role-btn" onclick="setRole('instructor',this)">Instructors</button>
          <button class="btn btn-outline btn-sm role-btn" onclick="setRole('admin',this)">Admins</button>
        </div>

        <div class="card">
          <div class="card-body" style="padding:0">
            <div id="user-list"><div style="padding:2rem;text-align:center"><span class="spinner"></span></div></div>
          </div>
        </div>
      </div>

      <aside class="admin-users-side">
        <div class="card" style="margin-bottom:1.25rem">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> User Breakdown</div></div>
          <div class="card-body">
            <div class="snapshot-row"><span>Total</span><strong id="u-total">0</strong></div>
            <div class="snapshot-row"><span>Students</span><strong id="u-students">0</strong></div>
            <div class="snapshot-row"><span>Instructors</span><strong id="u-instructors">0</strong></div>
            <div class="snapshot-row"><span>Admins</span><strong id="u-admins">0</strong></div>
            <div class="snapshot-row"><span>Active</span><strong id="u-active">0</strong></div>
            <div class="snapshot-row"><span>Inactive</span><strong id="u-inactive">0</strong></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-bolt"></i> Quick Shortcuts</div></div>
          <div class="card-body admin-users-actions">
            <a href="courses.php" class="btn btn-outline"><i class="fas fa-graduation-cap"></i> Manage Courses</a>
            <a href="enrollments.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Manage Enrollments</a>
            <a href="logs.php" class="btn btn-outline"><i class="fas fa-list"></i> Open Logs</a>
          </div>
        </div>
      </aside>
    </div>

    <link rel="stylesheet" href="<?= APP_URL ?>/frontend/assets/css/pages/admin/users.css">
  </div>
</div>
</div>

<div class="modal-overlay" id="user-modal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <div class="modal-title" id="user-modal-title">Add Student</div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="user-role-type">
      <div class="form-row">
        <div class="form-group"><label>First Name *</label>
          <input type="text" class="form-control" id="u-first"></div>
        <div class="form-group"><label>Last Name *</label>
          <input type="text" class="form-control" id="u-last"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Username *</label>
          <input type="text" class="form-control" id="u-username"></div>
        <div class="form-group"><label>Email *</label>
          <input type="email" class="form-control" id="u-email"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Phone</label>
          <input type="text" class="form-control" id="u-phone"></div>
      </div>
      <p style="font-size:.8rem;color:var(--slate);margin-top:-.2rem;margin-bottom:1rem;">
        Default password is fixed to <strong>12345</strong>. User will be required to change it on first login.
      </p>

      <div id="student-fields">
        <div class="form-row">
          <div class="form-group"><label>Student Number *</label>
            <input type="text" class="form-control" id="u-sn" placeholder="ID Number"></div>
          <div class="form-group"><label>Year Level</label>
            <select class="form-control" id="u-year">
              <option value="1">1st Year</option><option value="2">2nd Year</option>
              <option value="3">3rd Year</option><option value="4">4th Year</option>
            </select></div>
        </div>
        <div class="form-group"><label>Program</label>
          <input type="text" class="form-control" id="u-program" placeholder="BS Computer Science"></div>
      </div>

      <div id="instructor-fields" style="display:none">
        <div class="form-row">
          <div class="form-group"><label>Department *</label>
            <input type="text" class="form-control" id="u-dept" placeholder="Computer Science"></div>
          <div class="form-group"><label>Designation</label>
            <input type="text" class="form-control" id="u-desig" placeholder="Instructor I"></div>
        </div>
        <div class="form-group"><label>Office Location</label>
          <input type="text" class="form-control" id="u-office" placeholder="Room 201, Bldg A"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="saveUser()"><i class="fas fa-save"></i> Create Account</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="reset-password-modal">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-key"></i> Confirm Password Reset</div>
      <button class="modal-close" onclick="closeResetModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--slate);font-size:.9rem;line-height:1.5;margin:0;">
        Reset password for <strong id="reset-user-name">this user</strong> to default <strong>12345</strong>?
      </p>
      <p style="color:var(--slate);font-size:.82rem;line-height:1.5;margin-top:.85rem;">
        The user will be required to change password on next login.
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeResetModal()">Cancel</button>
      <button class="btn btn-primary" id="confirm-reset-btn" onclick="confirmResetPassword()">
        <i class="fas fa-key"></i> Reset Password
      </button>
    </div>
  </div>
</div>

<script>
window.CURRENT_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
</script>
<script src="<?= APP_URL ?>/frontend/assets/js/pages/admin/users.js?v=<?= $usersJsVersion ?>"></script>
<?php layout_footer(); ?>
