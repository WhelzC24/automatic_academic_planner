<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('student');
require_once __DIR__ . '/../layout.php';
layout_header('Assignments', 'student');
?>
<div class="app-shell">
  <?php layout_sidebar('student', 'assignments'); ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1>Assignments</h1>
        <p>View and submit your course assignments</p>
      </div>
    </div>
    <div class="page-content">
      <div id="assignments-list">
        <div style="text-align:center;padding:3rem"><span class="spinner"></span></div>
      </div>
    </div>
  </div>
</div>

<!-- Submit Modal -->
<div class="modal-overlay" id="submit-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Submit Assignment</div>
      <button class="modal-close" onclick="closeSubmit()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="submit-aid">
      <div id="submit-asg-title" style="background:var(--bg);border-radius:8px;padding:1rem;margin-bottom:1.25rem;font-weight:600;color:var(--navy)"></div>
      <div class="form-group">
        <label>Upload File *</label>
        <input type="file" class="form-control" id="submit-file" accept=".pdf,.doc,.docx,.txt,.zip,.png,.jpg,.jpeg">
        <p style="color:var(--slate);font-size:.75rem;margin-top:.4rem">Allowed: PDF, DOC, DOCX, TXT, ZIP, PNG, JPG (max 10MB)</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeSubmit()">Cancel</button>
      <button class="btn btn-primary" onclick="doSubmit()"><i class="fas fa-upload"></i> Submit</button>
    </div>
  </div>
</div>

