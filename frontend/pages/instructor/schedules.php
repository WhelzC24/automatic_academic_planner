<?php
require_once __DIR__ . '/../../../backend/config/helpers.php';
requireAuth('instructor');
require_once __DIR__ . '/../layout.php';
layout_header('Schedules', 'instructor', [APP_URL . '/frontend/assets/css/pages/instructor/courses.css']);
?>
<div class="instructor-page-shell">
    <?php layout_sidebar('instructor', 'schedules'); ?>
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>Schedules</h1>
                <p>Add and edit class schedules for your assigned offerings</p>
            </div>
            <div class="topbar-actions">
                <a class="btn btn-outline" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <button class="btn btn-outline" onclick="openEventModal()"><i class="fas fa-calendar-plus"></i> Create Event</button>
                <a class="btn btn-primary" href="courses.php"><i class="fas fa-book-open"></i> My Courses</a>
            </div>
        </div>
        <div class="page-content">
            <div class="instructor-content-grid">
                <div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-calendar-alt"></i> Manage Class Schedules</div>
                        </div>
                        <div class="card-body" style="padding:0">
                            <div id="courses-grid">
                                <div class="courses-loading"><span class="spinner"></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="margin-top:1.25rem">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-bell"></i> Published Events</div>
                            <button class="btn btn-sm btn-outline" onclick="loadInstructorEvents()"><i class="fas fa-sync"></i></button>
                        </div>
                        <div class="card-body" style="padding:0">
                            <div id="event-list">
                                <div class="courses-loading"><span class="spinner"></span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="instructor-side">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-info-circle"></i> Notes</div>
                        </div>
                        <div class="card-body">
                            <p style="color:var(--slate);font-size:.88rem;line-height:1.6">
                                Use this page to set the published schedule and room for each course offering.
                                Students will see these details in their read-only schedule tab.
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div>
                        </div>
                        <div class="card-body quick-links">
                            <a class="btn btn-outline" href="courses.php"><i class="fas fa-book-open"></i> My Courses</a>
                            <a class="btn btn-outline" href="assignments.php"><i class="fas fa-clipboard-list"></i> Manage Assignments</a>
                            <a class="btn btn-outline" href="submissions.php"><i class="fas fa-inbox"></i> Review Submissions</a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="schedule-modal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Manage Class Schedule</div>
            <button class="modal-close" onclick="closeScheduleModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="schedule-offering-id">
            <div class="form-group">
                <label>Course</label>
                <input type="text" class="form-control" id="schedule-course-label" readonly>
            </div>
            <div class="form-group">
                <label>Schedule</label>
                <input type="text" class="form-control" id="schedule-value" placeholder="e.g., Mon/Wed 8:00 AM - 9:30 AM">
            </div>
            <div class="form-group">
                <label>Room</label>
                <input type="text" class="form-control" id="room-value" placeholder="e.g., Room 204">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeScheduleModal()">Cancel</button>
            <button class="btn btn-primary" id="save-schedule-btn" onclick="saveOfferingSchedule()">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="event-modal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Create Exam / Activity Schedule</div>
            <button class="modal-close" onclick="closeEventModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Course Offering *</label>
                <select class="form-control" id="event-offering-id"></select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Type *</label>
                    <select class="form-control" id="event-type">
                        <option value="Exam">Exam</option>
                        <option value="Activity">Activity</option>
                        <option value="Quiz">Quiz</option>
                        <option value="Presentation">Presentation</option>
                        <option value="Meeting">Meeting</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <input type="color" class="form-control planner-color-input" id="event-color" value="#1e3a5f">
                </div>
            </div>
            <div class="form-group">
                <label>Title *</label>
                <input type="text" class="form-control" id="event-title" placeholder="e.g., Midterm Exam">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" id="event-description" placeholder="Optional notes or instructions"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start *</label>
                    <input type="datetime-local" class="form-control" id="event-start">
                </div>
                <div class="form-group">
                    <label>End *</label>
                    <input type="datetime-local" class="form-control" id="event-end">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeEventModal()">Cancel</button>
            <button class="btn btn-primary" id="save-event-btn" onclick="saveOfferingEvent()"><i class="fas fa-save"></i> Publish</button>
        </div>
    </div>
</div>

<?php $coursesJsVersion = @filemtime(__DIR__ . '/../../assets/js/pages/instructor/courses.js') ?: time(); ?>
<script src="<?= APP_URL ?>/frontend/assets/js/pages/instructor/courses.js?v=<?= $coursesJsVersion ?>"></script>
<?php layout_footer(); ?>