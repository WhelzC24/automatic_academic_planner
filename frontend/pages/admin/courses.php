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
      <div class="form-group"><label>Department *</label>
        <select class="form-control" id="o-department">
          <option value="">Select department...</option>
          <option value="BSCS">Bachelor of Science in Computer Science (BSCS)</option>
        </select>
      </div>
      <div class="form-group"><label>Course *</label>
        <select class="form-control" id="o-course" onchange="onCourseSelect()">
          <option value="">Select course...</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Year *</label>
          <select class="form-control" id="o-year">
            <option value="">Select year...</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
          </select>
        </div>
        <div class="form-group"><label>Block *</label>
          <select class="form-control" id="o-block">
            <option value="">Select block...</option>
            <option value="A">Block A</option>
            <option value="B">Block B</option>
          </select>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:.5rem;margin-top:1.5rem">
          <input type="checkbox" id="o-both-blocks" onchange="toggleBothBlocks()" style="width:1.25rem;height:1.25rem">
          <label style="margin:0;font-weight:500">Create for both Block A and B</label>
        </div>
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
<div class="form-group"><label>Day(s) *</label>
          <select class="form-control" id="o-day" multiple size="5">
            <option value="Mon">Monday</option>
            <option value="Tue">Tuesday</option>
            <option value="Wed">Wednesday</option>
            <option value="Thu">Thursday</option>
            <option value="Fri">Friday</option>
            <option value="Sat">Saturday</option>
          </select>
          <small style="color:var(--slate);font-size:.75rem">Hold Ctrl/Cmd to select multiple days</small>
        </div>
        <div class="form-group"><label>Time Start *</label>
          <select class="form-control" id="o-time-start">
            <option value="">Start...</option>
            <option value="07:00">7:00 AM</option>
            <option value="07:30">7:30 AM</option>
            <option value="08:00">8:00 AM</option>
            <option value="08:30">8:30 AM</option>
            <option value="09:00">9:00 AM</option>
            <option value="09:30">9:30 AM</option>
            <option value="10:00">10:00 AM</option>
            <option value="10:30">10:30 AM</option>
            <option value="11:00">11:00 AM</option>
            <option value="11:30">11:30 AM</option>
            <option value="12:00">12:00 PM</option>
            <option value="12:30">12:30 PM</option>
            <option value="13:00">1:00 PM</option>
            <option value="13:30">1:30 PM</option>
            <option value="14:00">2:00 PM</option>
            <option value="14:30">2:30 PM</option>
            <option value="15:00">3:00 PM</option>
            <option value="15:30">3:30 PM</option>
            <option value="16:00">4:00 PM</option>
            <option value="16:30">4:30 PM</option>
            <option value="17:00">5:00 PM</option>
            <option value="17:30">5:30 PM</option>
            <option value="18:00">6:00 PM</option>
            <option value="18:30">6:30 PM</option>
            <option value="19:00">7:00 PM</option>
            <option value="19:30">7:30 PM</option>
            <option value="20:00">8:00 PM</option>
          </select>
        </div>
        <div class="form-group"><label>Time End *</label>
          <select class="form-control" id="o-time-end">
            <option value="">End...</option>
            <option value="07:30">7:30 AM</option>
            <option value="08:00">8:00 AM</option>
            <option value="08:30">8:30 AM</option>
            <option value="09:00">9:00 AM</option>
            <option value="09:30">9:30 AM</option>
            <option value="10:00">10:00 AM</option>
            <option value="10:30">10:30 AM</option>
            <option value="11:00">11:00 AM</option>
            <option value="11:30">11:30 AM</option>
            <option value="12:00">12:00 PM</option>
            <option value="12:30">12:30 PM</option>
            <option value="13:00">1:00 PM</option>
            <option value="13:30">1:30 PM</option>
            <option value="14:00">2:00 PM</option>
            <option value="14:30">2:30 PM</option>
            <option value="15:00">3:00 PM</option>
            <option value="15:30">3:30 PM</option>
            <option value="16:00">4:00 PM</option>
            <option value="16:30">4:30 PM</option>
            <option value="17:00">5:00 PM</option>
            <option value="17:30">5:30 PM</option>
            <option value="18:00">6:00 PM</option>
            <option value="18:30">6:30 PM</option>
            <option value="19:00">7:00 PM</option>
            <option value="19:30">7:30 PM</option>
            <option value="20:00">8:00 PM</option>
            <option value="20:30">8:30 PM</option>
          </select>
        </div>
        <div class="form-group"><label>Room</label>
          <input type="text" class="form-control" id="o-room" placeholder="e.g., Lab A">
        </div>
      </div>
      <div id="block-b-schedule" style="display:none;margin-top:1rem;padding:1rem;background:var(--gray);border-radius:.5rem">
        <div style="font-weight:600;margin-bottom:.75rem;color:var(--primary)">Block B Schedule</div>
        <div class="form-row">
          <div class="form-group"><label>Day(s) *</label>
            <select class="form-control" id="o-day-b" multiple size="5">
              <option value="Mon">Monday</option>
              <option value="Tue">Tuesday</option>
              <option value="Wed">Wednesday</option>
              <option value="Thu">Thursday</option>
              <option value="Fri">Friday</option>
              <option value="Sat">Saturday</option>
            </select>
            <small style="color:var(--slate);font-size:.75rem">Hold Ctrl/Cmd to select multiple days</small>
          </div>
          <div class="form-group"><label>Time Start *</label>
            <select class="form-control" id="o-time-start-b">
              <option value="">Start...</option>
              <option value="07:00">7:00 AM</option>
              <option value="07:30">7:30 AM</option>
              <option value="08:00">8:00 AM</option>
              <option value="08:30">8:30 AM</option>
              <option value="09:00">9:00 AM</option>
              <option value="09:30">9:30 AM</option>
              <option value="10:00">10:00 AM</option>
              <option value="10:30">10:30 AM</option>
              <option value="11:00">11:00 AM</option>
              <option value="11:30">11:30 AM</option>
              <option value="12:00">12:00 PM</option>
              <option value="12:30">12:30 PM</option>
              <option value="13:00">1:00 PM</option>
              <option value="13:30">1:30 PM</option>
              <option value="14:00">2:00 PM</option>
              <option value="14:30">2:30 PM</option>
              <option value="15:00">3:00 PM</option>
              <option value="15:30">3:30 PM</option>
              <option value="16:00">4:00 PM</option>
              <option value="16:30">4:30 PM</option>
              <option value="17:00">5:00 PM</option>
              <option value="17:30">5:30 PM</option>
              <option value="18:00">6:00 PM</option>
              <option value="18:30">6:30 PM</option>
              <option value="19:00">7:00 PM</option>
              <option value="19:30">7:30 PM</option>
              <option value="20:00">8:00 PM</option>
            </select>
          </div>
          <div class="form-group"><label>Time End *</label>
            <select class="form-control" id="o-time-end-b">
              <option value="">End...</option>
              <option value="07:30">7:30 AM</option>
              <option value="08:00">8:00 AM</option>
              <option value="08:30">8:30 AM</option>
              <option value="09:00">9:00 AM</option>
              <option value="09:30">9:30 AM</option>
              <option value="10:00">10:00 AM</option>
              <option value="10:30">10:30 AM</option>
              <option value="11:00">11:00 AM</option>
              <option value="11:30">11:30 AM</option>
              <option value="12:00">12:00 PM</option>
              <option value="12:30">12:30 PM</option>
              <option value="13:00">1:00 PM</option>
              <option value="13:30">1:30 PM</option>
              <option value="14:00">2:00 PM</option>
              <option value="14:30">2:30 PM</option>
              <option value="15:00">3:00 PM</option>
              <option value="15:30">3:30 PM</option>
              <option value="16:00">4:00 PM</option>
              <option value="16:30">4:30 PM</option>
              <option value="17:00">5:00 PM</option>
              <option value="17:30">5:30 PM</option>
              <option value="18:00">6:00 PM</option>
              <option value="18:30">6:30 PM</option>
              <option value="19:00">7:00 PM</option>
              <option value="19:30">7:30 PM</option>
              <option value="20:00">8:00 PM</option>
              <option value="20:30">8:30 PM</option>
            </select>
          </div>
          <div class="form-group"><label>Room</label>
            <input type="text" class="form-control" id="o-room-b" placeholder="e.g., Lab B">
          </div>
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

  function formatTime(timeStr) {
    if (!timeStr) return '';
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${m} ${ampm}`;
  }

  function isDaySelected(scheduleStr, day) {
    if (!scheduleStr) return false;
    const days = scheduleStr.split(',');
    return days.includes(day) ? 'selected' : '';
  }

  function generateTimeOptions(selected) {
    const times = [];
    for (let h = 7; h <= 20; h++) {
      for (let m = 0; m < 60; m += 30) {
        const hour24 = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
        const hour12 = h % 12 || 12;
        const ampm = h >= 12 ? 'PM' : 'AM';
        const display = `${hour12}:${String(m).padStart(2, '0')} ${ampm}`;
        const sel = hour24 === selected ? 'selected' : '';
        times.push(`<option value="${hour24}" ${sel}>${display}</option>`);
      }
    }
    return times.join('');
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
          <td data-label="Schedule">
            <select id="eo-schedule-${o.offering_id}" class="form-control table-input" multiple size="5">
              <option value="Mon" ${isDaySelected(o.schedule, 'Mon')}>Monday</option>
              <option value="Tue" ${isDaySelected(o.schedule, 'Tue')}>Tuesday</option>
              <option value="Wed" ${isDaySelected(o.schedule, 'Wed')}>Wednesday</option>
              <option value="Thu" ${isDaySelected(o.schedule, 'Thu')}>Thursday</option>
              <option value="Fri" ${isDaySelected(o.schedule, 'Fri')}>Friday</option>
              <option value="Sat" ${isDaySelected(o.schedule, 'Sat')}>Saturday</option>
            </select>
            <select id="eo-time-start-${o.offering_id}" class="form-control table-input" style="margin-top:4px">
              <option value="">Start...</option>
              ${generateTimeOptions(o.time_start)}
            </select>
            <select id="eo-time-end-${o.offering_id}" class="form-control table-input" style="margin-top:4px">
              <option value="">End...</option>
              ${generateTimeOptions(o.time_end)}
            </select>
          </td>
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
        <td data-label="Schedule">${o.schedule ? escHtml(o.schedule) : '—'} ${o.time_start ? ` | ${formatTime(o.time_start)} - ${formatTime(o.time_end)}` : ''}</td>
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
    
    const toOrdinal = (n) => n + (n == 1 ? 'st' : n == 2 ? 'nd' : n == 3 ? 'rd' : 'th');
    
    const grouped = {};
    coursesCache.forEach(c => {
      const key = `${toOrdinal(c.year_level)} Year - ${c.semester}`;
      if (!grouped[key]) grouped[key] = [];
      grouped[key].push(c);
    });
    
    const order = ['1st Year - 1st Semester', '1st Year - 2nd Semester', 
               '2nd Year - 1st Semester', '2nd Year - 2nd Semester',
               '3rd Year - 1st Semester', '3rd Year - 2nd Semester', '3rd Year - Summer',
               '4th Year - 1st Semester', '4th Year - 2nd Semester'];
    
    let html = '<option value="">Select course...</option>';
    order.forEach(key => {
      if (grouped[key]) {
        html += `<optgroup label="${key}">`;
        grouped[key].forEach(c => {
          html += `<option value="${c.course_id}" data-year="${c.year_level}" data-semester="${c.semester}">${escHtml(c.code)} - ${escHtml(c.title)}</option>`;
        });
        html += '</optgroup>';
      }
    });
    
    sel.innerHTML = html;
  }
  
  function onCourseSelect() {
    const sel = document.getElementById('o-course');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.year) {
      document.getElementById('o-year').value = opt.dataset.year;
      document.getElementById('o-semester').value = opt.dataset.semester;
    }
  }

  function openCourseModal() {
    ['c-code', 'c-title', 'c-desc'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('c-units').value = 3;
    document.getElementById('course-modal').classList.add('show');
  }

  async function openOfferingModal() {
    await loadCoursesForSelect();
    await loadInstructorsForSelect();
    ['o-department', 'o-course', 'o-year', 'o-block', 'o-semester', 'o-academic-year', 'o-day', 'o-time-start', 'o-time-end', 'o-room'].forEach(id => document.getElementById(id).value = '');
    ['o-day-b', 'o-time-start-b', 'o-time-end-b', 'o-room-b'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('o-both-blocks').checked = false;
    document.getElementById('block-b-schedule').style.display = 'none';
    document.getElementById('o-block').parentElement.style.display = '';
    document.getElementById('offering-modal').classList.add('show');
  }
  
  function toggleBothBlocks() {
    const both = document.getElementById('o-both-blocks').checked;
    document.getElementById('block-b-schedule').style.display = both ? 'block' : 'none';
    document.getElementById('o-block').parentElement.style.display = both ? 'none' : '';
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
    const department = document.getElementById('o-department').value;
    const courseId = document.getElementById('o-course').value;
    const year = document.getElementById('o-year').value;
    const block = document.getElementById('o-block').value;
    const semester = document.getElementById('o-semester').value.trim();
    const academicYear = document.getElementById('o-academic-year').value.trim();
    const bothBlocks = document.getElementById('o-both-blocks').checked;

    if (!department || !courseId || !year || !semester || !academicYear) {
      toast('Department, course, year, semester, and academic year are required.', 'error');
      return;
    }

    const term = `${semester} AY ${academicYear}`;
    const instructorId = document.getElementById('o-instructor').value;

    if (bothBlocks) {
      const daySelectA = document.getElementById('o-day');
      const selectedDaysA = Array.from(daySelectA.selectedOptions).map(opt => opt.value);
      const timeStartA = document.getElementById('o-time-start').value;
      const timeEndA = document.getElementById('o-time-end').value;
      const roomA = document.getElementById('o-room').value.trim();
      const daySelectB = document.getElementById('o-day-b');
      const selectedDaysB = Array.from(daySelectB.selectedOptions).map(opt => opt.value);
      const timeStartB = document.getElementById('o-time-start-b').value;
      const timeEndB = document.getElementById('o-time-end-b').value;
      const roomB = document.getElementById('o-room-b').value.trim();

      if (!selectedDaysA.length || !timeStartA || !timeEndA || !selectedDaysB.length || !timeStartB || !timeEndB) {
        toast('Block A and Block B schedules are both required.', 'error');
        return;
      }

      const scheduleA = selectedDaysA.join(',');
      const scheduleB = selectedDaysB.join(',');
      if (scheduleA === scheduleB && timeStartA === timeStartB && timeEndA === timeEndB) {
        toast('Block A and Block B must have different schedules.', 'error');
        return;
      }

      let fd = new FormData();
      fd.append('action', 'add_offering');
      fd.append('course_id', courseId);
      fd.append('term', term);
      fd.append('section', `${department}-${year}A`);
      fd.append('schedule', scheduleA);
      fd.append('time_start', timeStartA);
      fd.append('time_end', timeEndA);
      fd.append('room', roomA);
      fd.append('instructor_id', instructorId);
      let res = await fetch(API, { method: 'POST', body: fd });
      let data = await res.json();
      if (!data.success) { toast(data.message, 'error'); return; }

      fd = new FormData();
      fd.append('action', 'add_offering');
      fd.append('course_id', courseId);
      fd.append('term', term);
      fd.append('section', `${department}-${year}B`);
      fd.append('schedule', scheduleB);
      fd.append('time_start', timeStartB);
      fd.append('time_end', timeEndB);
      fd.append('room', roomB);
      fd.append('instructor_id', instructorId);
      res = await fetch(API, { method: 'POST', body: fd });
      data = await res.json();
      if (data.success) {
        toast('Course offerings created for Block A and Block B.');
        document.getElementById('offering-modal').classList.remove('show');
        await Promise.all([loadCourses(), loadOfferings()]);
      } else toast(data.message, 'error');
      return;
    }

    if (!block) {
      toast('Block is required.', 'error');
      return;
    }

    const daySelect = document.getElementById('o-day');
    const selectedDays = Array.from(daySelect.selectedOptions).map(opt => opt.value);
    const timeStart = document.getElementById('o-time-start').value;
    const timeEnd = document.getElementById('o-time-end').value;
    const room = document.getElementById('o-room').value.trim();

    if (!selectedDays.length || !timeStart || !timeEnd) {
      toast('Day(s), time start, and time end are required.', 'error');
      return;
    }

    const section = `${department}-${year}${block}`;
    const schedule = selectedDays.join(',');

    const fd = new FormData();
    fd.append('action', 'add_offering');
    fd.append('course_id', courseId);
    fd.append('term', term);
    fd.append('section', section);
    fd.append('schedule', schedule);
    fd.append('time_start', timeStart);
    fd.append('time_end', timeEnd);
    fd.append('room', room);
    fd.append('instructor_id', instructorId);
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
    const scheduleSelect = document.getElementById(`eo-schedule-${offeringId}`);
    const selectedDays = Array.from(scheduleSelect.selectedOptions).map(opt => opt.value);
    const timeStart = document.getElementById(`eo-time-start-${offeringId}`).value;
    const timeEnd = document.getElementById(`eo-time-end-${offeringId}`).value;
    const room = document.getElementById(`eo-room-${offeringId}`).value;
    const instructorId = document.getElementById(`eo-instructor-${offeringId}`).value;

    if (!courseId || !term || !section) {
      toast('Course, term, and section are required.', 'error');
      return;
    }

    const schedule = selectedDays.join(',');

    const fd = new FormData();
    fd.append('action', 'update_offering');
    fd.append('offering_id', offeringId);
    fd.append('course_id', courseId);
    fd.append('term', term);
    fd.append('section', section);
    fd.append('schedule', schedule);
    fd.append('time_start', timeStart);
    fd.append('time_end', timeEnd);
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