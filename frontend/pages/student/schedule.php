<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('student');
require_once __DIR__ . '/../layout.php';
$currentUserId = (int)$_SESSION['user_id'];
layout_header('My Schedule', 'student');
?>
<div class="app-shell" data-user-id="<?= $currentUserId ?>">
  <?php layout_sidebar('student', 'schedule'); ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1>My Schedule</h1>
        <p>Manage your personal schedules and view class events</p>
      </div>
      <div class="topbar-actions">
        <button class="btn btn-primary" id="add-schedule-top-btn" onclick="openAddScheduleModal()">
          <i class="fas fa-plus"></i> Add Schedule
        </button>
      </div>
    </div>
    <div class="page-content">

      <!-- Tabs -->
      <div class="schedule-tabs">
        <button class="btn btn-sm schedule-tab-btn active" id="tab-personal" onclick="setScheduleTab('personal')">
          <i class="fas fa-user-clock"></i> My Schedules
        </button>
        <button class="btn btn-outline btn-sm schedule-tab-btn" id="tab-week" onclick="setScheduleTab('week')">
          <i class="fas fa-th"></i> Week View
        </button>
        <button class="btn btn-outline btn-sm schedule-tab-btn" id="tab-classes" onclick="setScheduleTab('classes')">
          <i class="fas fa-book-open"></i> Class Schedules
        </button>
      </div>

      <!-- Tab 1: Personal Schedules with Calendar -->
      <div class="schedule-layout" id="personal-view">
        <div class="card schedule-calendar-card">
          <div class="card-header">
            <button class="btn btn-sm btn-outline" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
            <div class="card-title" id="cal-month-label"></div>
            <button class="btn btn-sm btn-outline" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
          </div>
          <div class="card-body schedule-calendar-body">
            <div id="cal-grid"></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-list-ul"></i> <span id="schedule-date-label">All Schedules</span></div>
            <button class="btn btn-sm btn-outline" onclick="loadSchedules()"><i class="fas fa-sync"></i></button>
          </div>
          <div class="card-body schedule-list-body">
            <div id="schedule-list">
              <div class="schedule-loading"><span class="spinner"></span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 2: Week View -->
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

      <!-- Tab 3: Class Schedules (Read-Only) -->
      <div id="classes-view" style="display:none">
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-book-reader"></i> Instructor-Managed Class Schedules</div>
            <button class="btn btn-sm btn-outline" onclick="loadReadonlySchedules()"><i class="fas fa-sync"></i></button>
          </div>
          <div class="card-body schedule-list-body">
            <div id="readonly-schedule-list">
              <div class="schedule-loading"><span class="spinner"></span></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
  .schedule-tabs {
    display: flex;
    gap: .5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
  }

  .schedule-tab-btn.active {
    background: var(--deep);
    color: var(--white);
    border-color: var(--deep);
  }

  .schedule-layout {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 1.5rem;
    align-items: start;
  }

  .schedule-calendar-card {
    min-width: 300px;
  }

  .schedule-calendar-body {
    padding: 1rem;
  }

  .schedule-list-body {
    padding: 0;
  }

  .schedule-loading {
    padding: 2rem;
    text-align: center;
  }

  .schedule-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    gap: 2px;
  }

  .schedule-weekday {
    font-size: .7rem;
    font-weight: 700;
    color: var(--slate);
    padding: .3rem;
  }

  .schedule-day {
    padding: .35rem;
    border-radius: 50%;
    cursor: pointer;
    font-size: .82rem;
    transition: all .15s;
    position: relative;
  }

  .schedule-day-dot {
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

  .schedule-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    gap: 1rem;
    align-items: flex-start;
  }

  .schedule-item-accent {
    width: 4px;
    border-radius: 4px;
    flex-shrink: 0;
    align-self: stretch;
  }

  .schedule-item-content {
    flex: 1;
  }

  .schedule-item-title {
    font-weight: 700;
    font-size: .95rem;
  }

  .schedule-item-time {
    color: var(--slate);
    font-size: .8rem;
    margin-top: .2rem;
  }

  .schedule-item-badge-wrap {
    margin-top: .3rem;
  }

  .schedule-item-desc {
    color: var(--slate);
    font-size: .78rem;
    margin-top: .3rem;
  }

  .schedule-readonly-item {
    padding: 1rem 1.2rem;
    border-bottom: 1px solid var(--border);
  }

  .schedule-readonly-head {
    display: flex;
    justify-content: space-between;
    gap: .75rem;
    align-items: center;
    margin-bottom: .3rem;
  }

  .schedule-readonly-title {
    font-weight: 700;
    color: var(--navy);
  }

  .schedule-readonly-meta {
    display: flex;
    gap: .8rem;
    flex-wrap: wrap;
    color: var(--slate);
    font-size: .8rem;
  }

  .schedule-readonly-section {
    padding: 1rem 1.25rem 0;
  }

  .schedule-readonly-section-title {
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--slate);
    font-weight: 700;
    margin-bottom: .6rem;
  }

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

  @media (max-width: 980px) {
    .schedule-layout {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="modal-overlay" id="add-schedule-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="add-schedule-modal-title">Add Schedule</div>
      <button class="modal-close" onclick="closeAddScheduleModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="schedule-id">
      <div class="form-row">
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
          <input type="color" class="form-control" id="schedule-color" value="#22c55e">
        </div>
      </div>
      <div class="form-group">
        <label>Title *</label>
        <input type="text" class="form-control" id="schedule-title" placeholder="e.g., Math Study Group">
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
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeAddScheduleModal()">Cancel</button>
      <button class="btn btn-primary" id="save-schedule-btn" onclick="saveSchedule()">
        <i class="fas fa-save"></i> Save
      </button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="delete-schedule-modal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <div class="modal-title">Delete Schedule</div>
      <button class="modal-close" onclick="closeDeleteScheduleModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="delete-schedule-id">
      <p style="color:var(--slate);font-size:.9rem;line-height:1.6">
        Are you sure you want to delete <strong id="delete-schedule-name"></strong>?<br>
        This action cannot be undone.
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeDeleteScheduleModal()">Cancel</button>
      <button class="btn btn-danger" id="confirm-delete-schedule-btn" onclick="confirmDeleteSchedule()">
        <i class="fas fa-trash"></i> Delete
      </button>
    </div>
  </div>
</div>

<script>
const API = BASE_URL + '/backend/student/student_api.php';
const CURRENT_USER_ID = parseInt(document.querySelector('.app-shell').dataset.userId, 10);
const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const WEEKDAY_NAMES = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

let calYear = new Date().getFullYear();
let calMonth = new Date().getMonth();
let selectedDate = null;
let scheduleTab = 'personal';
let allSchedules = [];
let schedulesMap = {};
let weekStart = getMonday(new Date());

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

function getMonday(d) {
  d = new Date(d);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  d.setDate(diff);
  d.setHours(0, 0, 0, 0);
  return d;
}

function setScheduleTab(tab) {
  scheduleTab = tab;
  const personalBtn = document.getElementById('tab-personal');
  const weekBtn = document.getElementById('tab-week');
  const classesBtn = document.getElementById('tab-classes');
  const personalView = document.getElementById('personal-view');
  const weekView = document.getElementById('week-view');
  const classesView = document.getElementById('classes-view');
  const topBtn = document.getElementById('add-schedule-top-btn');

  personalBtn.className = 'btn btn-outline btn-sm schedule-tab-btn';
  weekBtn.className = 'btn btn-outline btn-sm schedule-tab-btn';
  classesBtn.className = 'btn btn-outline btn-sm schedule-tab-btn';
  personalView.style.display = 'none';
  weekView.style.display = 'none';
  classesView.style.display = 'none';
  topBtn.style.display = 'none';

  if (tab === 'personal') {
    personalBtn.className = 'btn btn-sm schedule-tab-btn active';
    personalView.style.display = 'grid';
    topBtn.style.display = 'inline-flex';
  } else if (tab === 'week') {
    weekBtn.className = 'btn btn-sm schedule-tab-btn active';
    weekView.style.display = 'block';
    topBtn.style.display = 'inline-flex';
    renderWeek();
  } else if (tab === 'classes') {
    classesBtn.className = 'btn btn-sm schedule-tab-btn active';
    classesView.style.display = 'block';
    loadReadonlySchedules();
  }
}

function changeMonth(dir) {
  calMonth += dir;
  if (calMonth < 0) { calMonth = 11; calYear--; }
  if (calMonth > 11) { calMonth = 0; calYear++; }
  renderCalendar();
  loadSchedules();
}

function changeWeek(dir) {
  weekStart.setDate(weekStart.getDate() + dir * 7);
  renderWeek();
}

// ==================== CALENDAR & SCHEDULE LIST ====================

function renderCalendar() {
  document.getElementById('cal-month-label').textContent = `${MONTH_NAMES[calMonth]} ${calYear}`;

  const firstDay = new Date(calYear, calMonth, 1).getDay();
  const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
  const today = new Date();

  let html = '<div class="schedule-calendar-grid">';
  WEEKDAY_NAMES.forEach(day => {
    html += `<div class="schedule-weekday">${day}</div>`;
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

    html += `<div class="schedule-day" onclick="selectDay('${dateStr}')" style="
      font-weight:${fontWeight};
      background:${background};
      color:${color};
    ">
    ${d}
    ${hasSched ? '<span class="schedule-day-dot"></span>' : ''}
  </div>`;
  }
  html += '</div>';
  document.getElementById('cal-grid').innerHTML = html;
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
  renderScheduleList(filtered, true);
}

async function loadSchedules() {
  const start = `${calYear}-${String(calMonth+1).padStart(2,'0')}-01T00:00:00`;
  const end = `${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(new Date(calYear,calMonth+1,0).getDate()).padStart(2,'0')}T23:59:59`;
  try {
    const res = await fetch(`${API}?action=get_schedules&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`);
    const data = await res.json();
    allSchedules = data.schedules || [];
    schedulesMap = {};
    allSchedules.forEach(function(s) { schedulesMap[s.schedule_id] = s; });
    renderCalendar();
    if (selectedDate) selectDay(selectedDate);
    else renderScheduleList(allSchedules, true);
  } catch (error) {
    renderScheduleList([], true);
  }
}

function renderScheduleList(items, showActions) {
  const el = document.getElementById('schedule-list');
  if (!items.length) {
    el.innerHTML = '<div class="empty-state"><i class="fas fa-calendar"></i><p>No schedules found.</p></div>';
    return;
  }

  el.innerHTML = items.map(s => {
    const color = s.color || typeColors[s.type] || '#1e3a5f';
    // Instructor schedules: created_by is set, or type is instructor-managed (legacy)
    const instructorTypes = ['Exam', 'Activity', 'Quiz', 'Presentation', 'Meeting'];
    const isInstructorSchedule = (s.created_by !== null && parseInt(s.created_by) !== CURRENT_USER_ID) ||
                               (s.created_by === null && instructorTypes.includes(s.type));
    const actionsHtml = (showActions && !isInstructorSchedule) ? `
      <div style="display:flex;align-items:center;gap:.35rem">
        <button class="btn btn-xs btn-outline" style="padding:.2rem .45rem;font-size:.68rem" onclick="openEditScheduleModal('${s.schedule_id}')" title="Edit"><i class="fas fa-pen"></i></button>
        <button class="btn btn-xs btn-danger-outline" style="padding:.2rem .45rem;font-size:.68rem" onclick="openDeleteScheduleModal('${s.schedule_id}')" title="Delete"><i class="fas fa-trash"></i></button>
      </div>
    ` : '';
    return `<div class="schedule-item">
      <div class="schedule-item-accent" style="background:${color}"></div>
      <div class="schedule-item-content">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;flex-wrap:wrap">
          <div class="schedule-item-title">${s.title}</div>
          ${actionsHtml}
        </div>
        <div class="schedule-item-time">
          <i class="fas fa-clock"></i>
          ${new Date(s.starts_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})} –
          ${new Date(s.ends_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})}
        </div>
        <div class="schedule-item-badge-wrap">
          <span class="badge" style="background:${color}22;color:${color}">${s.type}</span>
        </div>
        ${s.description ? `<div class="schedule-item-desc">${s.description}</div>` : ''}
      </div>
    </div>`;
  }).join('');
}

// ==================== WEEK VIEW ====================

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

  const days = Array.from({ length: 7 }, (_, i) => {
    const d = new Date(weekStart);
    d.setDate(d.getDate() + i);
    return d;
  });

  const hours = Array.from({ length: 14 }, (_, i) => i + 7);

  let html = '<div class="week-grid">';
  html += '<div class="week-cell week-header" style="border:none"></div>';
  days.forEach((d, i) => {
    const today = d.toDateString() === new Date().toDateString();
    html += `<div class="week-cell week-header" style="text-align:center;padding:.6rem;border:1px solid var(--border);${today?'background:#fffbeb;':''}">
      <div style="font-size:.7rem;font-weight:700;color:var(--slate)">${WEEKDAY_NAMES[i]}</div>
      <div style="font-size:1rem;font-weight:${today?'700':'400'};color:${today?'var(--gold)':'var(--navy)'}">${d.getDate()}</div>
    </div>`;
  });

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

// ==================== READONLY CLASS SCHEDULES ====================

async function loadReadonlySchedules() {
  const el = document.getElementById('readonly-schedule-list');
  el.innerHTML = '<div class="schedule-loading"><span class="spinner"></span></div>';
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
      return `<div class="schedule-readonly-item">
        <div class="schedule-readonly-head">
          <div class="schedule-readonly-title">${o.course_code} — ${o.course_title}</div>
          <span class="badge badge-submitted">${o.section}</span>
        </div>
        <div class="schedule-readonly-meta">
          <span><i class="fas fa-calendar-alt"></i> ${scheduleLabel}</span>
          <span><i class="fas fa-door-open"></i> ${roomLabel}</span>
          <span><i class="fas fa-user"></i> ${instructorName}</span>
          <span><i class="fas fa-tag"></i> ${o.term}</span>
        </div>
      </div>`;
    }).join('') : '<div class="empty-state" style="padding:1.5rem"><i class="fas fa-calendar-alt"></i><p>No class schedules published yet.</p></div>';

    const eventSchedules = events.length ? events.map((eventItem) => {
      const color = eventItem.color || '#1e3a5f';
      return `<div class="schedule-readonly-item">
        <div class="schedule-readonly-head">
          <div class="schedule-readonly-title">${eventItem.title}</div>
          <span class="badge" style="background:${color}22;color:${color}">${eventItem.type}</span>
        </div>
        <div class="schedule-readonly-meta">
          <span><i class="fas fa-clock"></i> ${new Date(eventItem.starts_at).toLocaleString('en-PH',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})} – ${new Date(eventItem.ends_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})}</span>
        </div>
        ${eventItem.description ? `<div class="schedule-item-desc">${eventItem.description}</div>` : ''}
      </div>`;
    }).join('') : '<div class="empty-state" style="padding:1.5rem"><i class="fas fa-bell"></i><p>No exam or activity schedules published yet.</p></div>';

    el.innerHTML = `
      <div class="schedule-readonly-section">
        <div class="schedule-readonly-section-title">Class Schedules</div>
        ${classSchedules}
      </div>
      <div class="schedule-readonly-section" style="padding-bottom:1rem">
        <div class="schedule-readonly-section-title">Exams / Activities</div>
        ${eventSchedules}
      </div>
    `;
  } catch (error) {
    el.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Unable to load class schedules right now.</p></div>';
  }
}

