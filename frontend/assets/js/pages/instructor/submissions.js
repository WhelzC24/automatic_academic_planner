const API = BASE_URL + '/backend/instructor/instructor_api.php';
let currentMaxScore = 100;

async function loadAssignments() {
  const res = await fetch(API + '?action=get_my_assignments');
  const data = await res.json();
  const sel = document.getElementById('asg-select');
  if (!data.assignments.length) {
    sel.innerHTML = '<option value="">No assignments found</option>';
    document.getElementById('sub-total').textContent = '0';
    document.getElementById('sub-graded').textContent = '0';
    document.getElementById('sub-ungraded').textContent = '0';
    document.getElementById('sub-avg').textContent = '—';
    return;
  }
  sel.innerHTML = '<option value="">— Select an assignment —</option>' +
    data.assignments.map((a) => `<option value="${a.assignment_id}" data-max="${a.max_score}">${a.code} | ${a.title} (Due: ${new Date(a.due_at).toLocaleDateString('en-PH')})</option>`).join('');
}

async function loadSubs() {
  const sel = document.getElementById('asg-select');
  const asgId = sel.value;
  if (!asgId) return;
  const opt = sel.options[sel.selectedIndex];
  currentMaxScore = parseFloat(opt.dataset.max || 100);

  const res = await fetch(`${API}?action=get_submissions&assignment_id=${asgId}`);
  const data = await res.json();
  const el = document.getElementById('subs-area');

  if (!data.submissions.length) {
    el.innerHTML = '<div class="card"><div class="empty-state"><i class="fas fa-inbox"></i><p>No submissions yet for this assignment.</p></div></div>';
    document.getElementById('sub-total').textContent = '0';
    document.getElementById('sub-graded').textContent = '0';
    document.getElementById('sub-ungraded').textContent = '0';
    document.getElementById('sub-avg').textContent = '—';
    return;
  }

  const graded = data.submissions.filter((s) => s.status === 'graded').length;
  const total = data.submissions.length;
  const ungraded = total - graded;
  const avgGrade = data.submissions.filter((s) => s.grade !== null).reduce((a, s) => a + parseFloat(s.grade), 0) / (graded || 1);
  document.getElementById('sub-total').textContent = String(total);
  document.getElementById('sub-graded').textContent = String(graded);
  document.getElementById('sub-ungraded').textContent = String(ungraded);
  document.getElementById('sub-avg').textContent = graded ? avgGrade.toFixed(1) : '—';

  el.innerHTML = `
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-bottom:1.5rem">
      <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-upload"></i></div>
        <div class="stat-info"><div class="value">${total}</div><div class="label">Total Submissions</div></div></div>
      <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check"></i></div>
        <div class="stat-info"><div class="value">${graded}</div><div class="label">Graded</div></div></div>
      <div class="stat-card"><div class="stat-icon gold"><i class="fas fa-star"></i></div>
        <div class="stat-info"><div class="value">${graded ? avgGrade.toFixed(1) : '—'}</div><div class="label">Avg Grade / ${currentMaxScore}</div></div></div>
    </div>
    <div class="card">
      <div class="card-body" style="padding:0">
        <table><thead><tr>
          <th>Student</th><th>Submitted</th><th>Status</th><th>Grade</th><th>Feedback</th><th>Actions</th>
        </tr></thead><tbody>
        ${data.submissions.map((s) => `<tr>
          <td>
            <div style="font-weight:600">${s.first_name} ${s.last_name}</div>
            <div style="color:var(--slate);font-size:.75rem">${s.student_number} | ${s.program} Y${s.year_level}</div>
          </td>
          <td style="font-size:.85rem">${new Date(s.submitted_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
          <td><span class="badge badge-${s.status}">${s.status}</span></td>
          <td>${s.grade !== null ? `<strong style="color:var(--deep)">${s.grade}</strong><span style="color:var(--slate)">/${currentMaxScore}</span>` : '<span style="color:var(--slate)">—</span>'}</td>
          <td style="font-size:.78rem;color:var(--slate);max-width:180px">${s.feedback ? `${s.feedback.substring(0, 60)}...` : '—'}</td>
          <td>
            <div style="display:flex;gap:.4rem">
              ${s.file_path ? `<a href="${BASE_URL}/${s.file_path}" target="_blank" class="btn btn-sm btn-outline" title="Download"><i class="fas fa-download"></i></a>` : ''}
              <button class="btn btn-sm btn-gold" onclick="openGrade(${s.submission_id},'${s.first_name} ${s.last_name}','${s.status}')"><i class="fas fa-star"></i></button>
            </div>
          </td>
        </tr>`).join('')}
        </tbody></table>
      </div>
    </div>`;
}

function openGrade(subId, name, status) {
  document.getElementById('g-sub-id').value = subId;
  document.getElementById('g-student-info').textContent = `Student: ${name}`;
  document.getElementById('g-max').textContent = currentMaxScore;
  document.getElementById('g-grade').max = currentMaxScore;
  document.getElementById('g-grade').value = '';
  document.getElementById('g-feedback').value = '';
  document.getElementById('g-status').textContent = `Current status: ${status}`;
  document.getElementById('grade-modal').classList.add('show');
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
    document.getElementById('grade-modal').classList.remove('show');
    loadSubs();
  } else {
    toast(data.message, 'error');
  }
}

loadAssignments();
