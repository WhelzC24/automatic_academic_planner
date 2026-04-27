<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('admin');
require_once __DIR__ . '/../layout.php';
layout_header('Course Management', 'admin');
?>
<div style="display:flex;min-height:100vh;">
  <?php layout_sidebar('admin', 'courses'); ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1>Course Management</h1>
        <p>Manage courses and offerings</p>
      </div>
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

          <div class="card" style="margin-top:1.25rem">
            <div class="card-header">
              <div class="card-title"><i class="fas fa-layer-group"></i> Course Offerings</div>
            </div>
            <div class="card-body" style="padding:0" id="offerings-list">
              <div style="padding:2rem;text-align:center"><span class="spinner"></span></div>
            </div>
          </div>
        </div>

        <aside class="admin-courses-side">
          <div class="card" style="margin-bottom:1.25rem">
            <div class="card-header">
              <div class="card-title"><i class="fas fa-chart-pie"></i> Course Snapshot</div>
            </div>
            <div class="card-body">
              <div class="snapshot-row"><span>Total Courses</span><strong id="c-total">0</strong></div>
              <div class="snapshot-row"><span>Total Offerings</span><strong id="c-offerings">0</strong></div>
              <div class="snapshot-row"><span>Multi-Section</span><strong id="c-multi">0</strong></div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <div class="card-title"><i class="fas fa-bolt"></i> Quick Shortcuts</div>
            </div>
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
          <input type="text" class="form-control" id="c-code" placeholder="CS101">
        </div>
        <div class="form-group"><label>Units</label>
          <input type="number" class="form-control" id="c-units" value="3" min="1" max="6">
        </div>
      </div>
      <div class="form-group"><label>Course Title *</label>
        <input type="text" class="form-control" id="c-title" placeholder="Introduction to Computing">
      </div>
      <div class="form-group"><label>Description</label>
        <textarea class="form-control" id="c-desc" placeholder="Course description..."></textarea>
      </div>
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
        <select class="form-control" id="o-course">
          <option value="">Select course...</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Semester *</label>
          <select class="form-control" id="o-semester">
            <option value="">Select semester...</option>
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
            <option value="Summer">Summer</option>
          </select>
        </div>
        <div class="form-group"><label>Academic Year *</label>
          <input type="text" class="form-control" id="o-academic-year" placeholder="2025-2026">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Section *</label>
          <input type="text" class="form-control" id="o-section" placeholder="BSCS-2A">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Schedule</label>
          <input type="text" class="form-control" id="o-schedule" placeholder="MWF 8:00-9:00 AM">
        </div>
        <div class="form-group"><label>Room</label>
          <input type="text" class="form-control" id="o-room" placeholder="ICT Lab 1">
        </div>
      </div>
      <div class="form-group"><label>Assign Instructor</label>
        <select class="form-control" id="o-instructor">
          <option value="">None / Assign later</option>
        </select>
      </div>
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
        <select class="form-control" id="enroll-student">
          <option value="">Loading...</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="document.getElementById('enroll-modal').classList.remove('show')">Cancel</button>
      <button class="btn btn-primary" onclick="enrollStudent()"><i class="fas fa-user-plus"></i> Enroll</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="delete-offering-modal">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-trash"></i> Confirm Offering Deletion</div>
      <button class="modal-close" onclick="closeDeleteOfferingModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--slate);font-size:.9rem;line-height:1.5;margin:0;">
        Delete offering <strong id="delete-offering-name">this offering</strong>?
      </p>
      <p style="color:var(--slate);font-size:.82rem;line-height:1.5;margin-top:.85rem;">
        This will also remove all enrollments linked to this offering.
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeDeleteOfferingModal()">Cancel</button>
      <button class="btn btn-danger" id="confirm-delete-offering-btn" onclick="confirmDeleteOffering()">
        <i class="fas fa-trash"></i> Delete Offering
      </button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="delete-course-modal">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-trash"></i> Confirm Course Deletion</div>
      <button class="modal-close" onclick="closeDeleteCourseModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--slate);font-size:.9rem;line-height:1.5;margin:0;">
        Delete course <strong id="delete-course-name">this course</strong>?
      </p>
      <p style="color:var(--slate);font-size:.82rem;line-height:1.5;margin-top:.85rem;">
        This will only work when there are no offerings or enrollments linked to it.
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeDeleteCourseModal()">Cancel</button>
      <button class="btn btn-danger" id="confirm-delete-course-btn" onclick="confirmDeleteCourse()">
        <i class="fas fa-trash"></i> Delete Course
      </button>
    </div>
  </div>
