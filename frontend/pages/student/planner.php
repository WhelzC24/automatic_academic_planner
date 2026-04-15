<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('student');
require_once __DIR__ . '/../layout.php';
layout_header('Planner & Schedule', 'student');
?>
<div class="app-shell">
  <?php layout_sidebar('student', 'planner'); ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1>Daily Planner</h1>
        <p>Organize your schedule and study sessions</p>
      </div>
      <div class="topbar-actions">
        <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Schedule</button>
      </div>
    </div>
    <div class="page-content">
      <!-- Mini Calendar -->
      <div style="display:grid;grid-template-columns:auto 1fr;gap:1.5rem;align-items:start">
        <div class="card" style="min-width:300px">
          <div class="card-header">
            <button class="btn btn-sm btn-outline" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
            <div class="card-title" id="cal-month-label"></div>
            <button class="btn btn-sm btn-outline" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
          </div>
          <div class="card-body" style="padding:1rem">
            <div id="cal-grid"></div>
          </div>
        </div>

        <!-- Schedule List -->
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-list-ul"></i> <span id="schedule-date-label">Schedules</span></div>
            <button class="btn btn-sm btn-outline" onclick="loadSchedules()"><i class="fas fa-sync"></i></button>
          </div>
          <div class="card-body" style="padding:0">
            <div id="schedule-list">
              <div style="padding:2rem;text-align:center"><span class="spinner"></span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal-overlay" id="sched-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Schedule</div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Title *</label>
        <input type="text" class="form-control" id="s-title" placeholder="e.g. Math Class">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Starts At *</label>
          <input type="datetime-local" class="form-control" id="s-start">
        </div>
        <div class="form-group">
          <label>Ends At *</label>
          <input type="datetime-local" class="form-control" id="s-end">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Type</label>
          <select class="form-control" id="s-type">
            <option>Class</option>
            <option>Study</option>
            <option>Personal</option>
            <option>Meeting</option>
          </select>
        </div>
        <div class="form-group">
          <label>Color</label>
          <input type="color" class="form-control" id="s-color" value="#1e3a5f">
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea class="form-control" id="s-desc" placeholder="Optional notes..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="saveSchedule()"><i class="fas fa-save"></i> Save</button>
    </div>
  </div>
</div>

