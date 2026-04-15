const API = BASE_URL + '/backend/admin/admin_api.php';
let allEnrollments = [];
let pendingUnenrollId = null;

async function init() {
  const res = await fetch(API + '?action=get_courses');
  const data = await res.json();

  await fetch(API + '?action=get_offering_list');
  populateOfferingFilter(data.courses || []);
  await loadEnrollments();
  await populateEnrollModal();
}

async function loadEnrollments() {
  const offeringId = document.getElementById('filter-offering').value;
  const res = await fetch(`${API}?action=get_enrollments${offeringId ? '&offering_id=' + offeringId : ''}`);
  let data;
  try {
    data = await res.json();
  } catch (e) {
    data = { success: false };
  }

  if (!data.success) {
    await loadEnrollmentsFallback();
    return;
  }
  allEnrollments = data.enrollments || [];
  renderEnrollments();
}

async function loadEnrollmentsFallback() {
  const res = await fetch(API + '?action=get_users&role=student');
  const data = await res.json();
  allEnrollments = (data.users || []).map(u => ({
    student_id: u.user_id,
    student_number: u.student_number || '—',
    first_name: u.first_name,
    last_name: u.last_name,
    program: u.extra_info || '—',
    course_code: '—',
    course_title: '—',
    section: '—',
    term: '—',
    enrolled_at: u.created_at
  }));
  renderEnrollments();
}

function renderEnrollments() {
  const total = allEnrollments.length;
  const offerings = new Set(allEnrollments.map(e => e.section || '')).size || 0;
  const students = new Set(allEnrollments.map(e => e.student_id)).size;

  document.getElementById('stat-total').textContent = total;
  document.getElementById('stat-offerings').textContent = offerings;
  document.getElementById('stat-students').textContent = students;
  document.getElementById('enr-total').textContent = total;
  document.getElementById('enr-offerings').textContent = offerings;
  document.getElementById('enr-students').textContent = students;

  filterTable();
}

function filterTable() {
  const q = document.getElementById('filter-search').value.toLowerCase();
  const offeringId = document.getElementById('filter-offering').value;
  const filtered = q
    ? allEnrollments.filter(e =>
      (e.first_name + ' ' + e.last_name).toLowerCase().includes(q) ||
      (e.student_number || '').toLowerCase().includes(q)
    )
    : allEnrollments;

  document.getElementById('enr-visible').textContent = filtered.length;
  document.getElementById('enr-filter').textContent = offeringId ? 'Offering #' + offeringId : 'All';

  const el = document.getElementById('enrollment-list');
  if (!filtered.length) {
    el.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><p>No enrollments found.</p></div>';
    return;
  }
  el.innerHTML = `<table><thead><tr>
    <th>Student</th><th>Student No.</th><th>Program</th>
    <th>Course</th><th>Section</th><th>Term</th><th>Enrolled</th>
    <th>Action</th>
  </tr></thead><tbody>
  ${filtered.map((e, idx) => `<tr data-idx="${idx}">
    <td><div style="font-weight:600">${e.first_name} ${e.last_name}</div></td>
    <td style="font-size:.85rem">${e.student_number || '—'}</td>
    <td style="font-size:.82rem;color:var(--slate)">${e.program || '—'}</td>
    <td style="font-size:.85rem">${e.course_code ? `<strong>${e.course_code}</strong> — ${e.course_title}` : '—'}</td>
    <td style="font-size:.82rem">${e.section || '—'}</td>
    <td style="font-size:.82rem;color:var(--slate)">${e.term || '—'}</td>
    <td style="font-size:.78rem;color:var(--slate)">${e.enrolled_at ? new Date(e.enrolled_at).toLocaleDateString('en-PH') : '—'}</td>
    <td>
      ${e.enrollment_id ? `<button class="btn btn-sm btn-danger" onclick="openUnenrollModal(${e.enrollment_id})" title="Remove enrollment"><i class="fas fa-user-minus"></i></button>` : ''}
    </td>
  </tr>`).join('')}
  </tbody></table>`;
}

function openUnenrollModal(enrollmentId) {
  pendingUnenrollId = enrollmentId;
  document.getElementById('unenroll-modal').classList.add('show');
}

function closeUnenrollModal() {
  pendingUnenrollId = null;
  document.getElementById('unenroll-modal').classList.remove('show');
}

function populateOfferingFilter(courses) {
  const sel = document.getElementById('filter-offering');
  sel.innerHTML = '<option value="">All Offerings</option>' +
    courses.map(c => `<option value="${c.course_id}">${c.code} — ${c.title}</option>`).join('');
}

async function populateEnrollModal() {
  const oRes = await fetch(API + '?action=get_courses');
  const oData = await oRes.json();
  document.getElementById('e-offering').innerHTML =
    '<option value="">Select offering...</option>' +
    (oData.courses || []).map(c => `<option value="${c.course_id}">${c.code} — ${c.title}</option>`).join('');

  const sRes = await fetch(API + '?action=get_users&role=student');
  const sData = await sRes.json();
  document.getElementById('e-student').innerHTML =
    '<option value="">Select student...</option>' +
    (sData.users || []).map(s => `<option value="${s.user_id}">${s.first_name} ${s.last_name} — ${s.student_number || '?'}</option>`).join('');
}

function openEnrollModal() {
  document.getElementById('enroll-modal').classList.add('show');
}

function closeEnroll() {
  document.getElementById('enroll-modal').classList.remove('show');
}

async function doEnroll() {
  const offeringId = document.getElementById('e-offering').value;
  const studentId = document.getElementById('e-student').value;
  if (!offeringId || !studentId) {
    toast('Please select both offering and student.', 'error');
    return;
  }

  const fd = new FormData();
  fd.append('action', 'enroll_student');
  fd.append('offering_id', offeringId);
  fd.append('student_id', studentId);
  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast(data.message);
    closeEnroll();
    await loadEnrollments();
  } else {
    toast(data.message, 'error');
  }
}

async function confirmUnenroll() {
  if (!pendingUnenrollId) return;

  const fd = new FormData();
  fd.append('action', 'unenroll_student');
  fd.append('enrollment_id', pendingUnenrollId);

  const btn = document.getElementById('confirm-unenroll-btn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';

  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();

  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-user-minus"></i> Remove Enrollment';

  if (data.success) {
    closeUnenrollModal();
    toast(data.message || 'Student unenrolled successfully.');
    await loadEnrollments();
  } else {
    toast(data.message || 'Unable to remove enrollment.', 'error');
  }
}


function exportCSV() {
  const rows = [['Student', 'Student No.', 'Program', 'Course', 'Section', 'Term', 'Enrolled Date']];
  allEnrollments.forEach(e => {
    rows.push([
      `${e.first_name} ${e.last_name}`,
      e.student_number || '',
      e.program || '',
      e.course_code ? `${e.course_code} - ${e.course_title}` : '',
      e.section || '',
      e.term || '',
      e.enrolled_at ? new Date(e.enrolled_at).toLocaleDateString('en-PH') : ''
    ]);
  });
  const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'enrollments_bisu.csv';
  a.click();
  URL.revokeObjectURL(url);
}

init();
