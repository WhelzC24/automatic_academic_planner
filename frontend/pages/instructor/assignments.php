<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('instructor');
require_once __DIR__ . '/../layout.php';
layout_header('Assignments', 'instructor', [APP_URL . '/frontend/assets/css/pages/instructor/assignments.css']);
?>
<div class="instructor-page-shell">
<?php layout_sidebar('instructor','assignments'); ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><h1>Assignments</h1><p>Create and manage course assignments</p></div>
    <div class="topbar-actions">
      <a class="btn btn-outline" href="submissions.php"><i class="fas fa-inbox"></i> View Submissions</a>
      <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Create Assignment</button>
    </div>
  </div>
  <div class="page-content">
    <div class="instructor-content-grid">
      <div class="card">
        <div class="card-body" style="padding:0">
          <div id="asg-list"><div class="assignments-loading"><span class="spinner"></span></div></div>
        </div>
      </div>

      <aside class="instructor-side">
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Assignment Snapshot</div></div>
          <div class="card-body">
            <div class="snapshot-row"><span>Total Assignments</span><strong id="a-total">0</strong></div>
            <div class="snapshot-row"><span>Overdue</span><strong id="a-overdue">0</strong></div>
            <div class="snapshot-row"><span>With Submissions</span><strong id="a-with-subs">0</strong></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div></div>
          <div class="card-body quick-links">
            <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Create New</button>
            <a class="btn btn-outline" href="submissions.php"><i class="fas fa-inbox"></i> Grade Submissions</a>
            <a class="btn btn-outline" href="courses.php"><i class="fas fa-book-open"></i> View Courses</a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>
</div>

<!-- Create/Edit Modal -->
<div class="modal-overlay" id="asg-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Create Assignment</div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-id">
      <div class="form-group">
        <label>Course Offering *</label>
        <select class="form-control" id="a-offering"><option value="">Loading...</option></select>
      </div>
      <div class="form-group">
        <label>Title *</label>
        <input type="text" class="form-control" id="a-title" placeholder="e.g. Lab Exercise 3">
      </div>
      <div class="form-group">
        <label>Description / Instructions</label>
        <textarea class="form-control" id="a-desc" style="min-height:100px" placeholder="Describe the task, requirements, rubric..."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Due Date &amp; Time *</label>
          <input type="datetime-local" class="form-control" id="a-due">
        </div>
        <div class="form-group">
          <label>Max Score</label>
          <input type="number" class="form-control" id="a-score" value="100" min="1" max="1000">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="saveAssignment()"><i class="fas fa-save"></i> Save</button>
    </div>
  </div>
</div>

<!-- Grade Modal -->
<div class="modal-overlay" id="grade-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Grade Submission</div>
      <button class="modal-close" onclick="closeGrade()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="g-sub-id">
      <div id="g-student-info" style="background:var(--bg);border-radius:8px;padding:1rem;margin-bottom:1.25rem"></div>
      <div class="form-group">
        <label>Grade (out of <span id="g-max">100</span>)</label>
        <input type="number" class="form-control" id="g-grade" min="0">
      </div>
      <div class="form-group">
        <label>Feedback</label>
        <textarea class="form-control" id="g-feedback" placeholder="Write your feedback here..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeGrade()">Cancel</button>
      <button class="btn btn-gold" onclick="saveGrade()"><i class="fas fa-star"></i> Save Grade</button>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/frontend/assets/js/pages/instructor/assignments.js"></script>
<?php layout_footer(); ?>