// ==================== ADD/EDIT/DELETE MODAL FUNCTIONS ====================

function openAddScheduleModal() {
  const now = new Date();
  if (selectedDate) {
    const selected = new Date(`${selectedDate}T09:00:00`);
    now.setFullYear(selected.getFullYear(), selected.getMonth(), selected.getDate());
    now.setHours(9, 0, 0, 0);
  } else {
    now.setSeconds(0, 0);
  }
  const end = new Date(now.getTime() + 60 * 60 * 1000);

  const pad = n => String(n).padStart(2, '0');
  document.getElementById('schedule-id').value = '';
  document.getElementById('schedule-title').value = '';
  document.getElementById('schedule-description').value = '';
  document.getElementById('schedule-start').value = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
  document.getElementById('schedule-end').value = `${end.getFullYear()}-${pad(end.getMonth()+1)}-${pad(end.getDate())}T${pad(end.getHours())}:${pad(end.getMinutes())}`;
  document.getElementById('schedule-type').value = 'Personal';
  document.getElementById('schedule-color').value = '#22c55e';
  document.getElementById('add-schedule-modal-title').textContent = 'Add Schedule';
  document.getElementById('add-schedule-modal').classList.add('show');
}

function openEditScheduleModal(schedId) {
  const s = schedulesMap[schedId];
  if (!s) return;
  const pad = n => String(n).padStart(2, '0');
  const startDt = new Date(s.starts_at);
  const endDt = new Date(s.ends_at);

  document.getElementById('schedule-id').value = schedId;
  document.getElementById('schedule-title').value = s.title || '';
  document.getElementById('schedule-description').value = s.description || '';
  document.getElementById('schedule-start').value = `${startDt.getFullYear()}-${pad(startDt.getMonth()+1)}-${pad(startDt.getDate())}T${pad(startDt.getHours())}:${pad(startDt.getMinutes())}`;
  document.getElementById('schedule-end').value = `${endDt.getFullYear()}-${pad(endDt.getMonth()+1)}-${pad(endDt.getDate())}T${pad(endDt.getHours())}:${pad(endDt.getMinutes())}`;
  document.getElementById('schedule-type').value = s.type || 'Personal';
  document.getElementById('schedule-color').value = s.color || '#22c55e';
  document.getElementById('add-schedule-modal-title').textContent = 'Edit Schedule';
  document.getElementById('add-schedule-modal').classList.add('show');
}

