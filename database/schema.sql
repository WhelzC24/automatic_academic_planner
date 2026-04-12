-- ============================================================
-- BISU Calape Campus - Automated Daily Academic Planner
-- Database Schema v1.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS academic_planner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE academic_planner;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE users (
    user_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50) NOT NULL UNIQUE,
    email       VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name  VARCHAR(60) NOT NULL,
    last_name   VARCHAR(60) NOT NULL,
    phone       VARCHAR(20),
    role        ENUM('student','instructor','admin') NOT NULL DEFAULT 'student',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- STUDENTS TABLE
-- ============================================================
CREATE TABLE students (
    user_id         INT UNSIGNED PRIMARY KEY,
    student_number  VARCHAR(20) NOT NULL UNIQUE,
    program         VARCHAR(100) NOT NULL,
    year_level      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    gpa             DECIMAL(4,2),
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- INSTRUCTORS TABLE
-- ============================================================
CREATE TABLE instructors (
    user_id         INT UNSIGNED PRIMARY KEY,
    department      VARCHAR(100) NOT NULL,
    designation     VARCHAR(100),
    office_location VARCHAR(100),
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ADMINS TABLE
-- ============================================================
CREATE TABLE admins (
    user_id     INT UNSIGNED PRIMARY KEY,
    department  VARCHAR(100),
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- COURSES TABLE
-- ============================================================
CREATE TABLE courses (
    course_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) NOT NULL UNIQUE,
    title       VARCHAR(150) NOT NULL,
    description TEXT,
    units       TINYINT UNSIGNED NOT NULL DEFAULT 3,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- COURSE OFFERINGS TABLE
-- ============================================================
CREATE TABLE course_offerings (
    offering_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id   INT UNSIGNED NOT NULL,
    term        VARCHAR(30) NOT NULL,
    section     VARCHAR(30) NOT NULL,
    schedule    VARCHAR(100),
    room        VARCHAR(50),
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ENROLLMENTS TABLE
-- ============================================================
CREATE TABLE enrollments (
    enrollment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offering_id   INT UNSIGNED NOT NULL,
    student_id    INT UNSIGNED NOT NULL,
    enrolled_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrollment (offering_id, student_id),
    FOREIGN KEY (offering_id) REFERENCES course_offerings(offering_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)  REFERENCES students(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TEACHING ASSIGNMENTS TABLE
-- ============================================================
CREATE TABLE teaching_assignments (
    ta_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offering_id   INT UNSIGNED NOT NULL,
    instructor_id INT UNSIGNED NOT NULL,
    assigned_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teaching (offering_id, instructor_id),
    FOREIGN KEY (offering_id)   REFERENCES course_offerings(offering_id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES instructors(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ASSIGNMENTS TABLE
-- ============================================================
CREATE TABLE assignments (
    assignment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offering_id   INT UNSIGNED NOT NULL,
    created_by    INT UNSIGNED NOT NULL,
    title         VARCHAR(200) NOT NULL,
    description   TEXT,
    instructions_file VARCHAR(255),
    due_at        DATETIME NOT NULL,
    max_score     DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offering_id) REFERENCES course_offerings(offering_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)  REFERENCES users(user_id)
) ENGINE=InnoDB;

-- ============================================================
-- SUBMISSIONS TABLE
-- ============================================================
CREATE TABLE submissions (
    submission_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    student_id    INT UNSIGNED NOT NULL,
    submitted_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    file_path     VARCHAR(500),
    status        ENUM('submitted','late','graded','returned') NOT NULL DEFAULT 'submitted',
    grade         DECIMAL(5,2),
    feedback      TEXT,
    UNIQUE KEY uq_submission (assignment_id, student_id),
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)    REFERENCES students(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TASKS TABLE (Daily Planner)
-- ============================================================
CREATE TABLE tasks (
    task_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    assignment_id INT UNSIGNED,
    task_name    VARCHAR(200) NOT NULL,
    description  TEXT,
    due_at       DATETIME NOT NULL,
    priority     ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
    status       ENUM('Pending','In Progress','Completed','Overdue') NOT NULL DEFAULT 'Pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)       REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SCHEDULES TABLE
-- ============================================================
CREATE TABLE schedules (
    schedule_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    starts_at   DATETIME NOT NULL,
    ends_at     DATETIME NOT NULL,
    type        ENUM('Class','Study','Personal','Meeting') NOT NULL DEFAULT 'Personal',
    color       VARCHAR(7) DEFAULT '#4f46e5',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    task_id         INT UNSIGNED,
    assignment_id   INT UNSIGNED,
    schedule_id     INT UNSIGNED,
    type            ENUM('Deadline Reminder','Assignment Posted','Submission Confirmation','Schedule Reminder') NOT NULL,
    message         TEXT NOT NULL,
    sent_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at         DATETIME,
    FOREIGN KEY (user_id)       REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (task_id)       REFERENCES tasks(task_id) ON DELETE SET NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE SET NULL,
    FOREIGN KEY (schedule_id)   REFERENCES schedules(schedule_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SYSTEM LOGS TABLE
-- ============================================================
CREATE TABLE system_logs (
    log_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED,
    action      VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address  VARCHAR(45),
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default Admin
INSERT INTO users (username, email, password_hash, first_name, last_name, role)
VALUES ('admin', 'admin@bisu-calape.edu.ph', '$2y$12$A/sGmX01vubAMUdm6cZQjOpUjZ6hfJu.KSrisq/ipQXQ06f2cY9ly', 'System', 'Administrator', 'admin');

INSERT INTO admins (user_id, department) VALUES (1, 'ICT Department');

-- Sample Courses
INSERT INTO courses (code, title, description, units) VALUES
('CS101', 'Introduction to Computing', 'Fundamentals of computing and programming', 3),
('CS201', 'Data Structures & Algorithms', 'Core data structures and algorithm design', 3),
('MATH101', 'College Algebra', 'Algebraic operations, equations, and functions', 3),
('ENG101', 'Technical Writing', 'Academic and technical writing skills', 3),
('CS301', 'Database Management Systems', 'Relational databases, SQL, and design principles', 3);
