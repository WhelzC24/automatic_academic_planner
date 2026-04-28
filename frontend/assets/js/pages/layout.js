function toast(msg, type = 'success') {
  const icons = {
    success: 'check-circle',
    error: 'exclamation-circle',
    warning: 'exclamation-triangle'
  };
  const colors = {
    success: '#22c55e',
    error: '#ef4444',
    warning: '#f97316'
  };
  const div = document.createElement('div');
  div.className = `toast ${type}`;
  div.innerHTML = `<i class="fas fa-${icons[type]}" style="color:${colors[type]}"></i><span>${msg}</span>`;
  document.getElementById('toast-container').appendChild(div);
  setTimeout(() => div.remove(), 4000);
}

async function doLogout() {
  const fd = new FormData();
  fd.append('action', 'logout');
  const res = await fetch(BASE_URL + '/backend/auth/auth_handler.php', { method: 'POST', body: fd });
  const data = await res.json();
  window.location.href = data.redirect || BASE_URL + '/frontend/pages/login.php';
}

async function loadNotifCount() {
  try {
    const role = document.documentElement.dataset.role || '';
    const apiUrl = role === 'instructor' 
      ? BASE_URL + '/backend/instructor/instructor_api.php?action=get_unread_notif_count'
      : BASE_URL + '/backend/student/student_api.php?action=get_unread_notif_count';
    
    const res = await fetch(apiUrl);
    const data = await res.json();
    if (!data.success) return;

    const badge = document.getElementById('nav-notif-count');
    const dot = document.getElementById('notif-dot');
    if (badge) {
      badge.textContent = data.unread_notif;
      badge.style.display = data.unread_notif >0 ? 'inline-flex' : 'none';
    }
    if (dot) {
      dot.textContent = data.unread_notif;
      dot.style.display = data.unread_notif > 0 ? 'inline-flex' : 'none';
    }
  } catch (e) {
    // Notification count is optional on pages without context.
  }
}
