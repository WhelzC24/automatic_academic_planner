# BISU Calape Campus Academic Planner
## Thesis Project Documentation and Setup Guide

## System Overview

**Title:** Automated Daily Academic Planner with Deadline Tracking in BISU Calape Campus  
**Stack:** PHP 8.x, MySQL, HTML5, CSS3, Vanilla JavaScript  
**Campus:** BISU Calape  
**Scope:** Student, Instructor, and Admin academic workflows

This README reflects the current implementation in this repository, including the latest enrollment, schedule, coursework, and notification flows.

## Project Structure

```text
bisu_planner/
|- index.php
|- setup.php
|- README.md
|- .gitignore
|- uploads/
|  |- .htaccess
|  `- submissions/ (auto-created)
|- database/
|  `- schema.sql
|- backend/
|  |- config/
|  |  |- database.php
|  |  `- helpers.php
|  |- auth/
|  |  `- auth_handler.php
|  |- api/
|  |  `- profile_api.php
|  |- student/
|  |  `- student_api.php
|  |- instructor/
|  |  `- instructor_api.php
|  |- admin/
|  |  `- admin_api.php
|  `- cron/
|     `- scheduler.php
`- frontend/
   |- assets/
   |  |- css/
   |  |  `- app.css
   |  `- js/
   |     `- pages/
   |- pages/
   |  |- layout.php
   |  |- login.php
   |  |- profile.php
   |  |- unauthorized.php
   |  |- student/
   |  |  |- dashboard.php
   |  |  |- coursework.php
   |  |  |- schedule.php
   |  |  `- notifications.php
   |  |- instructor/
   |  |  |- dashboard.php
   |  |  |- courses.php
   |  |  |- schedules.php
   |  |  |- assignments.php
   |  |  |- submissions.php
   |  |  `- notifications.php
   |  `- admin/
   |     |- dashboard.php
   |     |- users.php
   |     |- courses.php
   |     |- enrollments.php
   |     `- logs.php
   `- img/
```

## Installation

### 1. Requirements

| Requirement | Version |
|---|---|
| PHP | 8.0+ |
| MySQL / MariaDB | 10+ / 8+ |
| Apache | 2.4+ |
| XAMPP/WAMP/LAMP | Latest stable |

### 2. Place Project Folder

Copy `bisu_planner` into your web root.

- XAMPP: `C:/xampp/htdocs/bisu_planner/`
- WAMP: `C:/wamp64/www/bisu_planner/`
- Linux Apache: usually `/var/www/html/bisu_planner/`

### 3. Create Database and Import Schema

1. Create database: `academic_planner`
2. Import `database/schema.sql`

### 4. Configure Database Connection

Edit `backend/config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'academic_planner');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_URL', 'http://localhost/bisu_planner');
```

### 5. Configure Upload Permissions

`uploads` must be writable by the web server user.

```bash
chmod -R 775 uploads/
```

`.gitignore` is configured to ignore uploaded files while keeping `uploads/.htaccess` tracked.

### 6. Open the App

Visit: `http://localhost/bisu_planner`

## Default Account

A default admin account is seeded in `database/schema.sql`.

| Role | Username |
|---|---|
| Admin | `admin` |

If needed, regenerate admin password hash using PHP `password_hash()` and update the seeded hash before importing.

## Current Role Features

### Student

- Dashboard with task stats, unread notifications, today tasks, and upcoming deadlines
- Coursework page for assignment tracking and submission upload
- Schedule page with:
  - personal schedules (create, update, delete)
  - week view
  - read-only class schedules and instructor-published events
- Notifications center (single mark read, mark all read)
- Profile management and password update

### Instructor

- Dashboard with assigned offerings and recent submissions
- My Courses view
- Assignments management (create, update, delete)
- Submissions review and grading
- Schedules page:
  - update class schedule and room per offering
  - create, edit, and delete offering events (exam/activity/quiz/presentation/meeting)
- Notifications center
- Profile management and password update

### Admin

- Dashboard stats and recent logs
- User management:
  - add student/instructor
  - activate/deactivate user
  - delete user
  - reset student/instructor password to default
- Course and offering management:
  - add/update course
  - add/update/delete offering
  - assign instructor
- Enrollment management:
  - enroll student by course + section (offering)
  - view and filter enrollments
  - unenroll student
- System logs and system summary endpoints

## Automation and System Behavior

- Assignment-to-task sync for students is maintained in student and instructor flows
- Overdue task marking and deadline notifications are auto-triggered in active flows
- Assignment submissions update linked task status
- Instructor event publishing creates student schedule entries and notifications
- Cleanup endpoint exists to remove expired instructor-created schedule events

## Database Tables

| Table | Purpose |
|---|---|
| `users` | Base accounts and auth fields |
| `students` | Student-specific profile data |
| `instructors` | Instructor-specific profile data |
| `admins` | Admin metadata |
| `courses` | Course catalog |
| `course_offerings` | Term/section-based offerings |
| `enrollments` | Student to offering relationships |
| `teaching_assignments` | Instructor to offering relationships |
| `assignments` | Coursework per offering |
| `submissions` | Student assignment submissions |
| `tasks` | Assignment-linked planner tasks |
| `schedules` | Personal and instructor-published schedule events |
| `notifications` | User alerts and reminders |
| `system_logs` | Audit trail of actions |

## Security Highlights

