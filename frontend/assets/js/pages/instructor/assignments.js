const API = BASE_URL + '/backend/instructor/instructor_api.php';
let offerings = [];
let pendingDeleteId = null;

async function loadOfferings() {
  const res = await fetch(API + '?action=get_my_offerings');
  const data = await res.json();
  offerings = data.offerings || [];
  const sel = document.getElementById('a-offering');
  sel.innerHTML = '<option value="">Select course offering</option>' +
    offerings.map((o) => `<option value="${o.offering_id}">${o.code} — ${o.title} (${o.section})</option>`).join('');
}

async function loadAssignments() {
  const res = await fetch(API + '?action=get_my_assignments');
  const data = await res.json();
  const el = document.getElementById('asg-list');
  const totalAssignments = data.assignments.length;
  const overdueCount = data.assignments.filter((a) => new Date(a.due_at) < Date.now()).length;
  const withSubmissions = data.assignments.filter((a) => parseInt(a.submission_count, 10) > 0).length;
  document.getElementById('a-total').textContent = totalAssignments;
  document.getElementById('a-overdue').textContent = overdueCount;
  document.getElementById('a-with-subs').textContent = withSubmissions;

  if (!data.assignments.length) {
    el.innerHTML = '<div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No assignments yet. Create one!</p></div>';
    return;
  }
  el.innerHTML = `<table><thead><tr>
    <th>Title</th><th>Course</th><th>Due Date</th><th>Submissions</th><th>Actions</th>
  </tr></thead><tbody>
  ${data.assignments.map((a) => {
    const overdue = new Date(a.due_at) < Date.now();
    return `<tr>
      <td><div style="font-weight:600">${a.title}</div>
          <div style="color:var(--slate);font-size:.75rem">${a.description ? `${a.description.substring(0, 60)}...` : ''}</div></td>
      <td><div style="font-weight:500">${a.code}</div><div style="color:var(--slate);font-size:.78rem">${a.section}</div></td>
      <td>
        <div style="font-size:.88rem;color:${overdue ? 'var(--red)' : 'inherit'}">${new Date(a.due_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })}</div>
        <div style="color:var(--slate);font-size:.75rem">${new Date(a.due_at).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })}</div>
      </td>
      <td>
        <span style="font-weight:700;color:var(--deep)">${a.submission_count}</span>
        <span style="color:var(--slate);font-size:.8rem"> / ${a.enrolled_count} students</span>
      </td>
      <td>
        <div style="display:flex;gap:.4rem">
          <button class="btn btn-sm btn-outline" onclick="viewSubs(${a.assignment_id},'${a.title.replace(/'/g, "\\'")}',${a.max_score})"><i class="fas fa-list"></i> Submissions</button>
          <button class="btn btn-sm btn-danger" onclick="openDeleteModal(${a.assignment_id})"><i class="fas fa-trash"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('')}
  </tbody></table>`;
}

function openDeleteModal(id) {
  pendingDeleteId = id;
  document.getElementById('delete-modal').classList.add('show');
}

function closeDeleteModal() {
  pendingDeleteId = null;
  document.getElementById('delete-modal').classList.remove('show');
}

function openModal() {
  document.getElementById('edit-id').value = '';
  document.getElementById('a-title').value = '';
  document.getElementById('a-desc').value = '';
  document.getElementById('a-due').value = '';
  document.getElementById('a-score').value = 100;
  document.getElementById('modal-title').textContent = 'Create Assignment';
  document.getElementById('asg-modal').classList.add('show');
}

function closeModal() {
  document.getElementById('asg-modal').classList.remove('show');
}

async function saveAssignment() {
  const title = document.getElementById('a-title').value.trim();
  const due = document.getElementById('a-due').value;
  const ofid = document.getElementById('a-offering').value;
  if (!ofid || !title || !due) {
    toast('Please fill in required fields.', 'error');
    return;
  }

  const fd = new FormData();
  fd.append('action', 'create_assignment');
  fd.append('offering_id', ofid);
  fd.append('title', title);
  fd.append('description', document.getElementById('a-desc').value);
  fd.append('due_at', due);
  fd.append('max_score', document.getElementById('a-score').value);

  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast(data.message);
    closeModal();
    loadAssignments();
  } else {
    toast(data.message, 'error');
  }
}

async function confirmDeleteAsg() {
  if (!pendingDeleteId) return;
  const fd = new FormData();
  fd.append('action', 'delete_assignment');
  fd.append('assignment_id', pendingDeleteId);
  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Assignment deleted.');
    closeDeleteModal();
    loadAssignments();
  } else {
    toast(data.message, 'error');
  }
}

let currentMaxScore = 100;
async function viewSubs(asgId, title, maxScore) {
  currentMaxScore = maxScore;
  const res = await fetch(`${API}?action=get_submissions&assignment_id=${asgId}`);
  const data = await res.json();
  const el = document.getElementById('asg-list');
  if (!data.submissions.length) {
    toast('No submissions yet for this assignment.', 'warning');
    return;
  }
  el.innerHTML = `
    <div style="padding:1rem 1.5rem;background:var(--bg);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="font-weight:700">${title} — Submissions</div>
      <button class="btn btn-sm btn-outline" onclick="loadAssignments()"><i class="fas fa-arrow-left"></i> Back</button>
    </div>
    <table><thead><tr><th>Student</th><th>Submitted</th><th>Status</th><th>Grade</th><th>Action</th></tr></thead>
    <tbody>${data.submissions.map((s) => `<tr>
      <td>
        <div style="font-weight:600">${s.first_name} ${s.last_name}</div>
        <div style="color:var(--slate);font-size:.78rem">${s.student_number} | ${s.program} Y${s.year_level}</div>
      </td>
      <td style="font-size:.85rem">${new Date(s.submitted_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
      <td><span class="badge badge-${s.status}">${s.status}</span></td>
      <td>${s.grade !== null ? `<strong>${s.grade}</strong>/${maxScore}` : '<span style="color:var(--slate)">Not graded</span>'}</td>
      <td>
        <div style="display:flex;gap:.4rem">
          ${s.file_path ? `<a href="${BASE_URL}/${s.file_path}" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-download"></i></a>` : ''}
          <button class="btn btn-sm btn-gold" onclick="openGrade(${s.submission_id},'${s.first_name} ${s.last_name}',${maxScore})"><i class="fas fa-star"></i> Grade</button>
        </div>
      </td>
    </tr>`).join('')}</tbody></table>`;
}

function openGrade(subId, name, max) {
  document.getElementById('g-sub-id').value = subId;
  document.getElementById('g-student-info').textContent = `Student: ${name}`;
  document.getElementById('g-max').textContent = max;
  document.getElementById('g-grade').max = max;
  document.getElementById('g-grade').value = '';
  document.getElementById('g-feedback').value = '';
  document.getElementById('grade-modal').classList.add('show');
}

function closeGrade() {
  document.getElementById('grade-modal').classList.remove('show');
}

async function saveGrade() {
  const fd = new FormData();
  fd.append('action', 'grade_submission');
  fd.append('submission_id', document.getElementById('g-sub-id').value);
  fd.append('grade', document.getElementById('g-grade').value);
  fd.append('feedback', document.getElementById('g-feedback').value);
  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('Grade saved!');
    closeGrade();
  } else {
    toast(data.message, 'error');
  }
}

loadOfferings();
loadAssignments();
