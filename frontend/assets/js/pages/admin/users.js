const API = BASE_URL + '/backend/admin/admin_api.php';
const CURRENT_USER_ID = window.CURRENT_USER_ID || 0;
let currentRole = '';
let pendingReset = null;

function setRole(role, btn) {
  currentRole = role;
  document.querySelectorAll('.role-btn').forEach(b => b.className = 'btn btn-outline btn-sm role-btn');
  btn.className = 'btn btn-primary btn-sm role-btn';
  loadUsers();
}

async function loadUsers() {
  const el = document.getElementById('user-list');
  el.innerHTML = '<div style="padding:2rem;text-align:center"><span class="spinner"></span></div>';
  const res = await fetch(`${API}?action=get_users&role=${currentRole}`);
  const data = await res.json();
  const users = data.users || [];

  const byRole = users.reduce((acc, u) => {
    acc.total += 1;
    acc.active += Number(u.is_active) ? 1 : 0;
    acc.inactive += Number(u.is_active) ? 0 : 1;
    if (u.role === 'student') acc.students += 1;
    if (u.role === 'instructor') acc.instructors += 1;
    if (u.role === 'admin') acc.admins += 1;
    return acc;
  }, { total: 0, students: 0, instructors: 0, admins: 0, active: 0, inactive: 0 });

  document.getElementById('u-total').textContent = byRole.total;
  document.getElementById('u-students').textContent = byRole.students;
  document.getElementById('u-instructors').textContent = byRole.instructors;
  document.getElementById('u-admins').textContent = byRole.admins;
  document.getElementById('u-active').textContent = byRole.active;
  document.getElementById('u-inactive').textContent = byRole.inactive;

  if (!users.length) {
    el.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><p>No users found.</p></div>';
    return;
  }

  const roleColors = { student: 'badge-submitted', instructor: 'badge-medium', admin: 'badge-urgent' };
  el.innerHTML = `<table class="users-table"><thead><tr><th>Name</th><th>Username / Email</th><th>Role</th><th>Details</th><th>Status</th><th>Actions</th></tr></thead><tbody>
  ${users.map(u => {
    const isSelf = Number(u.user_id) === Number(CURRENT_USER_ID);
    const canResetPassword = u.role === 'student' || u.role === 'instructor';
    const youBadge = isSelf ? '<span class="badge badge-submitted" style="margin-left:.4rem;font-size:.65rem">You</span>' : '';
    return `<tr>
      <td data-label="Name"><div style="font-weight:600">${u.first_name} ${u.last_name}${youBadge}</div>
        <div style="color:var(--slate);font-size:.75rem">${u.phone || ''}</div></td>
      <td data-label="Username / Email"><div style="font-size:.88rem">${u.username}</div>
        <div style="color:var(--slate);font-size:.75rem">${u.email}</div></td>
      <td data-label="Role"><span class="badge ${roleColors[u.role] || ''}">${u.role}</span></td>
      <td data-label="Details" style="font-size:.82rem;color:var(--slate)">${u.student_number || u.extra_info || '—'}</td>
      <td data-label="Status"><span class="badge ${u.is_active ? 'badge-completed' : 'badge-overdue'}">${u.is_active ? 'Active' : 'Inactive'}</span></td>
      <td data-label="Actions">
      <div style="display:flex;gap:.4rem">
        <button class="btn btn-sm btn-outline" onclick="resetPassword(${u.user_id},'${u.first_name} ${u.last_name}','${u.role}')" title="${canResetPassword ? 'Reset password to default 12345' : 'Only students and instructors can be reset'}" ${canResetPassword ? '' : 'disabled'}>
          <i class="fas fa-key"></i>
        </button>
        <button class="btn btn-sm btn-outline" onclick="toggleUser(${u.user_id})" title="${isSelf ? 'You cannot modify your own status' : (u.is_active ? 'Deactivate' : 'Activate')}" ${isSelf ? 'disabled' : ''}>
          <i class="fas fa-${isSelf ? 'lock' : (u.is_active ? 'ban' : 'check')}"></i>
        </button>
        <button class="btn btn-sm btn-danger" onclick="deleteUser(${u.user_id},'${u.first_name} ${u.last_name}')" title="${isSelf ? 'You cannot delete your own account' : 'Delete user'}" ${isSelf ? 'disabled' : ''}>
          <i class="fas fa-${isSelf ? 'lock' : 'trash'}"></i>
        </button>
      </div>
    </td>
  </tr>`;
  }).join('')}
  </tbody></table>`;
}