function closeAddScheduleModal() {
  document.getElementById('add-schedule-modal').classList.remove('show');
}

async function saveSchedule() {
  const schedId = document.getElementById('schedule-id').value;
  const title = document.getElementById('schedule-title').value.trim();
  const desc = document.getElementById('schedule-description').value.trim();
  const startsAt = document.getElementById('schedule-start').value;
  const endsAt = document.getElementById('schedule-end').value;
  const type = document.getElementById('schedule-type').value;
  const color = document.getElementById('schedule-color').value;
  const btn = document.getElementById('save-schedule-btn');

  if (!title || !startsAt || !endsAt) { toast('Please fill in all required fields.', 'error'); return; }
  if (new Date(endsAt) <= new Date(startsAt)) { toast('End time must be after start time.', 'error'); return; }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Saving...';

  const fd = new FormData();
  fd.append('action', schedId ? 'update_schedule' : 'add_schedule');
  if (schedId) fd.append('schedule_id', schedId);
  fd.append('title', title);
  fd.append('description', desc);
  fd.append('starts_at', startsAt);
  fd.append('ends_at', endsAt);
  fd.append('type', type);
  fd.append('color', color);

  try {
    const res = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      toast(data.message || 'Schedule saved.');
      closeAddScheduleModal();
      loadSchedules();
      if (scheduleTab === 'week') renderWeek();
    } else {
      toast(data.message || 'Unable to save schedule.', 'error');
    }
  } catch {
    toast('Unable to save schedule right now.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Save';
  }
}

