<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('student');
require_once __DIR__ . '/../layout.php';
layout_header('My Schedule', 'student');
?>
<div class="app-shell">
  <?php layout_sidebar('student', 'schedule'); ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1>My Schedule</h1>
        <p>Weekly and monthly view of your academic schedule</p>
      </div>
      <div class="topbar-actions">
        <div style="display:flex;gap:.5rem">
          <button class="btn btn-outline btn-sm view-btn" onclick="setView('week',this)">Week</button>
          <button class="btn btn-primary btn-sm view-btn" onclick="setView('list',this)">List</button>
        </div>
      </div>
    </div>
    <div class="page-content">

      <!-- Week view -->
      <div id="week-view" style="display:none">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
          <button class="btn btn-outline" onclick="changeWeek(-1)"><i class="fas fa-chevron-left"></i></button>
          <h2 id="week-label" style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--navy)"></h2>
          <button class="btn btn-outline" onclick="changeWeek(1)"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="card">
          <div class="card-body" style="padding:0;overflow-x:auto">
            <div id="week-grid"></div>
          </div>
        </div>
      </div>

      <!-- List view -->
      <div id="list-view">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
          <button class="btn btn-outline" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
          <h2 id="month-label" style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--navy)"></h2>
          <button class="btn btn-outline" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="card">
          <div class="card-body" style="padding:0" id="list-content">
            <div style="padding:2rem;text-align:center"><span class="spinner"></span></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
  .week-grid {
    display: grid;
    grid-template-columns: 60px repeat(7, 1fr);
    min-width: 700px;
  }

  .week-header {
    background: var(--bg);
  }

  .week-cell {
    border: 1px solid var(--border);
    min-height: 60px;
    padding: .3rem;
    position: relative;
  }

  .week-time {
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    padding: .3rem .5rem 0 0;
    font-size: .7rem;
    color: var(--slate);
    border-right: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    height: 60px;
  }

  .sched-block {
    border-radius: 5px;
    padding: 3px 6px;
    font-size: .7rem;
    font-weight: 600;
    margin-bottom: 2px;
    color: var(--white);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }
</style>

