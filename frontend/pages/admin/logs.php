<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('admin');
require_once __DIR__ . '/../layout.php';
layout_header('System Logs', 'admin');
?>
<div style="display:flex;min-height:100vh;">
<?php layout_sidebar('admin','logs'); ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><h1>System Logs</h1><p>Monitor all system activity</p></div>
    <div class="topbar-actions">
      <select class="form-control" id="limit-sel" onchange="loadLogs()" style="width:auto;padding:.5rem .8rem">
        <option value="50">Last 50</option>
        <option value="100">Last 100</option>
        <option value="200">Last 200</option>
      </select>
    </div>
  </div>
  <div class="page-content">
    <div class="admin-logs-grid">
      <div class="admin-logs-main">
        <div class="card">
          <div class="card-body" style="padding:0" id="logs-list">
            <div style="padding:2rem;text-align:center"><span class="spinner"></span></div>
          </div>
        </div>
      </div>

      <aside class="admin-logs-side">
        <div class="card" style="margin-bottom:1.25rem">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-line"></i> Log Snapshot</div></div>
          <div class="card-body">
            <div class="snapshot-row"><span>Total Loaded</span><strong id="log-total">0</strong></div>
            <div class="snapshot-row"><span>Login Success</span><strong id="log-success">0</strong></div>
            <div class="snapshot-row"><span>Login Failed</span><strong id="log-failed">0</strong></div>
            <div class="snapshot-row"><span>User Changes</span><strong id="log-user-actions">0</strong></div>
            <div class="snapshot-row"><span>Data Window</span><strong id="log-window">Last 50</strong></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-bolt"></i> Quick Shortcuts</div></div>
          <div class="card-body admin-logs-actions">
            <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php" class="btn btn-outline"><i class="fas fa-users"></i> User Management</a>
            <a href="courses.php" class="btn btn-outline"><i class="fas fa-graduation-cap"></i> Course Management</a>
          </div>
        </div>
      </aside>
    </div>

    <link rel="stylesheet" href="<?= APP_URL ?>/frontend/assets/css/pages/admin/logs.css">
  </div>
</div>
</div>
<script src="<?= APP_URL ?>/frontend/assets/js/pages/admin/logs.js"></script>
<?php layout_footer(); ?>