function openDeleteScheduleModal(schedId) {
  const s = schedulesMap[schedId];
  if (!s) return;
  document.getElementById('delete-schedule-id').value = schedId;
  document.getElementById('delete-schedule-name').textContent = s.title || 'this schedule';
  document.getElementById('delete-schedule-modal').classList.add('show');
}

function closeDeleteScheduleModal() {
  document.getElementById('delete-schedule-modal').classList.remove('show');
}

async function confirmDeleteSchedule() {
  const schedId = document.getElementById('delete-schedule-id').value;
  const btn = document.getElementById('confirm-delete-schedule-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Deleting...';

  const fd = new FormData();
  fd.append('action', 'delete_schedule');
  fd.append('schedule_id', schedId);

  try {
    const res = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      toast(data.message || 'Schedule deleted.');
      closeDeleteScheduleModal();
      loadSchedules();
      if (scheduleTab === 'week') renderWeek();
    } else {
      toast(data.message || 'Unable to delete schedule.', 'error');
    }
  } catch {
    toast('Unable to delete schedule right now.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-trash"></i> Delete';
  }
  }

  async function cleanupExpiredSchedules() {
    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=cleanup_expired_schedules'
      });
      const data = await res.json();
      if (data.success && data.message) {
        console.log('[Schedule Cleanup]', data.message);
      }
    } catch (error) {
      console.error('[Schedule Cleanup] Failed:', error);
    }
  }

// ==================== INIT ====================

  cleanupExpiredSchedules();
  renderCalendar();
  loadSchedules();
  loadNotifCount();
</script>
<?php layout_footer(); ?>