<script>
  const API = BASE_URL + '/backend/student/student_api.php';
  let viewMode = 'list';
  let calYear = new Date().getFullYear();
  let calMonth = new Date().getMonth();
  let weekStart = getMonday(new Date());

  function localDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function getMonday(d) {
    d = new Date(d);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    d.setDate(diff);
    d.setHours(0, 0, 0, 0);
    return d;
  }

  function setView(mode, btn) {
    viewMode = mode;
    document.querySelectorAll('.view-btn').forEach(b => b.className = 'btn btn-outline btn-sm view-btn');
    btn.className = 'btn btn-primary btn-sm view-btn';
    document.getElementById('week-view').style.display = mode === 'week' ? 'block' : 'none';
    document.getElementById('list-view').style.display = mode === 'list' ? 'block' : 'none';
    if (mode === 'week') renderWeek();
    else loadList();
  }

  function changeWeek(dir) {
    weekStart.setDate(weekStart.getDate() + dir * 7);
    renderWeek();
  }

  function changeMonth(dir) {
    calMonth += dir;
    if (calMonth < 0) {
      calMonth = 11;
      calYear--;
    }
    if (calMonth > 11) {
      calMonth = 0;
      calYear++;
    }
    loadList();
  }

  const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

  async function loadList() {
    const start = `${calYear}-${String(calMonth+1).padStart(2,'0')}-01T00:00:00`;
    const end = `${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(new Date(calYear,calMonth+1,0).getDate()).padStart(2,'0')}T23:59:59`;
    document.getElementById('month-label').textContent = `${monthNames[calMonth]} ${calYear}`;
    const res = await fetch(`${API}?action=get_schedules&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`);
    const data = await res.json();
    const el = document.getElementById('list-content');
    const typeColors = {
      Class: '#1e3a5f',
      Study: '#3b82f6',
      Personal: '#22c55e',
      Meeting: '#f97316'
    };

    if (!data.schedules.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-calendar"></i><p>No schedules this month. Use the Planner to add some!</p></div>';
      return;
    }

    // Group by date
    const grouped = {};
    data.schedules.forEach(s => {
      const d = s.starts_at.split(' ')[0];
      if (!grouped[d]) grouped[d] = [];
      grouped[d].push(s);
    });

    el.innerHTML = Object.entries(grouped).sort().map(([date, items]) => {
      const d = new Date(date + 'T00:00:00');
      return `<div>
      <div style="padding:.6rem 1.5rem;background:var(--bg);border-bottom:1px solid var(--border);font-size:.8rem;font-weight:700;color:var(--slate)">
        ${d.toLocaleDateString('en-PH',{weekday:'long',month:'long',day:'numeric'})}
      </div>
      ${items.map(s => {
        const color = s.color || typeColors[s.type] || '#1e3a5f';
        return `<div style="padding:.9rem 1.5rem;border-bottom:1px solid var(--border);display:flex;gap:1rem;align-items:flex-start">
          <div style="width:4px;background:${color};border-radius:4px;align-self:stretch;flex-shrink:0"></div>
          <div style="flex:1">
            <div style="font-weight:600">${s.title}</div>
            <div style="color:var(--slate);font-size:.8rem;margin-top:.2rem">
              ${new Date(s.starts_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})} –
              ${new Date(s.ends_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})}
            </div>
            <span class="badge" style="background:${color}22;color:${color};margin-top:.3rem">${s.type}</span>
          </div>
        </div>`;
      }).join('')}
    </div>`;
    }).join('');
  }

  async function renderWeek() {
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    const startStr = localDateString(weekStart) + 'T00:00:00';
    const endStr = localDateString(weekEnd) + 'T23:59:59';
    document.getElementById('week-label').textContent =
      `${weekStart.toLocaleDateString('en-PH',{month:'short',day:'numeric'})} – ${weekEnd.toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'})}`;
    const res = await fetch(`${API}?action=get_schedules&start=${encodeURIComponent(startStr)}&end=${encodeURIComponent(endStr)}`);
    const data = await res.json();
    const scheds = data.schedules || [];

    // Build day columns
    const days = Array.from({
      length: 7
    }, (_, i) => {
      const d = new Date(weekStart);
      d.setDate(d.getDate() + i);
      return d;
    });

    const hours = Array.from({
      length: 14
    }, (_, i) => i + 7); // 7am to 8pm

    let html = '<div class="week-grid">';
    // Header row
    html += '<div class="week-cell week-header" style="border:none"></div>';
    days.forEach((d, i) => {
      const today = d.toDateString() === new Date().toDateString();
      html += `<div class="week-cell week-header" style="text-align:center;padding:.6rem;border:1px solid var(--border);${today?'background:#fffbeb;':''}">
      <div style="font-size:.7rem;font-weight:700;color:var(--slate)">${dayNames[i]}</div>
      <div style="font-size:1rem;font-weight:${today?'700':'400'};color:${today?'var(--gold)':'var(--navy)'}">${d.getDate()}</div>
    </div>`;
    });

    // Hour rows
    hours.forEach(h => {
      html += `<div class="week-time">${h % 12 || 12}${h < 12 ? 'am' : 'pm'}</div>`;
      days.forEach(d => {
        const dateStr = localDateString(d);
        const hourScheds = scheds.filter(s => {
          const sh = new Date(s.starts_at);
          return s.starts_at.startsWith(dateStr) && sh.getHours() === h;
        });
        html += `<div class="week-cell">
        ${hourScheds.map(s => `<div class="sched-block" style="background:${s.color||'#1e3a5f'}">${s.title}</div>`).join('')}
      </div>`;
      });
    });

    html += '</div>';
    document.getElementById('week-grid').innerHTML = html;
  }

  loadList();
  loadNotifCount();
</script>
<?php layout_footer(); ?>