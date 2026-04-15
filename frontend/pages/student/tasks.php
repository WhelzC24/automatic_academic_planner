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
      <div class="topbar-actions"></div>
    </div>

    <div class="page-content">

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



<script>
  const API = BASE_URL + '/backend/student/student_api.php';

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

  async function loadTasks() {
    const el = document.getElementById('tasks-list');
    el.innerHTML = '<div style="padding:2rem;text-align:center"><span class="spinner"></span></div>';
    const res = await fetch(`${API}?action=get_tasks`);
    const data = await res.json();
    if (!data.tasks.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No assignment tasks found.</p></div>';
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
      </div>
    </td>
  </tr>`).join('')}
  </tbody></table>`;
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

  loadTasks();
  loadNotifCount();
</script>
<?php layout_footer(); ?>