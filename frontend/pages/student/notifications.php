<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('student');
require_once __DIR__ . '/../layout.php';
layout_header('Notifications', 'student');
?>
<div style="display:flex;min-height:100vh;">
<?php layout_sidebar('student','notifications'); ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><h1>Notifications</h1><p>Stay updated with your academic activities</p></div>
    <div class="topbar-actions">
      <button class="btn btn-outline btn-sm" onclick="markAllRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
    </div>
  </div>
  <div class="page-content">
    <div class="card">
      <div class="card-body" style="padding:0">
        <div id="notif-list"><div style="text-align:center;padding:3rem"><span class="spinner"></span></div></div>
      </div>
    </div>
  </div>
</div>
</div>

<script>
const API = BASE_URL + '/backend/student/student_api.php';

const typeIcon = {
  'Deadline Reminder':      {icon:'clock',color:'#f97316'},
  'Assignment Posted':      {icon:'file-alt',color:'#3b82f6'},
  'Submission Confirmation':{icon:'check-circle',color:'#22c55e'},
  'Schedule Reminder':      {icon:'calendar-alt',color:'#8b5cf6'},
};

async function loadNotifications() {
  const res  = await fetch(API + '?action=get_notifications');
  const data = await res.json();
  const el   = document.getElementById('notif-list');
  if (!data.notifications.length) {
    el.innerHTML = '<div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notifications yet.</p></div>';
    return;
  }
  el.innerHTML = data.notifications.map(n => {
    const t = typeIcon[n.type] || {icon:'bell',color:'var(--slate)'};
    const unread = !n.read_at;
    return `<div class="notif-item" id="notif-${n.notification_id}" style="
        display:flex;align-items:flex-start;gap:1rem;
        padding:1rem 1.5rem;
        border-bottom:1px solid var(--border);
        background:${unread ? '#fffbeb' : 'var(--white)'};
        cursor:pointer;
        transition:background .2s;
      " onclick="markRead(${n.notification_id})">
      <div style="width:40px;height:40px;border-radius:50%;background:${t.color}22;
           display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="fas fa-${t.icon}" style="color:${t.color}"></i>
      </div>
      <div style="flex:1">
        <div style="font-size:.8rem;font-weight:700;color:${t.color};text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem">${n.type}</div>
        <div style="font-size:.9rem;color:var(--text);line-height:1.5">${n.message}</div>
        <div style="font-size:.75rem;color:var(--slate);margin-top:.3rem">
          ${new Date(n.sent_at).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'})}
          ${unread ? '<span style="margin-left:.5rem;background:#fbbf24;color:#78350f;padding:1px 7px;border-radius:10px;font-size:.65rem;font-weight:700">NEW</span>' : ''}
        </div>
      </div>
    </div>`;
  }).join('');
}

async function markRead(id) {
  const el = document.getElementById('notif-' + id);
  if (el) el.style.background = 'var(--white)';
  const fd = new FormData();
  fd.append('action','mark_notification_read');
  fd.append('notification_id', id);
  await fetch(API, {method:'POST', body:fd});
}

async function markAllRead() {
  const fd = new FormData();
  fd.append('action','mark_all_read');
  await fetch(API, {method:'POST', body:fd});
  toast('All notifications marked as read.');
  loadNotifications();
}

loadNotifications();
</script>
<?php layout_footer(); ?>
