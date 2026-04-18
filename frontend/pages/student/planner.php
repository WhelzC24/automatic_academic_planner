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
        <button class="btn btn-primary" id="add-schedule-btn" onclick="openAddScheduleModal()">
          <i class="fas fa-plus"></i> Add Schedule
        </button>
      </div>
    </div>
    <div class="page-content">
      <div class="planner-tabs">
        <button class="btn btn-sm planner-tab-btn active" id="tab-personal" onclick="setPlannerTab('personal')">
          <i class="fas fa-user-clock"></i> My Schedules
        </button>
        <button class="btn btn-outline btn-sm planner-tab-btn" id="tab-readonly" onclick="setPlannerTab('readonly')">
          <i class="fas fa-book-open"></i> Class Schedules (Read-Only)
        </button>
      </div>

      <div class="planner-layout" id="personal-schedule-view">
        <div class="card planner-calendar-card">
          <div class="card-header">
            <button class="btn btn-sm btn-outline" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
            <div class="card-title" id="cal-month-label"></div>
            <button class="btn btn-sm btn-outline" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
          </div>
          <div class="card-body planner-calendar-body">
            <div id="cal-grid"></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-list-ul"></i> <span id="schedule-date-label">Schedules</span></div>
            <button class="btn btn-sm btn-outline" onclick="loadSchedules()"><i class="fas fa-sync"></i></button>
          </div>
          <div class="card-body planner-list-body">
            <div id="schedule-list">
              <div class="planner-loading"><span class="spinner"></span></div>
            </div>
          </div>
        </div>
      </div>

      <div id="readonly-schedule-view" style="display:none">
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-book-reader"></i> Instructor-Managed Class Schedules</div>
            <button class="btn btn-sm btn-outline" onclick="loadReadonlySchedules()"><i class="fas fa-sync"></i></button>
          </div>
          <div class="card-body planner-list-body">
            <div id="readonly-schedule-list">
              <div class="planner-loading"><span class="spinner"></span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="add-schedule-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Schedule</div>
      <button class="modal-close" onclick="closeAddScheduleModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Title *</label>
        <input type="text" class="form-control" id="schedule-title" placeholder="e.g., Study Session">
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea class="form-control" id="schedule-description" placeholder="Optional notes"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Start *</label>
          <input type="datetime-local" class="form-control" id="schedule-start">
        </div>
        <div class="form-group">
          <label>End *</label>
          <input type="datetime-local" class="form-control" id="schedule-end">
        </div>
      </div>
      <div class="form-row planner-modal-form-row">
        <div class="form-group">
          <label>Type</label>
          <select class="form-control" id="schedule-type">
            <option value="Class">Class</option>
            <option value="Study">Study</option>
            <option value="Personal" selected>Personal</option>
            <option value="Meeting">Meeting</option>
          </select>
        </div>
        <div class="form-group">
          <label>Color</label>
          <input type="color" class="form-control planner-color-input" id="schedule-color" value="#4f46e5">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeAddScheduleModal()">Cancel</button>
      <button class="btn btn-primary" id="add-schedule-save" onclick="saveSchedule()">
        <i class="fas fa-save"></i> Save Schedule
      </button>
    </div>
  </div>
</div>