<script>
  const API = BASE_URL + '/backend/student/student_api.php';
  let calYear = new Date().getFullYear();
  let calMonth = new Date().getMonth();
  let selectedDate = null;
  let allSchedules = [];

  const typeColors = {
    Class: '#1e3a5f',
    Study: '#3b82f6',
    Personal: '#22c55e',
    Meeting: '#f97316'
  };

  function localDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function localDateTimeValue(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  }

  function renderCalendar() {
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('cal-month-label').textContent = `${months[calMonth]} ${calYear}`;

    const firstDay = new Date(calYear, calMonth, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
    const today = new Date();

    let html = '<div style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center;gap:2px">';
    ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].forEach(d => {
      html += `<div style="font-size:.7rem;font-weight:700;color:var(--slate);padding:.3rem">${d}</div>`;
    });
    for (let i = 0; i < firstDay; i++) html += '<div></div>';
    for (let d = 1; d <= daysInMonth; d++) {
      const thisDate = new Date(calYear, calMonth, d);
      const dateStr = localDateString(thisDate);
      const isToday = today.getDate() === d && today.getMonth() === calMonth && today.getFullYear() === calYear;
      const isSel = selectedDate === dateStr;
      const hasSched = allSchedules.some(s => s.starts_at.startsWith(dateStr));
      html += `<div onclick="selectDay('${dateStr}')" style="
        padding:.35rem;
        border-radius:50%;
        cursor:pointer;
        font-size:.82rem;
        font-weight:${isToday?'700':'400'};
        background:${isSel?'var(--deep)':isToday?'#fef9c3':'transparent'};
        color:${isSel?'var(--white)':isToday?'var(--navy)':'inherit'};
        transition:all .15s;
        position:relative;
      ">
      ${d}
      ${hasSched ? `<span style="position:absolute;bottom:1px;left:50%;transform:translateX(-50%);width:4px;height:4px;border-radius:50%;background:var(--gold);display:block"></span>` : ''}
    </div>`;
    }
    html += '</div>';
    document.getElementById('cal-grid').innerHTML = html;
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
    renderCalendar();
    loadSchedules();
  }

  function selectDay(dateStr) {
    selectedDate = dateStr;
    renderCalendar();
    document.getElementById('schedule-date-label').textContent =
      'Schedules — ' + new Date(dateStr + 'T00:00:00').toLocaleDateString('en-PH', {
        weekday: 'long',
        month: 'long',
        day: 'numeric'
      });
    const filtered = allSchedules.filter(s => s.starts_at.startsWith(dateStr));
    renderScheduleList(filtered);
  }

  async function loadSchedules() {
    const start = `${calYear}-${String(calMonth+1).padStart(2,'0')}-01T00:00:00`;
    const end = `${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(new Date(calYear,calMonth+1,0).getDate()).padStart(2,'0')}T23:59:59`;
    const res = await fetch(`${API}?action=get_schedules&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`);
    const data = await res.json();
    allSchedules = data.schedules || [];
    renderCalendar();
    if (selectedDate) selectDay(selectedDate);
    else renderScheduleList(allSchedules);
  }

  function renderScheduleList(items) {
    const el = document.getElementById('schedule-list');
    if (!items.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-plus"></i><p>No schedules here. Click a day or add one!</p></div>';
      return;
    }
    el.innerHTML = items.map(s => {
      const color = s.color || typeColors[s.type] || '#1e3a5f';
      return `<div style="padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;gap:1rem;align-items:flex-start;">
      <div style="width:4px;background:${color};border-radius:4px;flex-shrink:0;align-self:stretch"></div>
      <div style="flex:1">
        <div style="font-weight:700;font-size:.95rem">${s.title}</div>
        <div style="color:var(--slate);font-size:.8rem;margin-top:.2rem">
          <i class="fas fa-clock"></i>
          ${new Date(s.starts_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})} –
          ${new Date(s.ends_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})}
        </div>
        <div style="margin-top:.3rem">
          <span class="badge" style="background:${color}22;color:${color}">${s.type}</span>
        </div>
        ${s.description ? `<div style="color:var(--slate);font-size:.78rem;margin-top:.3rem">${s.description}</div>` : ''}
      </div>
      <button class="btn btn-sm btn-danger" onclick="deleteSchedule(${s.schedule_id})"><i class="fas fa-trash"></i></button>
    </div>`;
    }).join('');
  }

  function openModal() {
    const now = new Date();
    now.setMinutes(0);
    const dt = localDateTimeValue(now);
    document.getElementById('s-title').value = '';
    document.getElementById('s-start').value = selectedDate ? selectedDate + 'T08:00' : dt;
    document.getElementById('s-end').value = selectedDate ? selectedDate + 'T09:00' : dt;
    document.getElementById('s-desc').value = '';
    document.getElementById('sched-modal').classList.add('show');
  }

  function closeModal() {
    document.getElementById('sched-modal').classList.remove('show');
  }

  async function saveSchedule() {
    const title = document.getElementById('s-title').value.trim();
    const start = document.getElementById('s-start').value;
    const end = document.getElementById('s-end').value;
    if (!title || !start || !end) {
      toast('Please fill in all required fields.', 'error');
      return;
    }
    const fd = new FormData();
    fd.append('action', 'add_schedule');
    fd.append('title', title);
    fd.append('starts_at', start);
    fd.append('ends_at', end);
    fd.append('type', document.getElementById('s-type').value);
    fd.append('color', document.getElementById('s-color').value);
    fd.append('description', document.getElementById('s-desc').value);
    const res = await fetch(API, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      toast(data.message);
      closeModal();
      loadSchedules();
    } else toast(data.message, 'error');
  }

  async function deleteSchedule(id) {
    if (!confirm('Delete this schedule?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_schedule');
    fd.append('schedule_id', id);
    const res = await fetch(API, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      toast('Schedule deleted.');
      loadSchedules();
    }
  }

  renderCalendar();
  loadSchedules();
  loadNotifCount();
</script>
<?php layout_footer(); ?>