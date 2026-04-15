<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('student');
require_once __DIR__ . '/../layout.php';
layout_header('Student Dashboard', 'student');
?>
<div class="app-shell">
  <?php layout_sidebar('student', 'dashboard'); ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1>Dashboard</h1>
        <p id="current-date"></p>
      </div>
      <div class="topbar-actions">
        <button class="notif-btn" onclick="window.location='notifications.php'" title="Notifications">
          <i class="fas fa-bell"></i>
          <span class="notif-dot" id="notif-dot" style="display:none">0</span>
        </button>
      </div>
    </div>

    <div class="page-content">
      <!-- Stats -->
      <div class="stats-grid" id="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-tasks"></i></div>
          <div class="stat-info">
            <div class="value" id="stat-pending">–</div>
            <div class="label">Pending Tasks</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
          <div class="stat-info">
            <div class="value" id="stat-done">–</div>
            <div class="label">Completed</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
          <div class="stat-info">
            <div class="value" id="stat-overdue">–</div>
            <div class="label">Overdue</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><i class="fas fa-calendar-alt"></i></div>
          <div class="stat-info">
            <div class="value" id="stat-upcoming">–</div>
            <div class="label">Upcoming Deadlines</div>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
        <!-- Today's Tasks -->
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-sun"></i> Today's Tasks</div>
            <a href="tasks.php" class="btn btn-sm btn-outline">View All</a>
          </div>
          <div class="card-body" style="padding:0">
            <div id="today-tasks">
              <div style="padding:1.5rem;text-align:center;"><span class="spinner"></span></div>
            </div>
          </div>
        </div>

        <!-- Upcoming Deadlines -->
        <div class="card">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-clock"></i> Upcoming Deadlines</div>
            <a href="assignments.php" class="btn btn-sm btn-outline">View All</a>
          </div>
          <div class="card-body" style="padding:0">
            <div id="upcoming-list">
              <div style="padding:1.5rem;text-align:center;"><span class="spinner"></span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick add task -->
      <div class="card" style="margin-top:1.5rem;">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-plus-circle"></i> Quick Add Task</div>
        </div>
        <div class="card-body">
          <div style="display:grid;grid-template-columns:1fr 180px 140px auto;gap:1rem;align-items:end;">
            <div class="form-group" style="margin:0">
              <label>Task Name</label>
              <input type="text" class="form-control" id="quick-name" placeholder="e.g. Study for Math exam">
            </div>
            <div class="form-group" style="margin:0">
              <label>Due Date &amp; Time</label>
              <input type="datetime-local" class="form-control" id="quick-due">
            </div>
            <div class="form-group" style="margin:0">
              <label>Priority</label>
              <select class="form-control" id="quick-prio">
                <option>Low</option>
                <option selected>Medium</option>
                <option>High</option>
                <option>Urgent</option>
              </select>
            </div>
            <button class="btn btn-primary" onclick="quickAddTask()"><i class="fas fa-plus"></i> Add</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const API_STUDENT = BASE_URL + '/backend/student/student_api.php';

  function priorityBadge(p) {
    const map = {
      Low: 'badge-low',
      Medium: 'badge-medium',
      High: 'badge-high',
      Urgent: 'badge-urgent'
    };
    return `<span class="badge ${map[p]||''}">${p}</span>`;
  }

  function statusBadge(s) {
    const map = {
      Pending: 'badge-pending',
      'In Progress': 'badge-progress',
      Completed: 'badge-completed',
      Overdue: 'badge-overdue'
    };
    return `<span class="badge ${map[s]||''}">${s}</span>`;
  }

  function dueLabel(due) {
    const d = new Date(due);
    const diff = Math.round((d - Date.now()) / 86400000);
    if (diff < 0) return `<span style="color:var(--red);font-size:.78rem"><i class="fas fa-exclamation-circle"></i> Overdue</span>`;
    if (diff === 0) return `<span style="color:var(--orange);font-size:.78rem"><i class="fas fa-clock"></i> Today</span>`;
    if (diff === 1) return `<span style="color:var(--orange);font-size:.78rem"><i class="fas fa-clock"></i> Tomorrow</span>`;
    return `<span style="color:var(--slate);font-size:.78rem">${d.toLocaleDateString('en-PH',{month:'short',day:'numeric'})}</span>`;
  }

  async function loadDashboard() {
    const res = await fetch(API_STUDENT + '?action=get_dashboard');
    const data = await res.json();
    if (!data.success) return;

    // Stats
    const ts = data.task_stats || {};
    document.getElementById('stat-pending').textContent = ts['Pending'] || 0;
    document.getElementById('stat-done').textContent = ts['Completed'] || 0;
    document.getElementById('stat-overdue').textContent = ts['Overdue'] || 0;
    document.getElementById('stat-upcoming').textContent = data.upcoming.length;

    // Notif badge
    if (data.unread_notif > 0) {
      const d = document.getElementById('notif-dot');
      d.textContent = data.unread_notif;
      d.style.display = 'flex';
    }

    // Today's tasks
    const todayEl = document.getElementById('today-tasks');
    if (!data.today_tasks.length) {
      todayEl.innerHTML = '<div class="empty-state"><i class="fas fa-check-double"></i><p>No tasks due today. Great!</p></div>';
    } else {
      todayEl.innerHTML = data.today_tasks.map(t => `
      <div style="padding:.9rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div>
          <div style="font-weight:600;font-size:.9rem">${t.task_name}</div>
          <div style="margin-top:2px;display:flex;gap:.5rem;align-items:center">
            ${priorityBadge(t.priority)} ${statusBadge(t.status)}
          </div>
        </div>
        <button class="btn btn-sm btn-success" onclick="markDone(${t.task_id})"><i class="fas fa-check"></i></button>
      </div>`).join('');
    }

    // Upcoming
    const upEl = document.getElementById('upcoming-list');
    if (!data.upcoming.length) {
      upEl.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-check"></i><p>No upcoming deadlines in the next 7 days.</p></div>';
    } else {
      upEl.innerHTML = data.upcoming.map(a => `
      <div style="padding:.9rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div>
          <div style="font-weight:600;font-size:.9rem">${a.title}</div>
          <div style="color:var(--slate);font-size:.78rem;margin-top:2px">${a.code} — ${a.section}</div>
        </div>
        <div style="text-align:right">
          ${dueLabel(a.due_at)}
          <div>${a.submitted ? '<span class="badge badge-submitted" style="font-size:.65rem">Submitted</span>' : ''}</div>
        </div>
      </div>`).join('');
    }
  }

  async function markDone(taskId) {
    const fd = new FormData();
    fd.append('action', 'mark_task_status');
    fd.append('task_id', taskId);
    fd.append('status', 'Completed');
    await fetch(API_STUDENT, {
      method: 'POST',
      body: fd
    });
    toast('Task marked as completed!');
    loadDashboard();
  }

  async function quickAddTask() {
    const name = document.getElementById('quick-name').value.trim();
    const due = document.getElementById('quick-due').value;
    const prio = document.getElementById('quick-prio').value;
    if (!name || !due) {
      toast('Please fill in task name and due date.', 'error');
      return;
    }
    const fd = new FormData();
    fd.append('action', 'add_task');
    fd.append('task_name', name);
    fd.append('due_at', due);
    fd.append('priority', prio);
    const res = await fetch(API_STUDENT, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      toast('Task added!');
      document.getElementById('quick-name').value = '';
      document.getElementById('quick-due').value = '';
      loadDashboard();
    } else {
      toast(data.message, 'error');
    }
  }

  // Set date
  document.getElementById('current-date').textContent =
    new Date().toLocaleDateString('en-PH', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });

  loadDashboard();
  loadNotifCount();
</script>
<?php layout_footer(); ?>