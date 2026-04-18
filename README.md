# BISU Calape Campus — Automated Daily Academic Planner
## Thesis Project Documentation & Setup Guide

---

## SYSTEM OVERVIEW

**Title:** Automated Daily Academic Planner with Deadline Tracking in BISU Calape Campus  
**Stack:** PHP 8.x · MySQL 8 · HTML5 / CSS3 / Vanilla JavaScript  
**Author:** [Your Name]  
**Year:** 2024–2025

---

## PROJECT STRUCTURE

```
bisu_planner/
├── index.php                          ← Root redirect
├── uploads/                           ← Student file submissions
│   └── .htaccess                      ← Security rules
├── database/
│   └── schema.sql                     ← Full database schema + seed data
├── backend/
│   ├── config/
│   │   ├── database.php               ← DB connection + constants
│   │   └── helpers.php                ← Auth, session, utilities
│   ├── auth/
│   │   └── auth_handler.php           ← Login / Register / Logout
│   ├── student/
│   │   └── student_api.php            ← All student API endpoints
│   ├── instructor/
│   │   └── instructor_api.php         ← All instructor API endpoints
│   └── admin/
│       └── admin_api.php              ← All admin API endpoints
└── frontend/
    └── pages/
        ├── login.php                  ← Login + Register page
        ├── unauthorized.php           ← Access denied page
        ├── layout.php                 ← Shared sidebar + header + footer
        ├── student/
        │   ├── dashboard.php          ← Student dashboard
        │   ├── tasks.php              ← Assignment-linked task tracker
        │   ├── assignments.php        ← View & submit assignments
        │   ├── planner.php            ← Personal schedules + read-only class/events tab
        │   ├── schedule.php           ← Weekly/monthly schedule view
        │   └── notifications.php     ← Notification center
        ├── instructor/
        │   ├── dashboard.php          ← Instructor dashboard
        │   ├── courses.php            ← My course offerings
        │   ├── assignments.php        ← Create & manage assignments
        │   ├── schedules.php          ← Class schedule + published events manager
        │   └── submissions.php        ← View & grade submissions
        └── admin/
            ├── dashboard.php          ← Admin overview + stats
            ├── users.php              ← User management
            ├── courses.php            ← Course & offering management
            └── logs.php               ← System activity logs
```

---

## INSTALLATION STEPS

### Step 1 — Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | 8.0+    |
| MySQL       | 8.0+    |
| Apache      | 2.4+    |
| XAMPP/WAMP  | Latest  |

### Step 2 — Copy Project Files

Copy the entire `bisu_planner/` folder to:
- **XAMPP:** `C:/xampp/htdocs/bisu_planner/`
- **WAMP:**  `C:/wamp64/www/bisu_planner/`

### Step 3 — Create the Database

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **"New"** → Name it `academic_planner` → Create
3. Click the `academic_planner` database → **Import** tab
4. Browse to `database/schema.sql` → Click **Go**

### Step 4 — Configure Database Connection

Open `backend/config/database.php` and update:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'academic_planner');
define('DB_USER', 'root');         // your MySQL username
define('DB_PASS', '');             // your MySQL password
define('APP_URL', 'http://localhost/bisu_planner');
```

### Step 5 — Create Upload Folder Permissions

Ensure the `uploads/` folder exists and is writable:
```
uploads/
└── submissions/   ← auto-created on first submission
```

On Linux/Mac: make sure the web server user can write to `uploads/` and `uploads/submissions/`.
If you are developing locally, `chmod -R 775 uploads/` is usually enough; some setups may require ownership or broader permissions.

### Step 6 — Access the Application

Open your browser → `http://localhost/bisu_planner`

---

## DEFAULT ACCOUNTS

| Role       | Username | Password   | Notes                              |
|------------|----------|------------|------------------------------------|
| Admin      | admin    | *(set up)* | Change password after first login  |

> **How to set admin password:**
> For fresh installs (editing seed data):
> 1. Generate a bcrypt hash in terminal:
>    php -r "echo password_hash('YourPassword123', PASSWORD_BCRYPT, ['cost'=>12]), PHP_EOL;"
> 2. Open `database/schema.sql` and replace the admin `password_hash` value in the default admin insert.
> 3. Import the schema.
>
> For already-installed systems:
> 1. Generate the bcrypt hash using the same command above.
> 2. Update the existing admin row in MySQL:
>    UPDATE users SET password_hash = 'PASTE_BCRYPT_HASH_HERE' WHERE username = 'admin';
>
> Note: Do not use SHA2 for `password_hash`. This project uses PHP `password_hash()` / `password_verify()` with bcrypt.

