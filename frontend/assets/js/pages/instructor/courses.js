const API = BASE_URL + '/backend/instructor/instructor_api.php';
let currentOfferings = [];
let currentEventsMap = {};

async function loadCourses() {
  const res = await fetch(API + '?action=get_my_offerings');
  const data = await res.json();
  currentOfferings = data.offerings || [];
  const el = document.getElementById('courses-grid');

  const totalOfferings = currentOfferings.length;
  const totalUnits = currentOfferings.reduce((sum, o) => sum + parseFloat(o.units || 0), 0);
  const sectionCount = new Set(currentOfferings.map((o) => o.section).filter(Boolean)).size;
  const totalEl = document.getElementById('c-total');
  const unitsEl = document.getElementById('c-units');
  const sectionsEl = document.getElementById('c-sections');
  if (totalEl) totalEl.textContent = totalOfferings;
  if (unitsEl) unitsEl.textContent = Number.isInteger(totalUnits) ? totalUnits : totalUnits.toFixed(1);
  if (sectionsEl) sectionsEl.textContent = sectionCount;

  if (!currentOfferings.length) {
    el.innerHTML = '<div class="empty-state" style="padding:4rem"><i class="fas fa-book"></i><p>No courses assigned yet.</p></div>';
    return;
  }

  el.innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem">
  ${currentOfferings.map((o) => `
    <div class="card">
      <div style="padding:1.5rem;border-bottom:4px solid var(--gold);border-radius:12px 12px 0 0;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
          <div>
            <div style="font-size:.75rem;font-weight:700;color:var(--gold);letter-spacing:.08em;text-transform:uppercase">${o.code}</div>
            <div style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--navy);margin-top:.2rem;line-height:1.3">${o.title}</div>
          </div>
          <span class="badge badge-submitted" style="white-space:nowrap">${o.units} units</span>
        </div>
      </div>
      <div style="padding:1.25rem">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;font-size:.82rem">
          <div><span style="color:var(--slate)">Section</span><div style="font-weight:600;margin-top:.15rem">${o.section}</div></div>
          <div><span style="color:var(--slate)">Term</span><div style="font-weight:600;margin-top:.15rem">${o.term}</div></div>
          ${o.schedule ? `<div><span style="color:var(--slate)">Schedule</span><div style="font-weight:600;margin-top:.15rem">${o.schedule}</div></div>` : ''}
          ${o.room ? `<div><span style="color:var(--slate)">Room</span><div style="font-weight:600;margin-top:.15rem">${o.room}</div></div>` : ''}
        </div>
        <div style="margin-top:1.25rem;display:flex;gap:.75rem">
          <a href="assignments.php" class="btn btn-primary btn-sm" style="flex:1;justify-content:center">
            <i class="fas fa-clipboard-list"></i> Assignments
          </a>
          <a href="submissions.php" class="btn btn-outline btn-sm" style="flex:1;justify-content:center">
            <i class="fas fa-inbox"></i> Submissions
          </a>
        </div>
        <div style="margin-top:.75rem;display:flex;gap:.75rem">
          <button class="btn btn-outline btn-sm" style="flex:1;justify-content:center" onclick="openScheduleModal(${o.offering_id})">
            <i class="fas fa-calendar-alt"></i> ${o.schedule ? 'Edit Schedule' : 'Add Schedule'}
          </button>
        </div>
      </div>
    </div>`).join('')}
  </div>`;
}

async function loadInstructorEvents() {
  const el = document.getElementById('event-list');
  if (!el) return;

  el.innerHTML = '<div class="courses-loading"><span class="spinner"></span></div>';
  try {
    const res = await fetch(API + '?action=get_offering_events');
    const data = await res.json();
    const events = data.events || [];

    currentEventsMap = {};
    events.forEach(function(ev) {
      currentEventsMap[ev.schedule_id] = ev;
    });

    if (!events.length) {
      el.innerHTML = '<div class="empty-state" style="padding:3rem"><i class="fas fa-bell"></i><p>No published exam or activity schedules yet.</p></div>';
      return;
    }

    el.innerHTML = events.map((eventItem) => {
      const color = eventItem.color || '#1e3a5f';
      const startLabel = new Date(eventItem.starts_at).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
      const endLabel = new Date(eventItem.ends_at).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
      return `<div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;gap:1rem;align-items:flex-start">
        <div style="width:4px;background:${color};border-radius:4px;align-self:stretch;flex-shrink:0"></div>
        <div style="flex:1">
          <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start;flex-wrap:wrap">
            <div>
              <div style="font-weight:700;color:var(--navy)">${eventItem.title}</div>
              <div style="color:var(--slate);font-size:.8rem;margin-top:.2rem">
                <i class="fas fa-clock"></i> ${startLabel} – ${endLabel}
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem">
              <span class="badge" style="background:${color}22;color:${color}">${eventItem.type}</span>
              <button class="btn btn-xs btn-outline" style="padding:.25rem .5rem;font-size:.7rem" onclick="openEditEventModal('${eventItem.schedule_id}')" title="Edit"><i class="fas fa-pen"></i></button>
              <button class="btn btn-xs btn-danger-outline" style="padding:.25rem .5rem;font-size:.7rem" onclick="openDeleteEventModal('${eventItem.schedule_id}')" title="Delete"><i class="fas fa-trash"></i></button>
            </div>
          </div>
          ${eventItem.description ? `<div style="color:var(--slate);font-size:.8rem;margin-top:.35rem">${eventItem.description}</div>` : ''}
          <div style="color:var(--slate);font-size:.75rem;margin-top:.35rem">
            <i class="fas fa-users"></i> Published for ${eventItem.student_count} student${Number(eventItem.student_count) === 1 ? '' : 's'}
          </div>
        </div>
      </div>`;
    }).join('');
  } catch (error) {
    el.innerHTML = '<div class="empty-state" style="padding:3rem"><i class="fas fa-exclamation-circle"></i><p>Unable to load published events right now.</p></div>';
  }
}

function openScheduleModal(offeringId) {
  const offering = currentOfferings.find((item) => Number(item.offering_id) === Number(offeringId));
  if (!offering) return;

  document.getElementById('schedule-offering-id').value = offering.offering_id;
  document.getElementById('schedule-course-label').value = `${offering.code} — ${offering.title} (${offering.section})`;
  document.getElementById('schedule-value').value = offering.schedule || '';
  document.getElementById('room-value').value = offering.room || '';
  document.getElementById('schedule-modal').classList.add('show');
}

function closeScheduleModal() {
  document.getElementById('schedule-modal').classList.remove('show');
}

async function saveOfferingSchedule() {
  const offeringId = document.getElementById('schedule-offering-id').value;
  const schedule = document.getElementById('schedule-value').value.trim();
  const room = document.getElementById('room-value').value.trim();
  const btn = document.getElementById('save-schedule-btn');

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Saving...';

  const fd = new FormData();
  fd.append('action', 'update_offering_schedule');
  fd.append('offering_id', offeringId);
  fd.append('schedule', schedule);
  fd.append('room', room);

  try {
    const res = await fetch(API, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      toast(data.message || 'Schedule updated.');
      closeScheduleModal();
      loadCourses();
    } else {
      toast(data.message || 'Unable to update schedule.', 'error');
    }
  } catch (err) {
    toast('Unable to update schedule right now.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Save';
  }
}

  function populateEventOfferingOptions() {
    const select = document.getElementById('event-offering-id');
    if (!select) return;
    select.innerHTML = currentOfferings.map((o) => (
      `<option value="${o.offering_id}">${o.code} — ${o.title} (${o.section})</option>`
    )).join('');
  }

  function openEventModal() {
    if (!currentOfferings.length) {
      toast('No course offerings available.', 'error');
      return;
    }

    populateEventOfferingOptions();
    const now = new Date();
    now.setSeconds(0, 0);
    const start = new Date(now.getTime() + 60 * 60 * 1000);
    const end = new Date(start.getTime() + 60 * 60 * 1000);

    const pad = (n) => String(n).padStart(2, '0');
    const asLocal = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;

    document.getElementById('event-title').value = '';
    document.getElementById('event-description').value = '';
    document.getElementById('event-type').value = 'Exam';
    document.getElementById('event-color').value = '#1e3a5f';
    document.getElementById('event-start').value = asLocal(start);
    document.getElementById('event-end').value = asLocal(end);
    document.getElementById('event-modal').classList.add('show');
  }

  function closeEventModal() {
    const modal = document.getElementById('event-modal');
    if (modal) modal.classList.remove('show');
  }

  async function saveOfferingEvent() {
    const offeringId = document.getElementById('event-offering-id').value;
    const title = document.getElementById('event-title').value.trim();
    const description = document.getElementById('event-description').value.trim();
    const startsAt = document.getElementById('event-start').value;
    const endsAt = document.getElementById('event-end').value;
    const type = document.getElementById('event-type').value;
    const color = document.getElementById('event-color').value;
    const btn = document.getElementById('save-event-btn');

    if (!offeringId || !title || !startsAt || !endsAt) {
      toast('Please complete all required fields.', 'error');
      return;
    }

    const startDate = new Date(startsAt);
    const endDate = new Date(endsAt);
    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime()) || endDate <= startDate) {
      toast('End time must be later than start time.', 'error');
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Publishing...';

    const fd = new FormData();
    fd.append('action', 'create_offering_event');
    fd.append('offering_id', offeringId);
    fd.append('title', title);
    fd.append('description', description);
    fd.append('starts_at', startsAt);
    fd.append('ends_at', endsAt);
    fd.append('type', type);
    fd.append('color', color);

    try {
      const res = await fetch(API, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        toast(data.message || 'Schedule event published.');
        closeEventModal();
      } else {
        toast(data.message || 'Unable to publish event.', 'error');
      }
    } catch (err) {
      toast('Unable to publish event right now.', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-save"></i> Publish';
    }
  }

loadCourses();
loadInstructorEvents();

let editEventOfferingOptions = [];

function openEditEventModal(eventId) {
  const event = currentEventsMap ? currentEventsMap[eventId] : null;
  if (!event) return;

  document.getElementById('edit-event-id').value = eventId;
  document.getElementById('edit-event-title').value = event.title || '';
  document.getElementById('edit-event-description').value = event.description || '';
  document.getElementById('edit-event-type').value = event.type || 'Exam';
  document.getElementById('edit-event-color').value = event.color || '#1e3a5f';

  const pad = (n) => String(n).padStart(2, '0');
  const startsAt = new Date(event.starts_at);
  const endsAt = new Date(event.ends_at);
  document.getElementById('edit-event-start').value =
    `${startsAt.getFullYear()}-${pad(startsAt.getMonth() + 1)}-${pad(startsAt.getDate())}T${pad(startsAt.getHours())}:${pad(startsAt.getMinutes())}`;
  document.getElementById('edit-event-end').value =
    `${endsAt.getFullYear()}-${pad(endsAt.getMonth() + 1)}-${pad(endsAt.getDate())}T${pad(endsAt.getHours())}:${pad(endsAt.getMinutes())}`;

  document.getElementById('edit-event-modal').classList.add('show');
}

function closeEditEventModal() {
  document.getElementById('edit-event-modal').classList.remove('show');
}

async function saveEventEdit() {
  const eventId = document.getElementById('edit-event-id').value;
  const title = document.getElementById('edit-event-title').value.trim();
  const description = document.getElementById('edit-event-description').value.trim();
  const startsAt = document.getElementById('edit-event-start').value;
  const endsAt = document.getElementById('edit-event-end').value;
  const type = document.getElementById('edit-event-type').value;
  const color = document.getElementById('edit-event-color').value;
  const btn = document.getElementById('save-edit-event-btn');

  if (!eventId || !title || !startsAt || !endsAt) {
    toast('Please complete all required fields.', 'error');
    return;
  }

  const startDate = new Date(startsAt);
  const endDate = new Date(endsAt);
  if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime()) || endDate <= startDate) {
    toast('End time must be later than start time.', 'error');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Saving...';

  const fd = new FormData();
  fd.append('action', 'update_event');
  fd.append('event_id', eventId);
  fd.append('title', title);
  fd.append('description', description);
  fd.append('starts_at', startsAt);
  fd.append('ends_at', endsAt);
  fd.append('type', type);
  fd.append('color', color);

  try {
    const res = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      toast(data.message || 'Event updated.');
      closeEditEventModal();
      loadInstructorEvents();
    } else {
      toast(data.message || 'Unable to update event.', 'error');
    }
  } catch (err) {
    toast('Unable to update event right now.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
  }
}

function openDeleteEventModal(eventId) {
  const event = currentEventsMap ? currentEventsMap[eventId] : null;
  if (!event) return;
  document.getElementById('delete-event-id').value = eventId;
  document.getElementById('delete-event-name').textContent = event.title || 'this event';
  document.getElementById('delete-event-modal').classList.add('show');
}

function closeDeleteEventModal() {
  document.getElementById('delete-event-modal').classList.remove('show');
}

async function confirmDeleteEvent() {
  const eventId = document.getElementById('delete-event-id').value;
  const btn = document.getElementById('confirm-delete-event-btn');

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Deleting...';

  const fd = new FormData();
  fd.append('action', 'delete_event');
  fd.append('event_id', eventId);

  try {
    const res = await fetch(API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      toast(data.message || 'Event deleted.');
      closeDeleteEventModal();
      loadInstructorEvents();
    } else {
      toast(data.message || 'Unable to delete event.', 'error');
    }
  } catch (err) {
    toast('Unable to delete event right now.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-trash"></i> Delete';
  }
}
