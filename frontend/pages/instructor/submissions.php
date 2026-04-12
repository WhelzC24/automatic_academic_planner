<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('instructor');
require_once __DIR__ . '/../layout.php';
layout_header('Submissions', 'instructor', [APP_URL . '/frontend/assets/css/pages/instructor/submissions.css']);
?>
<div class="instructor-page-shell">
<?php layout_sidebar('instructor','submissions'); ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><h1>Student Submissions</h1><p>Review and grade submitted assignments</p></div>
    <div class="topbar-actions">
      <a class="btn btn-outline" href="assignments.php"><i class="fas fa-clipboard-list"></i> Assignments</a>
      <a class="btn btn-primary" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    </div>
  </div>
  <div class="page-content">
    <div class="instructor-content-grid">
      <div>
        <div class="card" style="margin-bottom:1.5rem">
          <div class="card-body">
            <div class="submissions-filter-row">
              <label class="submissions-filter-label">Select Assignment:</label>
              <select class="form-control submissions-filter-select" id="asg-select" onchange="loadSubs()">
                <option value="">Loading assignments...</option>
              </select>
            </div>
          </div>
        </div>

        <div id="subs-area">
          <div class="card">
            <div class="empty-state"><i class="fas fa-inbox"></i><p>Select an assignment to view submissions.</p></div>
          </div>
        </div>
      </div>

      <aside class="instructor-side">
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Grading Snapshot</div></div>
          <div class="card-body">
            <div class="snapshot-row"><span>Total Submissions</span><strong id="sub-total">0</strong></div>
            <div class="snapshot-row"><span>Graded</span><strong id="sub-graded">0</strong></div>
            <div class="snapshot-row"><span>Ungraded</span><strong id="sub-ungraded">0</strong></div>
            <div class="snapshot-row"><span>Average Grade</span><strong id="sub-avg">—</strong></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-info-circle"></i> Grading Notes</div></div>
          <div class="card-body">
            <p style="font-size:.85rem;color:var(--slate);line-height:1.6">Select an assignment first, then use the star button to record scores and feedback for each student submission.</p>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>
</div>

<!-- Grade Modal -->
<div class="modal-overlay" id="grade-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Grade Submission</div>
      <button class="modal-close" onclick="document.getElementById('grade-modal').classList.remove('show')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="g-sub-id">
      <div id="g-student-info" style="background:var(--bg);border-radius:8px;padding:1rem;margin-bottom:1.25rem;font-weight:600;color:var(--navy)"></div>
      <div class="form-row">
        <div class="form-group">
          <label>Grade (out of <span id="g-max">100</span>)</label>
          <input type="number" class="form-control" id="g-grade" min="0">
        </div>
        <div class="form-group">
          <label>Current Status</label>
          <div id="g-status" style="padding:.7rem;background:var(--bg);border-radius:7px;font-size:.88rem"></div>
        </div>
      </div>
      <div class="form-group">
        <label>Feedback for Student</label>
        <textarea class="form-control" id="g-feedback" placeholder="Write your comments here..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="document.getElementById('grade-modal').classList.remove('show')">Cancel</button>
      <button class="btn btn-gold" onclick="saveGrade()"><i class="fas fa-star"></i> Save Grade</button>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/frontend/assets/js/pages/instructor/submissions.js"></script>
<?php layout_footer(); ?>