---

## USER ROLES & FEATURES

### 👨‍🎓 STUDENT
| Feature | Description |
|---------|-------------|
| Dashboard | Today's tasks, upcoming deadlines, notification count, quick task completion |
| Tasks | Assignment-linked task tracker with status updates |
| Assignments | View all course assignments, submit files, check grades & feedback |
| Planner | Personal schedules plus read-only class schedules and instructor events |
| Schedule | Weekly grid view + monthly list of class/study/meeting/exam/activity/quiz/presentation schedules |
| Notifications | Deadline reminders (3-day, 1-day, due-day), new assignment alerts, submission confirmations |

### 👨‍🏫 INSTRUCTOR
| Feature | Description |
|---------|-------------|
| Dashboard | Course overview, recent submissions |
| My Courses | View assigned course offerings with details |
| Assignments | Create assignments with description/due date/max score; auto-notifies enrolled students and syncs planner tasks |
| Schedules | Edit class schedule details and publish exams, activities, quizzes, and presentations |
| Submissions | View all submissions per assignment; download files; grade with feedback |

### 🛠 ADMIN
| Feature | Description |
|---------|-------------|
| Dashboard | System stats (students, instructors, courses, assignments, submissions) |
| User Management | Add students/instructors; activate/deactivate/delete accounts; reset student/instructor passwords to default `12345` |
| Course Management | Create courses, add offerings with term/section/schedule/room, assign instructors, enroll students |
| System Logs | Full audit trail of all actions with timestamps, usernames, IP addresses |

---

## DATABASE TABLES

| Table | Description |
|-------|-------------|
| `users` | All system accounts (students, instructors, admins), including `must_change_password` flag for forced password updates |
| `students` | Student-specific data (student number, program, year) |
| `instructors` | Instructor-specific data (department, designation) |
| `admins` | Admin-specific data |
| `courses` | Course catalog |
| `course_offerings` | Specific course sections per term |
| `enrollments` | Student-to-offering relationships |
| `teaching_assignments` | Instructor-to-offering relationships |
| `assignments` | Coursework created by instructors for course offerings |
| `submissions` | Student file submissions for assignments |
| `tasks` | Assignment-linked planner tasks for students |
| `schedules` | Calendar events (class, study, personal, meeting, exam, activity, quiz, presentation) |
| `notifications` | System-generated alerts (deadline, assignment, submission) |
| `system_logs` | Audit trail of all system actions |

---

## SECURITY FEATURES

- **Password hashing:** PHP `password_hash()` with BCRYPT (cost=12)
- **Forced password reset flow:** Admin reset sets default password `12345` and requires user to change password on next login
- **SQL injection prevention:** PDO prepared statements throughout
- **Session security:** `session_regenerate_id()` on login, HttpOnly cookies
- **Role-based access control:** `requireAuth('role')` on every protected page
- **File upload security:** Extension whitelist, size limit (10MB), random filenames
- **Upload directory protection:** `.htaccess` blocks PHP execution in uploads folder
- **XSS prevention:** `htmlspecialchars()` on all output via `e()` helper

---

## AUTOMATED FEATURES

### Deadline Reminders (Auto-generated)
- ✅ 3 days before assignment due date
- ✅ 1 day before assignment due date  
- ✅ On the due date
- Triggered automatically on each login

### Assignment Task Sync (Auto)
- Assignment-linked planner tasks are created automatically for enrolled students
- Task details stay in sync when assignments are updated
- Submitting an assignment marks the linked planner task as completed or overdue

### Overdue Task Detection (Auto)
- Tasks past their due date are automatically marked `Overdue`
- Runs on every student login

### Assignment Notifications (Auto)
- When instructor creates an assignment → all enrolled students receive a notification instantly
- When instructor publishes an event in the Schedules page → students can see it in the read-only planner tab

---

## API ENDPOINTS

