const API = BASE_URL + '/backend/instructor/instructor_api.php';

async function loadDashboard() {
  const res = await fetch(API + '?action=get_dashboard');
  const data = await res.json();

  document.getElementById('s-courses').textContent = data.offerings.length;
  document.getElementById('s-assignments').textContent = data.offerings.reduce((a, o) => a + parseInt(o.assignment_count, 10), 0);
  document.getElementById('s-subs').textContent = data.recent_submissions.length;
  document.getElementById('s-students').textContent = data.offerings.reduce((a, o) => a + parseInt(o.student_count, 10), 0);
  document.getElementById('d-courses').textContent = data.offerings.length;
  document.getElementById('d-assignments').textContent = data.offerings.reduce((a, o) => a + parseInt(o.assignment_count, 10), 0);
  document.getElementById('d-subs').textContent = data.recent_submissions.length;
  document.getElementById('d-students').textContent = data.offerings.reduce((a, o) => a + parseInt(o.student_count, 10), 0);

  const ofEl = document.getElementById('offerings-list');
  if (!data.offerings.length) {
    ofEl.innerHTML = '<div class="empty-state"><i class="fas fa-chalkboard"></i><p>No course offerings assigned yet.</p></div>';
  } else {
    ofEl.innerHTML = data.offerings.map((o) => `
      <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;justify-content:space-between">
          <div>
            <div style="font-weight:700;font-size:.95rem">${o.code} — ${o.title}</div>
            <div style="color:var(--slate);font-size:.8rem;margin-top:.2rem">
              ${o.section} | ${o.term}
              ${o.schedule ? ` | <i class="fas fa-clock"></i> ${o.schedule}` : ''}
              ${o.room ? ` | <i class="fas fa-door-open"></i> ${o.room}` : ''}
            </div>
          </div>
          <div style="text-align:right">
            <div style="font-weight:700;font-size:.9rem">${o.student_count} <span style="color:var(--slate);font-weight:400;font-size:.75rem">students</span></div>
            <div style="color:var(--slate);font-size:.75rem">${o.assignment_count} assignments</div>
          </div>
        </div>
      </div>`).join('');
  }

  const subEl = document.getElementById('submissions-list');
  if (!data.recent_submissions.length) {
    subEl.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No submissions yet.</p></div>';
  } else {
    subEl.innerHTML = data.recent_submissions.map((s) => `
      <div style="padding:.9rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-weight:600;font-size:.88rem">${s.first_name} ${s.last_name} <span style="color:var(--slate);font-weight:400">(${s.student_number})</span></div>
          <div style="color:var(--slate);font-size:.75rem;margin-top:.2rem">${s.course_code}: ${s.assignment_title}</div>
        </div>
        <div style="text-align:right">
          <span class="badge badge-${s.status}">${s.status}</span>
          <div style="color:var(--slate);font-size:.72rem;margin-top:.2rem">${new Date(s.submitted_at).toLocaleDateString('en-PH')}</div>
        </div>
      </div>`).join('');
  }
}

document.getElementById('curr-date').textContent = new Date().toLocaleDateString('en-PH', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric'
});
loadDashboard();
