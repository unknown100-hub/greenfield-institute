-- Database schema for GreenField Institute Student Registration System-- ============================================================
--  GreenField Institute — Database Schema
--  Compatible with: MySQL 8.0+ / MariaDB 10.5+
-- ============================================================

CREATE DATABASE IF NOT EXISTS greenfield_institute
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE greenfield_institute;

-- ------------------------------------------------------------
--  STUDENTS
--  Fields match index.html studentRegistrationForm:
--    username, registration_number, email, password
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    username            VARCHAR(60)     NOT NULL,
    registration_number VARCHAR(30)     NOT NULL,
    email               VARCHAR(255)    NOT NULL,
    password_hash       VARCHAR(255)    NOT NULL,          -- store bcrypt hash, NEVER plain text
    is_active           TINYINT(1)      NOT NULL DEFAULT 1,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                 ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_students_username           (username),
    UNIQUE KEY uq_students_registration_number(registration_number),
    UNIQUE KEY uq_students_email              (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
--  ADMINS
--  Fields match index.html adminRegistrationForm:
--    username, email, password
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    username      VARCHAR(60)     NOT NULL,
    email         VARCHAR(255)    NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,               -- bcrypt hash
    role          ENUM('admin','super_admin')
                                  NOT NULL DEFAULT 'admin',
    is_active     TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username),
    UNIQUE KEY uq_admins_email    (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
--  STUDENT SESSIONS
--  Created on successful student login; destroyed on logout.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_sessions (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id    INT UNSIGNED    NOT NULL,
    token         VARCHAR(255)    NOT NULL,               -- random secure token (e.g. bin2hex(random_bytes(32)))
    ip_address    VARCHAR(45)             DEFAULT NULL,   -- supports IPv6
    user_agent    TEXT                    DEFAULT NULL,
    expires_at    DATETIME        NOT NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_student_sessions_token (token),
    KEY fk_student_sessions_student (student_id),
    CONSTRAINT fk_student_sessions_student
        FOREIGN KEY (student_id) REFERENCES students (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
--  ADMIN SESSIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_sessions (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id    INT UNSIGNED    NOT NULL,
    token       VARCHAR(255)    NOT NULL,
    ip_address  VARCHAR(45)             DEFAULT NULL,
    user_agent  TEXT                    DEFAULT NULL,
    expires_at  DATETIME        NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_sessions_token (token),
    KEY fk_admin_sessions_admin (admin_id),
    CONSTRAINT fk_admin_sessions_admin
        FOREIGN KEY (admin_id) REFERENCES admins (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  SAMPLE PHP INTEGRATION NOTES (for api.php)
-- ============================================================
--
--  POST /api.php/students  (Student Registration)
--  ------------------------------------------------
--  1. Validate & sanitise all inputs server-side.
--  2. Check for duplicate username / registration_number / email.
--  3. Hash password:  $hash = password_hash($password, PASSWORD_BCRYPT);
--  4. INSERT INTO students (username, registration_number, email, password_hash)
--     VALUES (?, ?, ?, ?);
--  5. Return JSON { "success": true, "message": "..." }
--
--  POST /api.php/admin/register  (Admin Registration)
--  ------------------------------------------------
--  Same flow but INSERT INTO admins (username, email, password_hash).
--
--  POST /api.php/students/login
--  ------------------------------------------------
--  1. SELECT id, password_hash FROM students WHERE email = ? OR username = ?
--  2. password_verify($input, $row['password_hash'])
--  3. On success: generate token = bin2hex(random_bytes(32))
--     INSERT INTO student_sessions (student_id, token, ip_address, user_agent, expires_at)
--  4. Return token in response; client stores in localStorage / cookie.
--
--  POST /api.php/admin/login  — same pattern using admin_sessions.
--
--  SECURITY REMINDERS
--  ------------------
--  • Always use prepared statements (PDO or MySQLi) — no string concatenation.
--  • Set CORS headers appropriately; restrict to your domain in production.
--  • Enforce HTTPS in production.
--  • Expire sessions (e.g. expires_at = NOW() + INTERVAL 8 HOUR).
--  • Purge expired sessions periodically (cron or on-login cleanup).
-- ============================================================

-- Create database
CREATE DATABASE IF NOT EXISTS `greenfield institute`;
USE `green field institute`;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               
-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    credits INT NOT NULL
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    name VARCHAR(255) NULL,
    registration_number VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    date_of_birth DATE NULL,
    course_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- Student course registrations table
CREATE TABLE IF NOT EXISTS student_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_course (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- If you already created the old students table, run these ALTER statements once.
ALTER TABLE students ADD COLUMN IF NOT EXISTS username VARCHAR(255) NULL AFTER id;
ALTER TABLE students ADD COLUMN IF NOT EXISTS name VARCHAR(255) NULL AFTER username;
ALTER TABLE students ADD COLUMN IF NOT EXISTS registration_number VARCHAR(100) NULL UNIQUE AFTER name;
ALTER TABLE students ADD COLUMN IF NOT EXISTS password VARCHAR(255) NULL AFTER registration_number;
ALTER TABLE students MODIFY phone VARCHAR(20) NULL;
ALTER TABLE students MODIFY address TEXT NULL;
ALTER TABLE students MODIFY date_of_birth DATE NULL;
ALTER TABLE students MODIFY course_id INT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS student_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_course (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a default admin (password: admin123)
INSERT INTO admins (username, password, email) VALUES
('admin', '$2y$10$eSW89scFyhjHrhFFvq58Ae1zWGdUkrJg7zmQrAjUW3FF2rLX9j89a', 'admin@greenfield.edu')
ON DUPLICATE KEY UPDATE password=VALUES(password), email=VALUES(email);

-- Seed courses from XML catalog
INSERT IGNORE INTO courses (id, code, title, description, credits) VALUES
(1, 'CS101', 'Introduction to Programming', 'Learn the fundamentals of programming and logic using PHP.', 3),
(2, 'WEB202', 'Web Development Fundamentals', 'Build websites with HTML, CSS, JavaScript and server-side PHP.', 4),
(3, 'DB303', 'Database Design', 'Design relational databases and interact with MySQL using PHP.', 3);