### Student API (`backend/student/student_api.php`)
| Action | Method | Description |
|--------|--------|-------------|
| `get_dashboard` | GET | Dashboard data (tasks, deadlines, stats) |
| `get_tasks` | GET | Assignment-linked tasks for the student |
| `mark_task_status` | POST | Quick status update |
| `get_schedules` | GET | Get schedules for date range |
| `add_schedule` | POST | Add schedule event |
| `get_readonly_schedules` | GET | Student view of class schedules and instructor events |
| `get_assignments` | GET | All course assignments with submission status |
| `submit_assignment` | POST | Upload file submission |
| `get_notifications` | GET | All notifications |
| `get_unread_notif_count` | GET | Unread notification badge count |
| `mark_notification_read` | POST | Mark one as read |
| `mark_all_read` | POST | Mark all as read |

### Instructor API (`backend/instructor/instructor_api.php`)
| Action | Method | Description |
|--------|--------|-------------|
| `get_dashboard` | GET | Offerings + recent submissions |
| `get_my_offerings` | GET | All course offerings assigned to the instructor |
| `create_assignment` | POST | New assignment (auto-notifies students) |
| `update_assignment` | POST | Edit assignment |
| `delete_assignment` | POST | Delete assignment |
| `get_submissions` | GET | Submissions for specific assignment |
| `grade_submission` | POST | Save grade + feedback |
| `update_offering_schedule` | POST | Update class schedule and room for an offering |
| `create_offering_event` | POST | Publish an exam/activity/quiz/presentation to enrolled students |
| `get_offering_events` | GET | List published events per offering |

### Admin API (`backend/admin/admin_api.php`)
| Action | Method | Description |
|--------|--------|-------------|
| `get_stats` | GET | System statistics + recent logs |
| `get_users` | GET | List users (filter by role) |
| `add_student` | POST | Create student account |
| `add_instructor` | POST | Create instructor account |
| `toggle_user` | POST | Activate/deactivate user |
| `delete_user` | POST | Delete user account |
| `reset_user_password` | POST | Reset student/instructor password to `12345` and force change on next login |
| `get_courses` | GET | All courses with offering count |
| `add_course` | POST | Create new course |
| `add_offering` | POST | Create course offering |
| `enroll_student` | POST | Enroll student in offering |
| `get_logs` | GET | System activity logs |

---

## WORKFLOW — TYPICAL USAGE

### Admin Setup Workflow
1. Login as admin → Create courses
2. Add course offerings (term, section, room, schedule)
3. Add instructor accounts → Assign to offerings
4. Add student accounts → Enroll in offerings

### Admin Password Reset Flow
1. Login as admin and open User Management.
2. Find a student or instructor account and click the reset password button (key icon).
3. Confirm reset to set password to `12345`.
4. User logs in using `12345`.
5. System shows a required change-password popup before dashboard access.
6. User enters new password and confirm password.
7. On success, `must_change_password` is cleared and the user is redirected to their dashboard.

### Instructor Workflow
1. Login → View your course offerings
2. Create assignments with due dates and instructions
3. Students receive automatic notifications
4. View/download/grade submissions with feedback

### Student Workflow
1. Register or login with admin-created account
2. View dashboard → see today's tasks and upcoming deadlines
3. Add personal schedules and review instructor-published class events in the planner
4. View assignments → upload submissions before deadline
5. Check notifications for reminders and updates
6. View grades and instructor feedback

---

## CUSTOMIZATION

### Change School Name
In `frontend/pages/layout.php`, find:
```html
<h3>BISU Planner</h3>
<p>Calape Campus</p>
```

### Add More Programs
Update the `program` field in user registration to use a `<select>` with BISU program options.

### Email Notifications (Future Enhancement)
Add PHP Mailer or SMTP in `helpers.php` `generateDeadlineNotifications()` function to also send email.

---

## TECH STACK SUMMARY

| Layer | Technology |
|-------|------------|
| Frontend | HTML5, CSS3, Vanilla JavaScript (ES6+) |
| Styling | Custom CSS with CSS Variables (no Bootstrap required) |
| Icons | Font Awesome 6.5 |
| Fonts | Playfair Display + DM Sans (Google Fonts) |
| Backend | PHP 8.x (PDO, OOP-style functions) |
| Database | MySQL 8 (InnoDB, Foreign Keys, Transactions) |
| Auth | PHP Sessions + BCRYPT password hashing |
| File Upload | PHP `move_uploaded_file()` with validation |

---

*BISU Calape Campus Academic Planner — Thesis Project 2026–2027*
