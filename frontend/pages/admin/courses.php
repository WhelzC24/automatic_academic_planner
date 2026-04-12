<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('admin');
require_once __DIR__ . '/../layout.php';
layout_header('Course Management', 'admin');
?>
<div style="display:flex;min-height:100vh;">
<?php layout_sidebar('admin','courses'); ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><h1>Course Management</h1><p>Manage courses and offerings</p></div>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openCourseModal()"><i class="fas fa-plus"></i> Add Course</button>
      <button class="btn btn-outline" onclick="openOfferingModal()"><i class="fas fa-plus"></i> Add Offering</button>
    </div>
  </div>

  <div class="page-content">
    <div class="admin-courses-grid">
      <div class="admin-courses-main">
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-book"></i> All Courses</div>
          </div>
          <div class="card-body" style="padding:0" id="courses-list">
            <div style="padding:2rem;text-align:center"><span class="spinner"></span></div>
          </div>
        </div>
      </div>

      <aside class="admin-courses-side">
        <div class="card" style="margin-bottom:1.25rem">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Course Snapshot</div></div>
          <div class="card-body">
            <div class="snapshot-row"><span>Total Courses</span><strong id="c-total">0</strong></div>
            <div class="snapshot-row"><span>Total Offerings</span><strong id="c-offerings">0</strong></div>
            <div class="snapshot-row"><span>Multi-Section</span><strong id="c-multi">0</strong></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-bolt"></i> Quick Shortcuts</div></div>
          <div class="card-body admin-courses-actions">
            <button class="btn btn-outline" onclick="openCourseModal()"><i class="fas fa-plus"></i> Add Course</button>
            <button class="btn btn-outline" onclick="openOfferingModal()"><i class="fas fa-layer-group"></i> Add Offering</button>
            <a href="users.php" class="btn btn-outline"><i class="fas fa-users"></i> Manage Users</a>
            <a href="enrollments.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Manage Enrollments</a>
          </div>
        </div>
      </aside>
    </div>

    <link rel="stylesheet" href="<?= APP_URL ?>/frontend/assets/css/pages/admin/courses.css">
  </div>
</div>
</div>

<div class="modal-overlay" id="course-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Course</div>
      <button class="modal-close" onclick="document.getElementById('course-modal').classList.remove('show')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group"><label>Course Code *</label>
          <input type="text" class="form-control" id="c-code" placeholder="CS101"></div>
        <div class="form-group"><label>Units</label>
          <input type="number" class="form-control" id="c-units" value="3" min="1" max="6"></div>
      </div>
      <div class="form-group"><label>Course Title *</label>
        <input type="text" class="form-control" id="c-title" placeholder="Introduction to Computing"></div>
      <div class="form-group"><label>Description</label>
        <textarea class="form-control" id="c-desc" placeholder="Course description..."></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="document.getElementById('course-modal').classList.remove('show')">Cancel</button>
      <button class="btn btn-primary" onclick="saveCourse()"><i class="fas fa-save"></i> Save</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="offering-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Course Offering</div>
      <button class="modal-close" onclick="document.getElementById('offering-modal').classList.remove('show')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-group"><label>Course *</label>
        <select class="form-control" id="o-course"><option value="">Select course...</option></select></div>
      <div class="form-row">
        <div class="form-group"><label>Term *</label>
          <input type="text" class="form-control" id="o-term" placeholder="1st Sem AY 2024-2025"></div>
        <div class="form-group"><label>Section *</label>
          <input type="text" class="form-control" id="o-section" placeholder="BSCS-2A"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Schedule</label>
          <input type="text" class="form-control" id="o-schedule" placeholder="MWF 8:00-9:00 AM"></div>
        <div class="form-group"><label>Room</label>
          <input type="text" class="form-control" id="o-room" placeholder="ICT Lab 1"></div>
      </div>
      <div class="form-group"><label>Assign Instructor</label>
        <select class="form-control" id="o-instructor"><option value="">None / Assign later</option></select></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="document.getElementById('offering-modal').classList.remove('show')">Cancel</button>
      <button class="btn btn-primary" onclick="saveOffering()"><i class="fas fa-save"></i> Save</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="enroll-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Enroll Student</div>
      <button class="modal-close" onclick="document.getElementById('enroll-modal').classList.remove('show')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="enroll-offering-id">
      <div class="form-group"><label>Select Student *</label>
        <select class="form-control" id="enroll-student"><option value="">Loading...</option></select></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="document.getElementById('enroll-modal').classList.remove('show')">Cancel</button>
      <button class="btn btn-primary" onclick="enrollStudent()"><i class="fas fa-user-plus"></i> Enroll</button>
    </div>
  </div>
</div>

<script>
const API = BASE_URL + '/backend/admin/admin_api.php';
let allStudents = [];