<script>
  const API = BASE_URL + '/backend/student/student_api.php';

  function dueColor(due) {
    const diff = (new Date(due) - Date.now()) / 86400000;
    if (diff < 0) return '#ef4444';
    if (diff < 1) return '#f97316';
    if (diff <= 3) return '#f97316';
    return 'var(--slate)';
  }

  function dueText(due) {
    const d = new Date(due);
    const diff = Math.round((d - Date.now()) / 86400000);
    if (diff < 0) return 'Overdue!';
    if (diff === 0) return 'Due today!';
    if (diff === 1) return 'Due tomorrow';
    return `Due in ${diff} days`;
  }

  async function loadAssignments() {
    const res = await fetch(API + '?action=get_assignments');
    const data = await res.json();
    const el = document.getElementById('assignments-list');
    if (!data.assignments.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-file-alt"></i><p>No assignments found. You may not be enrolled in any courses yet.</p></div>';
      return;
    }

    const isCompleted = (assignment) => Boolean(assignment.sub_status);

    const groupByCourse = (items) => {
      const grouped = {};
      items.forEach(a => {
        const key = `${a.code} — ${a.course_title} (${a.section})`;
        if (!grouped[key]) grouped[key] = [];
        grouped[key].push(a);
      });
      return grouped;
    };

    const renderAssignmentRows = (items, completedMode) => items.map(a => {
      const color = dueColor(a.due_at);
      const subStatus = a.sub_status || null;
      return `<div style="padding:1.2rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
        <div style="flex:1;min-width:200px">
          <div style="font-weight:700;font-size:1rem;margin-bottom:.25rem">${a.title}</div>
          <div style="color:var(--slate);font-size:.82rem;margin-bottom:.5rem">${a.description ? a.description.substring(0,120)+'...' : 'No description.'}</div>
          <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <span style="color:${color};font-size:.8rem;font-weight:600"><i class="fas fa-clock"></i>
              ${new Date(a.due_at).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'})}
              — ${dueText(a.due_at)}
            </span>
            <span style="color:var(--slate);font-size:.78rem">
              <i class="fas fa-user"></i> ${a.first_name} ${a.last_name}
            </span>
          </div>
          ${a.grade!==null ? `<div style="margin-top:.5rem;font-weight:700;color:var(--green)"><i class="fas fa-star"></i> Grade: ${a.grade}/100
            ${a.feedback ? `<span style="color:var(--slate);font-weight:400;font-size:.8rem"> — "${a.feedback}"</span>` : ''}</div>` : ''}
        </div>
        <div style="text-align:right;flex-shrink:0">
          ${completedMode && subStatus
            ? `<span class="badge badge-${subStatus}" style="font-size:.8rem;padding:5px 12px">${subStatus.charAt(0).toUpperCase()+subStatus.slice(1)}</span>
               <div style="color:var(--slate);font-size:.72rem;margin-top:.3rem">Submitted ${new Date(a.submitted_at).toLocaleDateString('en-PH')}</div>`
            : `<button class="btn btn-primary" onclick="openSubmit(${a.assignment_id},'${a.title.replace(/'/g,"\\'")}')">
                 <i class="fas fa-upload"></i> Submit
               </button>`
          }
        </div>
      </div>`;
    }).join('');

    const renderCourseCards = (grouped, completedMode) => Object.entries(grouped).map(([course, items]) => `
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header" style="background:var(--navy);border-radius:12px 12px 0 0;">
          <div class="card-title" style="color:var(--white)"><i class="fas fa-book-open" style="color:var(--gold)"></i> ${course}</div>
        </div>
        <div class="card-body" style="padding:0">
          ${renderAssignmentRows(items, completedMode)}
        </div>
      </div>
    `).join('');

    const activeAssignments = data.assignments.filter(a => !isCompleted(a));
    const completedAssignments = data.assignments.filter(isCompleted);

    const activeGrouped = groupByCourse(activeAssignments);
    const completedGrouped = groupByCourse(completedAssignments);

    const activeHtml = activeAssignments.length ?
      renderCourseCards(activeGrouped, false) :
      '<div class="empty-state"><i class="fas fa-inbox"></i><p>No active assignments right now.</p></div>';

    const completedHtml = completedAssignments.length ?
      renderCourseCards(completedGrouped, true) :
      '<div class="empty-state"><i class="fas fa-check-circle"></i><p>No completed assignments yet.</p></div>';

    el.innerHTML = `
      <div style="margin-bottom:2rem">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.9rem">
          <h2 style="font-family:'Playfair Display',serif;color:var(--navy);font-size:1.25rem">Active Assignments</h2>
          <span class="badge" style="background:var(--deep)22;color:var(--deep)">${activeAssignments.length}</span>
        </div>
        ${activeHtml}
      </div>

      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.9rem">
          <h2 style="font-family:'Playfair Display',serif;color:var(--navy);font-size:1.25rem">Completed</h2>
          <span class="badge" style="background:var(--green)22;color:var(--green)">${completedAssignments.length}</span>
        </div>
        ${completedHtml}
      </div>
    `;
  }

  function openSubmit(aid, title) {
    document.getElementById('submit-aid').value = aid;
    document.getElementById('submit-asg-title').textContent = title;
    document.getElementById('submit-file').value = '';
    document.getElementById('submit-modal').classList.add('show');
  }

  function closeSubmit() {
    document.getElementById('submit-modal').classList.remove('show');
  }

  async function doSubmit() {
    const aid = document.getElementById('submit-aid').value;
    const file = document.getElementById('submit-file').files[0];
    if (!file) {
      toast('Please select a file.', 'error');
      return;
    }
    const fd = new FormData();
    fd.append('action', 'submit_assignment');
    fd.append('assignment_id', aid);
    fd.append('submission_file', file);
    const btn = document.querySelector('#submit-modal .btn-primary');
    btn.innerHTML = '<span class="spinner"></span> Uploading...';
    btn.disabled = true;
    const res = await fetch(API, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    btn.innerHTML = '<i class="fas fa-upload"></i> Submit';
    btn.disabled = false;
    if (data.success) {
      toast(data.message);
      closeSubmit();
      loadAssignments();
    } else toast(data.message, 'error');
  }

  loadAssignments();
  loadNotifCount();
</script>
<?php layout_footer(); ?>