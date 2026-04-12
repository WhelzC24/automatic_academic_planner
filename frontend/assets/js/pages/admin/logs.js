const API = BASE_URL + '/backend/admin/admin_api.php';

async function loadLogs() {
  const limit = document.getElementById('limit-sel').value;
  const res = await fetch(`${API}?action=get_logs&limit=${limit}`);
  const data = await res.json();
  const el = document.getElementById('logs-list');
  const logs = data.logs || [];
  const actionColors = {
    LOGIN_SUCCESS: 'badge-completed',
    LOGIN_FAILED: 'badge-overdue',
    LOGOUT: 'badge-pending',
    REGISTER: 'badge-submitted',
    ADD_STUDENT: 'badge-submitted',
    ADD_INSTRUCTOR: 'badge-medium',
    CREATE_ASSIGNMENT: 'badge-medium',
    TOGGLE_USER: 'badge-high',
    DELETE_USER: 'badge-urgent'
  };
  const loginSuccess = logs.filter(l => l.action === 'LOGIN_SUCCESS').length;
  const loginFailed = logs.filter(l => l.action === 'LOGIN_FAILED').length;
  const userActions = logs.filter(l => ['ADD_STUDENT', 'ADD_INSTRUCTOR', 'TOGGLE_USER', 'DELETE_USER', 'REGISTER'].includes(l.action)).length;
  document.getElementById('log-total').textContent = logs.length;
  document.getElementById('log-success').textContent = loginSuccess;
  document.getElementById('log-failed').textContent = loginFailed;
  document.getElementById('log-user-actions').textContent = userActions;
  document.getElementById('log-window').textContent = 'Last ' + limit;

  el.innerHTML = `<table class="logs-table"><thead><tr>
    <th>Timestamp</th><th>User</th><th>Role</th><th>Action</th><th>Description</th><th>IP</th>
  </tr></thead><tbody>
  ${logs.map(l => `<tr>
    <td data-label="Timestamp" style="font-size:.8rem;color:var(--slate)">${new Date(l.created_at).toLocaleString('en-PH')}</td>
    <td data-label="User" style="font-weight:600;font-size:.85rem">${l.username || '—'}</td>
    <td data-label="Role" style="font-size:.8rem">${l.role || '—'}</td>
    <td data-label="Action"><span class="badge ${actionColors[l.action] || 'badge-pending'}" style="font-size:.7rem">${l.action}</span></td>
    <td data-label="Description" style="font-size:.82rem;color:var(--slate)">${l.description || ''}</td>
    <td data-label="IP" style="font-size:.75rem;color:var(--slate)">${l.ip_address || '—'}</td>
  </tr>`).join('')}
  </tbody></table>`;
}

loadLogs();
