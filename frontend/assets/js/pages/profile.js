const API_PROFILE = BASE_URL + '/backend/api/profile_api.php';
const ROLE = window.PROFILE_ROLE;

function showTab(tab, btn) {
  document.querySelectorAll('.prof-tab').forEach((b) => {
    b.style.color = 'var(--slate)';
    b.style.borderBottomColor = 'transparent';
  });
  btn.style.color = 'var(--deep)';
  btn.style.borderBottomColor = 'var(--deep)';
  document.getElementById('tab-info').style.display = tab === 'info' ? 'block' : 'none';
  document.getElementById('tab-security').style.display = tab === 'security' ? 'block' : 'none';
}

function showAlert(id, msg, type = 'error') {
  const el = document.getElementById(id);
  el.className = `alert ${type}`;
  el.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> ${msg}`;
}

async function loadProfile() {
  const res = await fetch(API_PROFILE + '?action=get_profile');
  const data = await res.json();
  if (!data.success) {
    return;
  }

  const p = data.profile;
  document.getElementById('avatar-circle').textContent = p.first_name.charAt(0).toUpperCase();
  document.getElementById('prof-fullname').textContent = `${p.first_name} ${p.last_name}`;
  document.getElementById('prof-joined').textContent = `Joined: ${new Date(p.created_at).toLocaleDateString('en-PH', { month: 'long', year: 'numeric' })}`;

  const roleColors = { student: '#1e3a5f', instructor: '#c9a227', admin: '#ef4444' };
  const roleColor = roleColors[p.role] || '#64748b';
  document.getElementById('prof-role-badge').innerHTML =
    `<span style="background:${roleColor}22;color:${roleColor};padding:3px 12px;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em">${p.role}</span>`;

  document.getElementById('side-role').textContent = p.role;
  document.getElementById('side-email').textContent = p.email;
  document.getElementById('side-joined').textContent = new Date(p.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });

  document.getElementById('p-first').value = p.first_name;
  document.getElementById('p-last').value = p.last_name;
  document.getElementById('p-email').value = p.email;
  document.getElementById('p-phone').value = p.phone || '';

  if (p.role === 'student') {
    document.getElementById('student-fields').style.display = 'block';
    document.getElementById('p-sn').value = p.student_number || '';
    document.getElementById('p-year').value = p.year_level || 1;
    const prog = p.program || '';
    const progSelect = document.getElementById('p-program');
    progSelect.value = prog ? (progSelect.querySelector('option[value="' + prog + '"]') ? prog : 'BSCS') : '';
    const blocks = p.blocks || [];
    document.getElementById('p-block').value = blocks.length > 0 ? blocks.join(', ') : 'No block enrolled';
    
    const coursesEl = document.getElementById('enrolled-courses-list');
    const courses = p.enrolled_courses || [];
    if (courses.length === 0) {
      coursesEl.innerHTML = '<span style="color:var(--slate)">No enrolled courses yet.</span>';
    } else {
      coursesEl.innerHTML = courses.map(c => `
        <div style="display:flex;justify-content:space-between;padding:.25rem 0;border-bottom:1px solid var(--border)">
          <span><strong>${c.code}</strong> - ${c.title}</span>
          <span style="color:var(--gold)">${c.section}</span>
        </div>
      `).join('');
    }
  } else if (p.role === 'instructor') {
    document.getElementById('instructor-fields').style.display = 'block';
    const prog = p.department || p.program || '';
    const progSelect = document.getElementById('p-dept');
    if (prog) {
      const opt = progSelect.querySelector('option[value="' + prog + '"]');
      progSelect.value = opt ? prog : '';
    } else {
      progSelect.value = '';
    }
    document.getElementById('p-desig').value = p.designation || '';
    document.getElementById('p-office').value = p.office_location || '';
  }
}

async function saveProfile() {
  const fd = new FormData();
  fd.append('action', 'update_profile');
  fd.append('first_name', document.getElementById('p-first').value.trim());
  fd.append('last_name', document.getElementById('p-last').value.trim());
  fd.append('email', document.getElementById('p-email').value.trim());
  fd.append('phone', document.getElementById('p-phone').value.trim());

  if (ROLE === 'student') {
    fd.append('year_level', document.getElementById('p-year').value);
    fd.append('program', document.getElementById('p-program').value.trim());
  } else if (ROLE === 'instructor') {
    fd.append('department', document.getElementById('p-dept').value.trim());
    fd.append('designation', document.getElementById('p-desig').value.trim());
    fd.append('office_location', document.getElementById('p-office').value.trim());
  }

  const res = await fetch(API_PROFILE, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    showAlert('info-alert', data.message, 'success');
    toast(data.message);
    loadProfile();
  } else {
    showAlert('info-alert', data.message, 'error');
  }
}

async function changePassword() {
  const fd = new FormData();
  fd.append('action', 'change_password');
  fd.append('current_password', document.getElementById('s-current').value);
  fd.append('new_password', document.getElementById('s-new').value);
  fd.append('confirm_password', document.getElementById('s-confirm').value);

  const res = await fetch(API_PROFILE, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    showAlert('sec-alert', data.message, 'success');
    toast(data.message);
    document.getElementById('s-current').value = '';
    document.getElementById('s-new').value = '';
    document.getElementById('s-confirm').value = '';
  } else {
    showAlert('sec-alert', data.message, 'error');
  }
}

function togglePw(inputId, btn) {
  const inp = document.getElementById(inputId);
  if (inp.type === 'password') {
    inp.type = 'text';
    btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
  } else {
    inp.type = 'password';
    btn.innerHTML = '<i class="fas fa-eye"></i>';
  }
}

document.getElementById('s-new').addEventListener('input', function () {
  const val = this.value;
  const fill = document.getElementById('pw-strength-fill');
  const label = document.getElementById('pw-strength-label');
  let score = 0;

  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { pct: '0%', color: 'var(--border)', text: '' },
    { pct: '25%', color: 'var(--red)', text: 'Weak' },
    { pct: '50%', color: 'var(--orange)', text: 'Fair' },
    { pct: '75%', color: '#eab308', text: 'Good' },
    { pct: '100%', color: 'var(--green)', text: 'Strong' }
  ];

  fill.style.width = levels[score].pct;
  fill.style.background = levels[score].color;
  label.textContent = levels[score].text;
  label.style.color = levels[score].color;
});

loadProfile();
loadNotifCount();
