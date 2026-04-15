<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('student');
require_once __DIR__ . '/../layout.php';
layout_header('My Tasks', 'student');
?>
<div class="app-shell">
  <?php layout_sidebar('student', 'tasks'); ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1>My Tasks</h1>
        <p>Manage and track your academic tasks</p>
      </div>
      <div class="topbar-actions">
        <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Task</button>
      </div>
    </div>

    <div class="page-content">
      <!-- Filter tabs -->
      <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
        <button class="btn btn-primary btn-sm filter-btn" onclick="setFilter('all',this)">All Tasks</button>
        <button class="btn btn-outline btn-sm filter-btn" onclick="setFilter('today',this)">Today</button>
        <button class="btn btn-outline btn-sm filter-btn" onclick="setFilter('pending',this)">Pending</button>
        <button class="btn btn-outline btn-sm filter-btn" onclick="setFilter('overdue',this)">Overdue</button>
      </div>

      <div class="card">
        <div class="card-body" style="padding:0">
          <div id="tasks-list">
            <div style="padding:2rem;text-align:center"><span class="spinner"></span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="task-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-title-text">Add Task</div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-id">
      <div class="form-group">
        <label>Task Name *</label>
        <input type="text" class="form-control" id="t-name" placeholder="e.g. Study Chapter 5">
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea class="form-control" id="t-desc" placeholder="Optional notes..."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Due Date &amp; Time *</label>
          <input type="datetime-local" class="form-control" id="t-due">
        </div>
        <div class="form-group">
          <label>Priority</label>
          <select class="form-control" id="t-prio">
            <option>Low</option>
            <option selected>Medium</option>
            <option>High</option>
            <option>Urgent</option>
          </select>
        </div>
      </div>
      <div class="form-group" id="status-group" style="display:none">
        <label>Status</label>
        <select class="form-control" id="t-status">
          <option>Pending</option>
          <option>In Progress</option>
          <option>Completed</option>
          <option>Overdue</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="saveTask()"><i class="fas fa-save"></i> Save Task</button>
    </div>
  </div>
</div>

<script>
  const API = BASE_URL + '/backend/student/student_api.php';
  let currentFilter = 'all';

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

  function setFilter(f, btn) {
    currentFilter = f;
    document.querySelectorAll('.filter-btn').forEach(b => {
      b.className = 'btn btn-outline btn-sm filter-btn';
    });
    btn.className = 'btn btn-primary btn-sm filter-btn';
    loadTasks();
  }

  async function loadTasks() {
    const el = document.getElementById('tasks-list');
    el.innerHTML = '<div style="padding:2rem;text-align:center"><span class="spinner"></span></div>';
    const res = await fetch(`${API}?action=get_tasks&filter=${currentFilter}`);
    const data = await res.json();
    if (!data.tasks.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No tasks found.</p></div>';
      return;
    }
    el.innerHTML = `<table><thead><tr>
    <th>Task</th><th>Due</th><th>Priority</th><th>Status</th><th>Actions</th>
  </tr></thead><tbody>
  ${data.tasks.map(t => `<tr>
    <td>
      <div style="font-weight:600">${t.task_name}</div>
      ${t.description ? `<div style="color:var(--slate);font-size:.78rem;margin-top:2px">${t.description.substring(0,60)}...</div>` : ''}
      ${t.assignment_title ? `<div style="color:var(--blue);font-size:.75rem;margin-top:2px"><i class="fas fa-link"></i> ${t.assignment_title}</div>` : ''}
    </td>
    <td style="white-space:nowrap">
      <div style="font-size:.88rem">${new Date(t.due_at).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'})}</div>
      <div style="color:var(--slate);font-size:.75rem">${new Date(t.due_at).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'})}</div>
    </td>
    <td>${priorityBadge(t.priority)}</td>
    <td>${statusBadge(t.status)}</td>
    <td>
      <div style="display:flex;gap:.4rem">
        ${t.status!=='Completed'?`<button class="btn btn-sm btn-success" onclick="quickStatus(${t.task_id},'Completed')" title="Mark Complete"><i class="fas fa-check"></i></button>`:''}
        <button class="btn btn-sm btn-outline" onclick="editTask(${JSON.stringify(t).replace(/"/g,'&quot;')})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-danger" onclick="deleteTask(${t.task_id})"><i class="fas fa-trash"></i></button>
      </div>
    </td>
  </tr>`).join('')}
  </tbody></table>`;
  }

  function openModal() {
    document.getElementById('edit-id').value = '';
    document.getElementById('t-name').value = '';
    document.getElementById('t-desc').value = '';
    document.getElementById('t-due').value = '';
    document.getElementById('t-prio').value = 'Medium';
    document.getElementById('modal-title-text').textContent = 'Add Task';
    document.getElementById('status-group').style.display = 'none';
    document.getElementById('task-modal').classList.add('show');
  }

  function closeModal() {
    document.getElementById('task-modal').classList.remove('show');
  }

  function editTask(t) {
    document.getElementById('edit-id').value = t.task_id;
    document.getElementById('t-name').value = t.task_name;
    document.getElementById('t-desc').value = t.description || '';
    document.getElementById('t-due').value = t.due_at.replace(' ', 'T').substring(0, 16);
    document.getElementById('t-prio').value = t.priority;
    document.getElementById('t-status').value = t.status;
    document.getElementById('modal-title-text').textContent = 'Edit Task';
    document.getElementById('status-group').style.display = 'block';
    document.getElementById('task-modal').classList.add('show');
  }

  async function saveTask() {
    const id = document.getElementById('edit-id').value;
    const name = document.getElementById('t-name').value.trim();
    const due = document.getElementById('t-due').value;
    if (!name || !due) {
      toast('Task name and due date are required.', 'error');
      return;
    }
    const fd = new FormData();
    fd.append('action', id ? 'update_task' : 'add_task');
    if (id) fd.append('task_id', id);
    fd.append('task_name', name);
    fd.append('description', document.getElementById('t-desc').value);
    fd.append('due_at', due);
    fd.append('priority', document.getElementById('t-prio').value);
    if (id) fd.append('status', document.getElementById('t-status').value);
    const res = await fetch(API, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      toast(data.message);
      closeModal();
      loadTasks();
    } else toast(data.message, 'error');
  }

  async function quickStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'mark_task_status');
    fd.append('task_id', id);
    fd.append('status', status);
    await fetch(API, {
      method: 'POST',
      body: fd
    });
    toast('Task updated!');
    loadTasks();
  }

  async function deleteTask(id) {
    if (!confirm('Delete this task?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_task');
    fd.append('task_id', id);
    const res = await fetch(API, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      toast('Task deleted.');
      loadTasks();
    } else toast(data.message, 'error');
  }

  loadTasks();
  loadNotifCount();
</script>
<?php layout_footer(); ?>