async function loadCourses() {
  const res = await fetch(API + '?action=get_courses');
  const data = await res.json();
  const courses = data.courses || [];
  const el = document.getElementById('courses-list');
  const totalOfferings = courses.reduce((sum, c) => sum + Number(c.offering_count || 0), 0);
  const multiSection = courses.filter(c => Number(c.offering_count || 0) > 1).length;

  document.getElementById('c-total').textContent = courses.length;
  document.getElementById('c-offerings').textContent = totalOfferings;
  document.getElementById('c-multi').textContent = multiSection;

  if (!courses.length) {
    el.innerHTML = '<div class="empty-state"><i class="fas fa-book"></i><p>No courses yet.</p></div>';
    return;
  }

  el.innerHTML = `<table class="courses-table"><thead><tr>
    <th>Code</th><th>Title</th><th>Units</th><th>Offerings</th><th>Description</th>
  </tr></thead><tbody>
  ${courses.map(c => `<tr>
    <td data-label="Code"><strong>${c.code}</strong></td>
    <td data-label="Title">${c.title}</td>
    <td data-label="Units" style="text-align:center">${c.units}</td>
    <td data-label="Offerings" style="text-align:center">
      <span class="badge badge-submitted">${c.offering_count} offering${c.offering_count != 1 ? 's' : ''}</span>
    </td>
    <td data-label="Description" style="color:var(--slate);font-size:.82rem">${c.description ? c.description.substring(0,80) + '...' : '—'}</td>
  </tr>`).join('')}
  </tbody></table>`;
}

async function loadInstructorsForSelect() {
  const res = await fetch(API + '?action=get_users&role=instructor');
  const data = await res.json();
  const sel = document.getElementById('o-instructor');
  sel.innerHTML = '<option value="">None / Assign later</option>' +
    (data.users || []).map(u => `<option value="${u.user_id}">${u.first_name} ${u.last_name} (${u.extra_info || ''})</option>`).join('');
}

async function loadStudentsForEnroll() {
  const res = await fetch(API + '?action=get_users&role=student');
  const data = await res.json();
  allStudents = data.users || [];
}

async function loadCoursesForSelect() {
  const res = await fetch(API + '?action=get_courses');
  const data = await res.json();
  const sel = document.getElementById('o-course');
  sel.innerHTML = '<option value="">Select course...</option>' +
    (data.courses || []).map(c => `<option value="${c.course_id}">${c.code} - ${c.title}</option>`).join('');
}

function openCourseModal() {
  ['c-code', 'c-title', 'c-desc'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('c-units').value = 3;
  document.getElementById('course-modal').classList.add('show');
}

async function openOfferingModal() {
  await loadCoursesForSelect();
  await loadInstructorsForSelect();
  ['o-term', 'o-section', 'o-schedule', 'o-room'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('offering-modal').classList.add('show');
}

async function openEnrollModal(offeringId) {
  document.getElementById('enroll-offering-id').value = offeringId;
  await loadStudentsForEnroll();
  const sel = document.getElementById('enroll-student');
  sel.innerHTML = '<option value="">Select student...</option>' +
    allStudents.map(s => `<option value="${s.user_id}">${s.first_name} ${s.last_name} - ${s.student_number || ''}</option>`).join('');
  document.getElementById('enroll-modal').classList.add('show');
}

async function saveCourse() {
  const fd = new FormData();
  fd.append('action', 'add_course');
  fd.append('code', document.getElementById('c-code').value.trim());
  fd.append('title', document.getElementById('c-title').value.trim());
  fd.append('description', document.getElementById('c-desc').value.trim());
  fd.append('units', document.getElementById('c-units').value);
  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast(data.message);
    document.getElementById('course-modal').classList.remove('show');
    loadCourses();
  } else toast(data.message, 'error');
}

async function saveOffering() {
  const fd = new FormData();
  fd.append('action', 'add_offering');
  fd.append('course_id', document.getElementById('o-course').value);
  fd.append('term', document.getElementById('o-term').value.trim());
  fd.append('section', document.getElementById('o-section').value.trim());
  fd.append('schedule', document.getElementById('o-schedule').value.trim());
  fd.append('room', document.getElementById('o-room').value.trim());
  fd.append('instructor_id', document.getElementById('o-instructor').value);
  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast(data.message);
    document.getElementById('offering-modal').classList.remove('show');
    loadCourses();
  } else toast(data.message, 'error');
}

async function enrollStudent() {
  const fd = new FormData();
  fd.append('action', 'enroll_student');
  fd.append('offering_id', document.getElementById('enroll-offering-id').value);
  fd.append('student_id', document.getElementById('enroll-student').value);
  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast(data.message);
    document.getElementById('enroll-modal').classList.remove('show');
  } else toast(data.message, 'error');
}

loadCourses();
</script>
<?php layout_footer(); ?>