<style>
  .planner-layout {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 1.5rem;
    align-items: start;
  }

  .planner-tabs {
    display: flex;
    gap: .5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
  }

  .planner-tab-btn.active {
    background: var(--deep);
    color: var(--white);
    border-color: var(--deep);
  }

  .planner-calendar-card {
    min-width: 300px;
  }

  .planner-calendar-body {
    padding: 1rem;
  }

  .planner-list-body {
    padding: 0;
  }

  .planner-loading {
    padding: 2rem;
    text-align: center;
  }

  .planner-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    gap: 2px;
  }

  .planner-weekday {
    font-size: .7rem;
    font-weight: 700;
    color: var(--slate);
    padding: .3rem;
  }

  .planner-day {
    padding: .35rem;
    border-radius: 50%;
    cursor: pointer;
    font-size: .82rem;
    transition: all .15s;
    position: relative;
  }

  .planner-day-dot {
    position: absolute;
    bottom: 1px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--gold);
    display: block;
  }

  .planner-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    gap: 1rem;
    align-items: flex-start;
  }

  .planner-item-accent {
    width: 4px;
    border-radius: 4px;
    flex-shrink: 0;
    align-self: stretch;
  }

  .planner-item-content {
    flex: 1;
  }

  .planner-item-title {
    font-weight: 700;
    font-size: .95rem;
  }

  .planner-item-time {
    color: var(--slate);
    font-size: .8rem;
    margin-top: .2rem;
  }

  .planner-item-badge-wrap {
    margin-top: .3rem;
  }

  .planner-item-desc {
    color: var(--slate);
    font-size: .78rem;
    margin-top: .3rem;
  }

  .planner-readonly-item {
    padding: 1rem 1.2rem;
    border-bottom: 1px solid var(--border);
  }

  .planner-readonly-head {
    display: flex;
    justify-content: space-between;
    gap: .75rem;
    align-items: center;
    margin-bottom: .3rem;
  }

  .planner-readonly-title {
    font-weight: 700;
    color: var(--navy);
  }

  .planner-readonly-meta {
    display: flex;
    gap: .8rem;
    flex-wrap: wrap;
    color: var(--slate);
    font-size: .8rem;
  }

  .planner-readonly-section {
    padding: 1rem 1.25rem 0;
  }

  .planner-readonly-section-title {
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--slate);
    font-weight: 700;
    margin-bottom: .6rem;
  }

  .planner-modal-form-row {
    align-items: end;
  }

  .planner-color-input {
    padding: .35rem;
    min-height: 42px;
  }

  @media (max-width: 980px) {
    .planner-layout {
      grid-template-columns: 1fr;
    }
  }
</style>


