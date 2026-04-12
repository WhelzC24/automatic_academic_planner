const API = '../../backend/auth/auth_handler.php';
let pendingRedirect = '';

function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector('i');
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
  btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
}

function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach((b, i) =>
    b.classList.toggle('active', (i === 0 && tab === 'login') || (i === 1 && tab === 'register'))
  );
  document.querySelectorAll('.panel').forEach((p) => p.classList.remove('active'));
  document.getElementById('panel-' + tab).classList.add('active');
}

function showAlert(id, msg, type = 'error') {
  const el = document.getElementById(id);
  el.className = `alert ${type} show`;
  el.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> ${msg}`;
}

async function doLogin() {
  const u = document.getElementById('login-username').value.trim();
  const p = document.getElementById('login-password').value;
  if (!u || !p) {
    showAlert('login-alert', 'Please fill in all fields.');
    return;
  }

  const fd = new FormData();
  fd.append('action', 'login');
  fd.append('username', u);
  fd.append('password', p);

  const btn = document.querySelector('#panel-login .btn-submit');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
  btn.disabled = true;

  try {
    const res = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      if (data.must_change_password) {
        pendingRedirect = data.redirect || '';
        showAlert('login-alert', data.message || 'Please change your password to continue.', 'success');
        openForcedPasswordModal();
      } else {
        showAlert('login-alert', 'Login successful! Redirecting...', 'success');
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 800);
      }
    } else {
      showAlert('login-alert', data.message);
      btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
      btn.disabled = false;
    }
  } catch (e) {
    showAlert('login-alert', 'Server error. Please try again.');
    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
    btn.disabled = false;
  }
}

function openForcedPasswordModal() {
  const overlay = document.getElementById('pw-change-overlay');
  overlay.classList.add('show');
  overlay.setAttribute('aria-hidden', 'false');

  const loginBtn = document.querySelector('#panel-login .btn-submit');
  loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
  loginBtn.disabled = false;

  document.getElementById('force-new-pass').value = '';
  document.getElementById('force-new-pass2').value = '';
  const alert = document.getElementById('pw-change-alert');
  alert.className = 'alert';
  alert.innerHTML = '';
  document.getElementById('force-new-pass').focus();
}

async function submitForcedPasswordChange() {
  const pass = document.getElementById('force-new-pass').value;
  const pass2 = document.getElementById('force-new-pass2').value;

  if (!pass || !pass2) {
    showAlert('pw-change-alert', 'Please fill in both password fields.');
    return;
  }
  if (pass !== pass2) {
    showAlert('pw-change-alert', 'Passwords do not match.');
    return;
  }

  const fd = new FormData();
  fd.append('action', 'change_password_required');
  fd.append('new_password', pass);
  fd.append('confirm_password', pass2);

  const btn = document.getElementById('force-pass-btn');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
  btn.disabled = true;

  try {
    const res = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) {
      showAlert('pw-change-alert', data.message || 'Unable to update password.');
      btn.innerHTML = '<i class="fas fa-save"></i> Update Password';
      btn.disabled = false;
      return;
    }

    showAlert('pw-change-alert', 'Password updated. Redirecting...', 'success');
    setTimeout(() => {
      window.location.href = pendingRedirect || data.redirect || '/';
    }, 700);
  } catch (e) {
    showAlert('pw-change-alert', 'Server error. Please try again.');
    btn.innerHTML = '<i class="fas fa-save"></i> Update Password';
    btn.disabled = false;
  }
}

async function doRegister() {
  const fd = new FormData();
  fd.append('action', 'register');
  fd.append('first_name', document.getElementById('reg-first').value.trim());
  fd.append('last_name', document.getElementById('reg-last').value.trim());
  fd.append('username', document.getElementById('reg-username').value.trim());
  fd.append('email', document.getElementById('reg-email').value.trim());
  fd.append('password', document.getElementById('reg-pass').value);
  fd.append('confirm_password', document.getElementById('reg-pass2').value);
  fd.append('student_number', document.getElementById('reg-sn').value.trim());
  fd.append('year_level', document.getElementById('reg-year').value);
  fd.append('program', document.getElementById('reg-program').value.trim());
  fd.append('phone', document.getElementById('reg-phone').value.trim());

  const btn = document.querySelector('#panel-register .btn-submit');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';
  btn.disabled = true;

  try {
    const res = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      showAlert('register-alert', data.message, 'success');
      setTimeout(() => switchTab('login'), 2000);
    } else {
      showAlert('register-alert', data.message);
    }
    btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
    btn.disabled = false;
  } catch (e) {
    showAlert('register-alert', 'Server error. Please try again.');
    btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
    btn.disabled = false;
  }
}

document.addEventListener('keydown', (e) => {
  const forceOverlay = document.getElementById('pw-change-overlay');
  if (forceOverlay && forceOverlay.classList.contains('show') && e.key === 'Enter') {
    submitForcedPasswordChange();
    return;
  }
  if (e.key === 'Enter') {
    const active = document.querySelector('.panel.active');
    if (active.id === 'panel-login') {
      doLogin();
    } else {
      doRegister();
    }
  }
});