</div>

<script>
  const API = BASE_URL + '/backend/admin/admin_api.php';
  let allStudents = [];
  let coursesCache = [];
  let offeringsCache = [];
  let instructorsCache = [];
  let editingCourseId = null;
  let editingOfferingId = null;
  let pendingDeleteCourse = null;
  let pendingDeleteOffering = null;

  function escHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  async function loadCourses() {
    const res = await fetch(API + '?action=get_courses');
    const data = await res.json();
    coursesCache = data.courses || [];
    renderCourses();
  }

  function renderCourses() {
    const el = document.getElementById('courses-list');
    const courses = coursesCache;

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
    <th>Code</th><th>Title</th><th>Units</th><th>Offerings</th><th>Description</th><th>Actions</th>
  </tr></thead><tbody>
  ${courses.map(c => {
    const isEditing = Number(editingCourseId) === Number(c.course_id);
    if (isEditing) {
      return `<tr>
        <td data-label="Code"><input id="ec-code-${c.course_id}" class="form-control table-input" maxlength="20" value="${escHtml(c.code)}"></td>
        <td data-label="Title"><input id="ec-title-${c.course_id}" class="form-control table-input" maxlength="150" value="${escHtml(c.title)}"></td>
        <td data-label="Units" style="text-align:center"><input id="ec-units-${c.course_id}" class="form-control table-input table-input-sm" type="number" min="1" max="6" value="${Number(c.units || 3)}"></td>
        <td data-label="Offerings" style="text-align:center"><span class="badge badge-submitted">${c.offering_count} offering${c.offering_count != 1 ? 's' : ''}</span></td>
        <td data-label="Description"><textarea id="ec-desc-${c.course_id}" class="form-control table-input table-textarea">${escHtml(c.description || '')}</textarea></td>
        <td data-label="Actions">
          <div class="table-actions">
            <button class="btn btn-sm btn-primary" onclick="saveCourseEdit(${c.course_id})"><i class="fas fa-save"></i></button>
            <button class="btn btn-sm btn-outline" onclick="cancelCourseEdit()"><i class="fas fa-times"></i></button>
          </div>
        </td>
      </tr>`;
    }

    return `<tr>
      <td data-label="Code"><strong>${escHtml(c.code)}</strong></td>
      <td data-label="Title">${escHtml(c.title)}</td>
      <td data-label="Units" style="text-align:center">${Number(c.units || 0)}</td>
      <td data-label="Offerings" style="text-align:center">
        <span class="badge badge-submitted">${c.offering_count} offering${c.offering_count != 1 ? 's' : ''}</span>
      </td>
      <td data-label="Description" style="color:var(--slate);font-size:.82rem">${c.description ? escHtml(c.description.substring(0, 80)) + (c.description.length > 80 ? '...' : '') : '—'}</td>
      <td data-label="Actions">
        <div class="table-actions">
          <button class="btn btn-sm btn-outline" onclick="startCourseEdit(${c.course_id})"><i class="fas fa-pen"></i></button>
          <button class="btn btn-sm btn-danger" onclick="openDeleteCourseModal(${c.course_id})"><i class="fas fa-trash"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('')}
  </tbody></table>`;
  }

  async function loadOfferings() {
    const res = await fetch(API + '?action=get_offering_list');
    const data = await res.json();
    offeringsCache = data.offerings || [];
    renderOfferings();
  }

  function renderOfferings() {
    const el = document.getElementById('offerings-list');

    if (!offeringsCache.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-layer-group"></i><p>No offerings yet.</p></div>';
      return;
    }

    el.innerHTML = `<table class="courses-table offerings-table"><thead><tr>
      <th>Course</th><th>Term</th><th>Section</th><th>Schedule</th><th>Room</th><th>Instructor</th><th>Students</th><th>Actions</th>
    </tr></thead><tbody>
    ${offeringsCache.map(o => {
      const isEditing = Number(editingOfferingId) === Number(o.offering_id);

      if (isEditing) {
        const courseOptions = coursesCache.map(c => {
          const selected = Number(c.course_id) === Number(o.course_id) ? 'selected' : '';
          return `<option value="${c.course_id}" ${selected}>${escHtml(c.code)} - ${escHtml(c.title)}</option>`;
        }).join('');

        const instructorOptions = instructorsCache.map(i => {
          const selected = Number(i.user_id) === Number(o.instructor_id || 0) ? 'selected' : '';
          return `<option value="${i.user_id}" ${selected}>${escHtml(i.first_name)} ${escHtml(i.last_name)}</option>`;
        }).join('');

        return `<tr>
          <td data-label="Course"><select id="eo-course-${o.offering_id}" class="form-control table-input">${courseOptions}</select></td>
          <td data-label="Term"><input id="eo-term-${o.offering_id}" class="form-control table-input" value="${escHtml(o.term)}"></td>
          <td data-label="Section"><input id="eo-section-${o.offering_id}" class="form-control table-input" value="${escHtml(o.section)}"></td>
          <td data-label="Schedule"><input id="eo-schedule-${o.offering_id}" class="form-control table-input" value="${escHtml(o.schedule || '')}"></td>
          <td data-label="Room"><input id="eo-room-${o.offering_id}" class="form-control table-input" value="${escHtml(o.room || '')}"></td>
          <td data-label="Instructor">
            <select id="eo-instructor-${o.offering_id}" class="form-control table-input">
              <option value="">None / Assign later</option>
              ${instructorOptions}
            </select>
          </td>
          <td data-label="Students" style="text-align:center"><span class="badge badge-medium">${Number(o.student_count || 0)}</span></td>
          <td data-label="Actions">
            <div class="table-actions">
              <button class="btn btn-sm btn-primary" onclick="saveOfferingEdit(${o.offering_id})"><i class="fas fa-save"></i></button>
              <button class="btn btn-sm btn-outline" onclick="cancelOfferingEdit()"><i class="fas fa-times"></i></button>
            </div>
          </td>
        </tr>`;
      }

      const instructorName = o.first_name ? `${escHtml(o.first_name)} ${escHtml(o.last_name)}` : '—';
      return `<tr>
        <td data-label="Course"><strong>${escHtml(o.code)}</strong><div style="font-size:.78rem;color:var(--slate)">${escHtml(o.title)}</div></td>
        <td data-label="Term">${escHtml(o.term)}</td>
        <td data-label="Section">${escHtml(o.section)}</td>
        <td data-label="Schedule">${o.schedule ? escHtml(o.schedule) : '—'}</td>
        <td data-label="Room">${o.room ? escHtml(o.room) : '—'}</td>
        <td data-label="Instructor">${instructorName}</td>
        <td data-label="Students" style="text-align:center"><span class="badge badge-medium">${Number(o.student_count || 0)}</span></td>
        <td data-label="Actions">
          <div class="table-actions">
            <button class="btn btn-sm btn-outline" onclick="startOfferingEdit(${o.offering_id})"><i class="fas fa-pen"></i></button>
            <button class="btn btn-sm btn-danger" onclick="openDeleteOfferingModal(${o.offering_id})"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>`;
    }).join('')}
    </tbody></table>`;
  }

  async function loadInstructorsForSelect() {
    const res = await fetch(API + '?action=get_users&role=instructor');
    const data = await res.json();
    instructorsCache = data.users || [];
    const sel = document.getElementById('o-instructor');
    sel.innerHTML = '<option value="">None / Assign later</option>' +
      instructorsCache.map(u => `<option value="${u.user_id}">${escHtml(u.first_name)} ${escHtml(u.last_name)} (${escHtml(u.extra_info || '')})</option>`).join('');
  }

  async function loadStudentsForEnroll() {
    const res = await fetch(API + '?action=get_users&role=student');
    const data = await res.json();
    allStudents = data.users || [];
  }

  async function loadCoursesForSelect() {
    if (!coursesCache.length) {
      await loadCourses();
    }
    const sel = document.getElementById('o-course');
    sel.innerHTML = '<option value="">Select course...</option>' +
      coursesCache.map(c => `<option value="${c.course_id}">${escHtml(c.code)} - ${escHtml(c.title)}</option>`).join('');
  }

  function openCourseModal() {
    ['c-code', 'c-title', 'c-desc'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('c-units').value = 3;
    document.getElementById('course-modal').classList.add('show');
  }

  async function openOfferingModal() {
    await loadCoursesForSelect();
    await loadInstructorsForSelect();
    ['o-semester', 'o-academic-year', 'o-section', 'o-schedule', 'o-room'].forEach(id => document.getElementById(id).value = '');
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
    const res = await fetch(API, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      toast(data.message);
      document.getElementById('course-modal').classList.remove('show');
      await Promise.all([loadCourses(), loadOfferings()]);
    } else toast(data.message, 'error');
  }

  async function saveOffering() {
    const semester = document.getElementById('o-semester').value.trim();
    const academicYear = document.getElementById('o-academic-year').value.trim();
    const section = document.getElementById('o-section').value.trim();

    if (!semester || !academicYear || !section) {
      toast('Semester, academic year, and section are required.', 'error');
      return;
    }

    const courseId = document.getElementById('o-course').value;
    if (!courseId) {
      toast('Please select a course.', 'error');
      return;
    }

    const term = `${semester} AY ${academicYear}`;

    const fd = new FormData();
    fd.append('action', 'add_offering');
    fd.append('course_id', courseId);
    fd.append('term', term);
    fd.append('section', section);
    fd.append('schedule', document.getElementById('o-schedule').value.trim());
    fd.append('room', document.getElementById('o-room').value.trim());
    fd.append('instructor_id', document.getElementById('o-instructor').value);
    const res = await fetch(API, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      toast(data.message);
      document.getElementById('offering-modal').classList.remove('show');
      await Promise.all([loadCourses(), loadOfferings()]);
    } else toast(data.message, 'error');
  }

  async function enrollStudent() {
    const fd = new FormData();
    fd.append('action', 'enroll_student');
    fd.append('offering_id', document.getElementById('enroll-offering-id').value);
    fd.append('student_id', document.getElementById('enroll-student').value);
    const res = await fetch(API, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      toast(data.message);
      document.getElementById('enroll-modal').classList.remove('show');
    } else toast(data.message, 'error');
  }

  function startCourseEdit(courseId) {
    editingCourseId = Number(courseId);
    editingOfferingId = null;
    renderCourses();
    renderOfferings();
  }

  function cancelCourseEdit() {
    editingCourseId = null;
    renderCourses();
  }

  async function saveCourseEdit(courseId) {
    const code = document.getElementById(`ec-code-${courseId}`).value.trim();
    const title = document.getElementById(`ec-title-${courseId}`).value.trim();
    const units = Number(document.getElementById(`ec-units-${courseId}`).value || 0);
    const description = document.getElementById(`ec-desc-${courseId}`).value.trim();

    if (!code || !title) {
      toast('Code and title are required.', 'error');
      return;
    }
    if (units < 1 || units > 6) {
      toast('Units must be between 1 and 6.', 'error');
      return;
    }

    const fd = new FormData();
    fd.append('action', 'update_course');
    fd.append('course_id', courseId);
    fd.append('code', code);
    fd.append('title', title);
    fd.append('description', description);
    fd.append('units', units);

    const res = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      editingCourseId = null;
      toast(data.message);
      await Promise.all([loadCourses(), loadOfferings()]);
    } else {
      toast(data.message, 'error');
    }
  }

  function openDeleteCourseModal(courseId) {
    const course = coursesCache.find(c => Number(c.course_id) === Number(courseId));
    pendingDeleteCourse = {
      id: Number(courseId),
      name: course ? `${course.code} - ${course.title}` : 'this course'
    };
    document.getElementById('delete-course-name').textContent = pendingDeleteCourse.name;
    document.getElementById('delete-course-modal').classList.add('show');
  }

  function closeDeleteCourseModal() {
    pendingDeleteCourse = null;
    const btn = document.getElementById('confirm-delete-course-btn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-trash"></i> Delete Course';
    document.getElementById('delete-course-modal').classList.remove('show');
  }

  async function confirmDeleteCourse() {
    if (!pendingDeleteCourse) return;

    const btn = document.getElementById('confirm-delete-course-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

    try {
      const fd = new FormData();
      fd.append('action', 'delete_course');
      fd.append('course_id', pendingDeleteCourse.id);
      const res = await fetch(API, { method: 'POST', body: fd });
      const data = await res.json();

      if (data.success) {
        const deletedId = pendingDeleteCourse.id;
        closeDeleteCourseModal();
        toast(data.message);
        if (Number(editingCourseId) === Number(deletedId)) editingCourseId = null;
        await Promise.all([loadCourses(), loadOfferings()]);
      } else {
        toast(data.message, 'error');
      }
    } catch (error) {
      toast(error.message, 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-trash"></i> Delete Course';
    }
  }

  function openDeleteOfferingModal(offeringId) {
    const offering = offeringsCache.find(o => Number(o.offering_id) === Number(offeringId));
    const course = offering ? coursesCache.find(c => Number(c.course_id) === Number(offering.course_id)) : null;
    pendingDeleteOffering = {
      id: Number(offeringId),
      name: course ? `${course.code} ${offering.term} Section ${offering.section}` : 'this offering'
    };
    document.getElementById('delete-offering-name').textContent = pendingDeleteOffering.name;
    document.getElementById('delete-offering-modal').classList.add('show');
  }

  function closeDeleteOfferingModal() {
    pendingDeleteOffering = null;
    const btn = document.getElementById('confirm-delete-offering-btn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-trash"></i> Delete Offering';
    document.getElementById('delete-offering-modal').classList.remove('show');
  }

  async function confirmDeleteOffering() {
    if (!pendingDeleteOffering) return;

    const btn = document.getElementById('confirm-delete-offering-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

    try {
      const fd = new FormData();
      fd.append('action', 'delete_offering');
      fd.append('offering_id', pendingDeleteOffering.id);
      const res = await fetch(API, { method: 'POST', body: fd });
      const data = await res.json();

      if (data.success) {
        const deletedId = pendingDeleteOffering.id;
        closeDeleteOfferingModal();
        toast(data.message);
        if (Number(editingOfferingId) === Number(deletedId)) editingOfferingId = null;
        await Promise.all([loadCourses(), loadOfferings()]);
      } else {
        toast(data.message, 'error');
      }
    } catch (error) {
      toast(error.message, 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-trash"></i> Delete Offering';
    }
  }

  function startOfferingEdit(offeringId) {
    editingOfferingId = Number(offeringId);
    editingCourseId = null;
    renderCourses();
    renderOfferings();
  }

  function cancelOfferingEdit() {
    editingOfferingId = null;
    renderOfferings();
  }

  async function saveOfferingEdit(offeringId) {
    const courseId = document.getElementById(`eo-course-${offeringId}`).value;
    const term = document.getElementById(`eo-term-${offeringId}`).value.trim();
    const section = document.getElementById(`eo-section-${offeringId}`).value.trim();
    const schedule = document.getElementById(`eo-schedule-${offeringId}`).value.trim();
    const room = document.getElementById(`eo-room-${offeringId}`).value.trim();
    const instructorId = document.getElementById(`eo-instructor-${offeringId}`).value;

    if (!courseId || !term || !section) {
      toast('Course, term, and section are required.', 'error');
      return;
    }

    const fd = new FormData();
    fd.append('action', 'update_offering');
    fd.append('offering_id', offeringId);
    fd.append('course_id', courseId);
    fd.append('term', term);
    fd.append('section', section);
    fd.append('schedule', schedule);
    fd.append('room', room);
    fd.append('instructor_id', instructorId);

    const res = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      editingOfferingId = null;
      toast(data.message);
      await Promise.all([loadCourses(), loadOfferings()]);
    } else {
      toast(data.message, 'error');
    }
  }

  Promise.all([loadCourses(), loadOfferings(), loadInstructorsForSelect()]);
</script>
<?php layout_footer(); ?>