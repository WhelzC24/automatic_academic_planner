const API = BASE_URL + '/backend/admin/admin_api.php';

async function loadDashboard() {
  const res = await fetch(API + '?action=get_stats');
  const data = await res.json();
  const st = data.stats;
  document.getElementById('st-students').textContent = st.student_count;
  document.getElementById('st-instructors').textContent = st.instructor_count;
  document.getElementById('st-courses').textContent = st.course_count;
  document.getElementById('st-asgs').textContent = st.active_assignments;
  document.getElementById('st-subs').textContent = st.today_submissions;

  document.getElementById('snap-students').textContent = st.student_count;
  document.getElementById('snap-instructors').textContent = st.instructor_count;
  document.getElementById('snap-courses').textContent = st.course_count;
  document.getElementById('snap-asgs').textContent = st.active_assignments;
  document.getElementById('snap-subs').textContent = st.today_submissions;

  const el = document.getElementById('recent-logs');
  if (!data.recent_logs.length) {
    el.innerHTML = '<div class="empty-state"><i class="fas fa-history"></i><p>No activity logged yet.</p></div>';
    return;
  }

  el.innerHTML = `<table class="recent-logs-table"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Description</th></tr></thead><tbody>
  ${data.recent_logs.map(l => `<tr>
      <td data-label="Time" style="font-size:.78rem;color:var(--slate)">${new Date(l.created_at).toLocaleString('en-PH')}</td>
      <td data-label="User" style="font-weight:600;font-size:.85rem">${l.username || '—'}</td>
      <td data-label="Action"><span class="badge badge-submitted" style="font-size:.7rem">${l.action}</span></td>
      <td data-label="Description" style="font-size:.82rem;color:var(--slate)">${l.description || ''}</td>
  </tr>`).join('')}
  </tbody></table>`;
}

loadDashboard();
