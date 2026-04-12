const API = BASE_URL + '/backend/instructor/instructor_api.php';

async function loadCourses() {
  const res = await fetch(API + '?action=get_my_offerings');
  const data = await res.json();
  const el = document.getElementById('courses-grid');

  const totalOfferings = data.offerings.length;
  const totalUnits = data.offerings.reduce((sum, o) => sum + parseFloat(o.units || 0), 0);
  const sectionCount = new Set(data.offerings.map((o) => o.section).filter(Boolean)).size;
  document.getElementById('c-total').textContent = totalOfferings;
  document.getElementById('c-units').textContent = Number.isInteger(totalUnits) ? totalUnits : totalUnits.toFixed(1);
  document.getElementById('c-sections').textContent = sectionCount;

  if (!data.offerings.length) {
    el.innerHTML = '<div class="empty-state" style="padding:4rem"><i class="fas fa-book"></i><p>No courses assigned yet.</p></div>';
    return;
  }

  el.innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem">
  ${data.offerings.map((o) => `
    <div class="card">
      <div style="padding:1.5rem;border-bottom:4px solid var(--gold);border-radius:12px 12px 0 0;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
          <div>
            <div style="font-size:.75rem;font-weight:700;color:var(--gold);letter-spacing:.08em;text-transform:uppercase">${o.code}</div>
            <div style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--navy);margin-top:.2rem;line-height:1.3">${o.title}</div>
          </div>
          <span class="badge badge-submitted" style="white-space:nowrap">${o.units} units</span>
        </div>
      </div>
      <div style="padding:1.25rem">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;font-size:.82rem">
          <div><span style="color:var(--slate)">Section</span><div style="font-weight:600;margin-top:.15rem">${o.section}</div></div>
          <div><span style="color:var(--slate)">Term</span><div style="font-weight:600;margin-top:.15rem">${o.term}</div></div>
          ${o.schedule ? `<div><span style="color:var(--slate)">Schedule</span><div style="font-weight:600;margin-top:.15rem">${o.schedule}</div></div>` : ''}
          ${o.room ? `<div><span style="color:var(--slate)">Room</span><div style="font-weight:600;margin-top:.15rem">${o.room}</div></div>` : ''}
        </div>
        <div style="margin-top:1.25rem;display:flex;gap:.75rem">
          <a href="assignments.php" class="btn btn-primary btn-sm" style="flex:1;justify-content:center">
            <i class="fas fa-clipboard-list"></i> Assignments
          </a>
          <a href="submissions.php" class="btn btn-outline btn-sm" style="flex:1;justify-content:center">
            <i class="fas fa-inbox"></i> Submissions
          </a>
        </div>
      </div>
    </div>`).join('')}
  </div>`;
}

loadCourses();