- Password hashing with bcrypt (`password_hash`, `password_verify`)
- Role-protected pages and APIs via `requireAuth(...)`
- PDO prepared statements used across handlers
- Upload validation: extension allowlist + size cap + unique filenames
- Upload execution hardening via `uploads/.htaccess`
- Force-change-password flow supported (`must_change_password`)

## API Endpoints

All APIs use `action` parameters.

### Auth API (`backend/auth/auth_handler.php`)

| Action | Method | Description |
|---|---|---|
| `login` | POST | Login by username/email and password |
| `register` | POST | Register student account |
| `change_password_required` | POST | Complete forced password change |
| `logout` | POST | Logout and destroy session |

### Profile API (`backend/api/profile_api.php`)

| Action | Method | Description |
|---|---|---|
| `get_profile` | GET | Get current user profile |
| `update_profile` | POST | Update profile fields by role |
| `change_password` | POST | Change current user password |

### Student API (`backend/student/student_api.php`)

| Action | Method | Description |
|---|---|---|
| `get_dashboard` | GET | Dashboard payload (today tasks, upcoming, stats, unread count) |
| `get_unread_notif_count` | GET | Unread notification count |
| `mark_task_status` | POST | Update task status (assignment-linked quick action) |
| `get_schedules` | GET | Personal schedule list by date range |
| `get_readonly_schedules` | GET | Read-only class schedules and instructor events |
| `add_schedule` | POST | Add personal schedule |
| `update_schedule` | POST | Update personal schedule |
| `delete_schedule` | POST | Delete personal schedule (with ownership checks) |
| `cleanup_expired_schedules` | POST/GET | Cleanup expired instructor-created events |
| `get_assignments` | GET | Coursework list with submission/task metadata |
| `submit_assignment` | POST | Upload assignment submission |
| `get_notifications` | GET | List notifications |
| `mark_notification_read` | POST | Mark single notification read |
| `mark_all_read` | POST | Mark all notifications read |

### Instructor API (`backend/instructor/instructor_api.php`)

| Action | Method | Description |
|---|---|---|
| `get_dashboard` | GET | Offerings and recent submissions |
| `get_my_assignments` | GET | Instructor assignment list |
| `create_assignment` | POST | Create assignment and notify students |
| `update_assignment` | POST | Update assignment and sync linked tasks |
| `delete_assignment` | POST | Delete assignment |
| `get_submissions` | GET | Get submissions for assignment |
| `grade_submission` | POST | Save grade and feedback |
| `get_my_offerings` | GET | Instructor offerings |
| `update_offering_schedule` | POST | Update offering schedule and room |
| `create_offering_event` | POST | Publish event to enrolled students |
| `get_offering_events` | GET | List published events |
| `get_single_event` | GET | Fetch single event |
| `update_event` | POST | Update event |
| `delete_event` | POST | Delete event |
| `get_notifications` | GET | List instructor notifications |
| `mark_notification_read` | POST | Mark one notification read |
| `mark_all_read` | POST | Mark all notifications read |
| `get_unread_notif_count` | GET | Unread count |

### Admin API (`backend/admin/admin_api.php`)

| Action | Method | Description |
|---|---|---|
| `get_stats` | GET | Dashboard stats and recent logs |
| `get_users` | GET | Users list (optional role filter) |
| `add_student` | POST | Create student account |
| `add_instructor` | POST | Create instructor account |
| `toggle_user` | POST | Activate/deactivate account |
| `delete_user` | POST | Delete account |
| `reset_user_password` | POST | Reset password and force change |
| `get_courses` | GET | List courses |
| `add_course` | POST | Create course |
| `update_course` | POST | Update course |
| `add_offering` | POST | Create offering |
| `update_offering` | POST | Update offering and instructor assignment |
| `delete_offering` | POST | Delete offering |
| `delete_course` | POST | Delete course (with dependency checks) |
| `assign_instructor` | POST | Assign/update instructor for offering |
| `enroll_student` | POST | Enroll student in offering |
| `get_enrollments` | GET | Get enrollment records |
| `unenroll_student` | POST | Remove enrollment |
| `get_offering_list` | GET | Offerings list with section and instructor |
| `get_system_summary` | GET | Row counts for key tables |
| `get_logs` | GET | System logs list |

## Typical Workflows

### Admin Setup

1. Create courses
2. Create offerings (term, section, schedule, room)
3. Add instructors and assign offerings
4. Add students
5. Enroll students by selecting course then section

### Instructor Flow

1. Review assigned offerings
2. Set offering schedule/room
3. Create assignments
4. Publish class events (exam/activity/quiz/presentation/meeting)
5. Review and grade submissions

### Student Flow

1. Login and open dashboard
2. Track deadlines and tasks
3. Submit coursework files
4. Manage personal schedules and view read-only class schedules
5. Check notifications and profile updates

## UI Notes (Current)

- Login page provides Sign In and Register tabs in one screen
- Marketing panel highlights planner, reminders, submissions, progress, and instructor-student connection
- Role sidebars include notifications and profile access

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Backend | PHP 8.x |
| DB | MySQL / MariaDB (InnoDB, foreign keys) |
| Auth | PHP sessions + bcrypt |
| Icons | Font Awesome |
| Fonts | Google Fonts (Playfair Display, DM Sans) |

BISU Calape Campus Academic Planner