<script>
  const API = BASE_URL + '/backend/student/student_api.php';
  const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const WEEKDAY_NAMES = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

  let calYear = new Date().getFullYear();
  let calMonth = new Date().getMonth();
  let selectedDate = null;
  let plannerTab = 'personal';
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

  function setPlannerTab(tab) {
    plannerTab = tab;
    const personalBtn = document.getElementById('tab-personal');
    const readonlyBtn = document.getElementById('tab-readonly');
    const personalView = document.getElementById('personal-schedule-view');
    const readonlyView = document.getElementById('readonly-schedule-view');
    const addBtn = document.getElementById('add-schedule-btn');

    if (tab === 'readonly') {
      personalBtn.className = 'btn btn-outline btn-sm planner-tab-btn';
      readonlyBtn.className = 'btn btn-sm planner-tab-btn active';
      personalView.style.display = 'none';
      readonlyView.style.display = 'block';
      addBtn.style.display = 'none';
      loadReadonlySchedules();
      return;
    }

    personalBtn.className = 'btn btn-sm planner-tab-btn active';
    readonlyBtn.className = 'btn btn-outline btn-sm planner-tab-btn';
    personalView.style.display = 'grid';
    readonlyView.style.display = 'none';
    addBtn.style.display = 'inline-flex';
  }

  function localDateTimeValue(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  }

  function openAddScheduleModal() {
    const now = new Date();
    now.setSeconds(0, 0);
    const start = new Date(now);
    const end = new Date(now.getTime() + 60 * 60 * 1000);

    if (selectedDate) {
      const selected = new Date(`${selectedDate}T09:00:00`);
      if (!Number.isNaN(selected.getTime())) {
        start.setFullYear(selected.getFullYear(), selected.getMonth(), selected.getDate());
        start.setHours(9, 0, 0, 0);
        end.setFullYear(selected.getFullYear(), selected.getMonth(), selected.getDate());
        end.setHours(10, 0, 0, 0);
      }
    }

    document.getElementById('schedule-title').value = '';
    document.getElementById('schedule-description').value = '';
    document.getElementById('schedule-start').value = localDateTimeValue(start);
    document.getElementById('schedule-end').value = localDateTimeValue(end);
    document.getElementById('schedule-type').value = 'Personal';
    document.getElementById('schedule-color').value = '#4f46e5';
    document.getElementById('add-schedule-modal').classList.add('show');
    document.getElementById('schedule-title').focus();
  }

  function closeAddScheduleModal() {
    document.getElementById('add-schedule-modal').classList.remove('show');
  }

  async function saveSchedule() {
    const title = document.getElementById('schedule-title').value.trim();
    const description = document.getElementById('schedule-description').value.trim();
    const startsAt = document.getElementById('schedule-start').value;
    const endsAt = document.getElementById('schedule-end').value;
    const type = document.getElementById('schedule-type').value;
    const color = document.getElementById('schedule-color').value;

    if (!title || !startsAt || !endsAt) {
      toast('Please complete all required fields.', 'error');
      return;
    }

    const startDate = new Date(startsAt);
    const endDate = new Date(endsAt);
    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime()) || endDate <= startDate) {
      toast('End time must be later than start time.', 'error');
      return;
    }

    const saveBtn = document.getElementById('add-schedule-save');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner"></span> Saving...';

    const fd = new FormData();
    fd.append('action', 'add_schedule');
    fd.append('title', title);
    fd.append('description', description);
    fd.append('starts_at', startsAt);
    fd.append('ends_at', endsAt);
    fd.append('type', type);
    fd.append('color', color);

    try {
      const res = await fetch(API, {
        method: 'POST',
        body: fd
      });
      const data = await res.json();
      if (data.success) {
        toast(data.message || 'Schedule added.');
        closeAddScheduleModal();
        await loadSchedules();
      } else {
        toast(data.message || 'Unable to add schedule.', 'error');
      }
    } catch (error) {
      toast('Unable to add schedule right now.', 'error');
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Schedule';
    }
  }

  async function loadReadonlySchedules() {
    const el = document.getElementById('readonly-schedule-list');
    el.innerHTML = '<div class="planner-loading"><span class="spinner"></span></div>';
    try {
      const res = await fetch(`${API}?action=get_readonly_schedules`);
      const data = await res.json();
      const offerings = data.offerings || [];
      const events = data.events || [];

      if (!offerings.length && !events.length) {
        el.innerHTML = '<div class="empty-state"><i class="fas fa-calendar"></i><p>No instructor-managed schedules yet.</p></div>';
        return;
      }

      const classSchedules = offerings.length ? offerings.map((o) => {
        const instructorName = (o.first_name && o.last_name) ? `${o.first_name} ${o.last_name}` : 'TBA';
        const scheduleLabel = o.schedule && o.schedule.trim() ? o.schedule : 'Not set yet';
        const roomLabel = o.room && o.room.trim() ? o.room : 'TBA';
        return `<div class="planner-readonly-item">
          <div class="planner-readonly-head">
            <div class="planner-readonly-title">${o.course_code} — ${o.course_title}</div>
            <span class="badge badge-submitted">${o.section}</span>
          </div>
          <div class="planner-readonly-meta">
            <span><i class="fas fa-calendar-alt"></i> ${scheduleLabel}</span>
            <span><i class="fas fa-door-open"></i> ${roomLabel}</span>
            <span><i class="fas fa-user"></i> ${instructorName}</span>
            <span><i class="fas fa-tag"></i> ${o.term}</span>
          </div>
        </div>`;
      }).join('') : '<div class="empty-state" style="padding:1.5rem"><i class="fas fa-calendar-alt"></i><p>No class schedules published yet.</p></div>';

      const eventSchedules = events.length ? events.map((eventItem) => {
        const color = eventItem.color || '#1e3a5f';
        return `<div class="planner-readonly-item">
          <div class="planner-readonly-head">
            <div class="planner-readonly-title">${eventItem.title}</div>
            <span class="badge" style="background:${color}22;color:${color}">${eventItem.type}</span>
          </div>
          <div class="planner-readonly-meta">
            <span><i class="fas fa-clock"></i> ${new Date(eventItem.starts_at).toLocaleString('en-PH',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})} – ${new Date(eventItem.ends_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})}</span>
          </div>
          ${eventItem.description ? `<div class="planner-item-desc">${eventItem.description}</div>` : ''}
        </div>`;
      }).join('') : '<div class="empty-state" style="padding:1.5rem"><i class="fas fa-bell"></i><p>No exam or activity schedules published yet.</p></div>';

      el.innerHTML = `
        <div class="planner-readonly-section">
          <div class="planner-readonly-section-title">Class Schedules</div>
          ${classSchedules}
        </div>
        <div class="planner-readonly-section" style="padding-bottom:1rem">
          <div class="planner-readonly-section-title">Exams / Activities</div>
          ${eventSchedules}
        </div>
      `;
    } catch (error) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Unable to load class schedules right now.</p></div>';
    }
  }

  function renderCalendar() {
    document.getElementById('cal-month-label').textContent = `${MONTH_NAMES[calMonth]} ${calYear}`;

    const firstDay = new Date(calYear, calMonth, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
    const today = new Date();

    let html = '<div class="planner-calendar-grid">';
    WEEKDAY_NAMES.forEach(day => {
      html += `<div class="planner-weekday">${day}</div>`;
    });
    for (let i = 0; i < firstDay; i++) html += '<div></div>';

    for (let d = 1; d <= daysInMonth; d++) {
      const thisDate = new Date(calYear, calMonth, d);
      const dateStr = localDateString(thisDate);
      const isToday = today.getDate() === d && today.getMonth() === calMonth && today.getFullYear() === calYear;
      const isSelected = selectedDate === dateStr;
      const hasSched = allSchedules.some(s => s.starts_at.startsWith(dateStr));

      const fontWeight = isToday ? '700' : '400';
      const background = isSelected ? 'var(--deep)' : (isToday ? '#fef9c3' : 'transparent');
      const color = isSelected ? 'var(--white)' : (isToday ? 'var(--navy)' : 'inherit');

      html += `<div class="planner-day" onclick="selectDay('${dateStr}')" style="
        font-weight:${fontWeight};
        background:${background};
        color:${color};
      ">
      ${d}
      ${hasSched ? '<span class="planner-day-dot"></span>' : ''}
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
    try {
      const res = await fetch(`${API}?action=get_schedules&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`);
      const data = await res.json();
      allSchedules = data.schedules || [];
      renderCalendar();
      if (selectedDate) selectDay(selectedDate);
      else renderScheduleList(allSchedules);
    } catch (error) {
      renderScheduleList([]);
    }
  }

  function renderScheduleList(items) {
    const el = document.getElementById('schedule-list');
    if (!items.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-calendar"></i><p>No course schedules found.</p></div>';
      return;
    }
    el.innerHTML = items.map(s => {
      const color = s.color || typeColors[s.type] || '#1e3a5f';
      return `<div class="planner-item">
      <div class="planner-item-accent" style="background:${color}"></div>
      <div class="planner-item-content">
        <div class="planner-item-title">${s.title}</div>
        <div class="planner-item-time">
          <i class="fas fa-clock"></i>
          ${new Date(s.starts_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})} –
          ${new Date(s.ends_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})}
        </div>
        <div class="planner-item-badge-wrap">
          <span class="badge" style="background:${color}22;color:${color}">${s.type}</span>
        </div>
        ${s.description ? `<div class="planner-item-desc">${s.description}</div>` : ''}
      </div>
    </div>`;
    }).join('');
  }

  // Class schedules may be auto-generated; students can add personal schedules.

  renderCalendar();
  loadSchedules();
  loadNotifCount();
</script>
<?php layout_footer(); ?>