function openModal(roleType) {
  document.getElementById('user-role-type').value = roleType;
  document.getElementById('user-modal-title').textContent = roleType === 'student' ? 'Add Student' : 'Add Instructor';
  document.getElementById('student-fields').style.display = roleType === 'student' ? 'block' : 'none';
  document.getElementById('instructor-fields').style.display = roleType === 'instructor' ? 'block' : 'none';
  ['u-first', 'u-last', 'u-username', 'u-email', 'u-phone', 'u-sn', 'u-program', 'u-dept', 'u-desig', 'u-office'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  document.getElementById('user-modal').classList.add('show');
}

function closeModal() {
  document.getElementById('user-modal').classList.remove('show');
}

async function saveUser() {
  const role = document.getElementById('user-role-type').value;
  const fd = new FormData();
  fd.append('action', role === 'student' ? 'add_student' : 'add_instructor');
  fd.append('first_name', document.getElementById('u-first').value.trim());
  fd.append('last_name', document.getElementById('u-last').value.trim());
  fd.append('username', document.getElementById('u-username').value.trim());
  fd.append('email', document.getElementById('u-email').value.trim());
  fd.append('phone', document.getElementById('u-phone').value.trim());
  if (role === 'student') {
    fd.append('student_number', document.getElementById('u-sn').value.trim());
    fd.append('year_level', document.getElementById('u-year').value);
    fd.append('program', document.getElementById('u-program').value.trim());
  } else {
    fd.append('department', document.getElementById('u-dept').value.trim());
    fd.append('designation', document.getElementById('u-desig').value.trim());
    fd.append('office_location', document.getElementById('u-office').value.trim());
  }
  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast(data.message);
    closeModal();
    loadUsers();
  } else {
    toast(data.message, 'error');
  }
}

async function toggleUser(id) {
  const fd = new FormData();
  fd.append('action', 'toggle_user');
  fd.append('user_id', id);
  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast(data.message);
    loadUsers();
  } else {
    toast(data.message, 'error');
  }
}

async function deleteUser(id, name) {
  if (!confirm(`Delete user \"${name}\"? This cannot be undone.`)) return;
  const fd = new FormData();
  fd.append('action', 'delete_user');
  fd.append('user_id', id);
  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    toast('User deleted.');
    loadUsers();
  } else {
    toast(data.message, 'error');
  }
}

async function resetPassword(id, name, role) {
  if (role !== 'student' && role !== 'instructor') {
    toast('Only student and instructor accounts can be reset.', 'error');
    return;
  }

  pendingReset = { id, name, role };
  document.getElementById('reset-user-name').textContent = name;
  document.getElementById('reset-password-modal').classList.add('show');
}

function closeResetModal() {
  pendingReset = null;
  document.getElementById('reset-password-modal').classList.remove('show');
}

async function confirmResetPassword() {
  if (!pendingReset) return;

  const fd = new FormData();
  fd.append('action', 'reset_user_password');
  fd.append('user_id', pendingReset.id);

  const btn = document.getElementById('confirm-reset-btn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';

  const res = await fetch(API, { method: 'POST', body: fd });
  const data = await res.json();
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-key"></i> Reset Password';

  if (data.success) {
    closeResetModal();
    toast(data.message);
    loadUsers();
  } else {
    toast(data.message, 'error');
  }
}

loadUsers();
