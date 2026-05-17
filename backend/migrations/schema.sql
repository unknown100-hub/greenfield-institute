-- ============================================================
-- GreenField Institute - Production-Ready Relational Schema
-- Compatible with MySQL 8.0+ / MariaDB 10.5+
--
-- Import with:
--   Get-Content backend\migrations\schema.sql | C:\xampp\mysql\bin\mysql.exe -u root -P 4306
--
-- Default admin:
--   email/username: admin@greenfield.edu or admin
--   password:       admin123
-- ============================================================

CREATE DATABASE IF NOT EXISTS greenfield_institute
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE greenfield_institute;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Courses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS courses (
    id          INT NOT NULL AUTO_INCREMENT,
    code        VARCHAR(50)       NOT NULL,
    title       VARCHAR(255)      NOT NULL,
    description TEXT              NOT NULL,
    credits     TINYINT UNSIGNED  NOT NULL,
    capacity    SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    instructor  VARCHAR(150)      NOT NULL DEFAULT 'To be assigned',
    schedule    VARCHAR(120)      NOT NULL DEFAULT 'To be announced',
    is_active   TINYINT(1)        NOT NULL DEFAULT 1,
    created_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_courses_code (code),
    KEY idx_courses_title (title),
    KEY idx_courses_active_code (is_active, code),
    CHECK (credits BETWEEN 1 AND 12),
    CHECK (capacity BETWEEN 1 AND 500)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Students
-- `password` stores PHP password_hash() output.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id         INT NOT NULL AUTO_INCREMENT,
    full_name  VARCHAR(150) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    password   VARCHAR(255) NOT NULL,
    course_id  INT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_students_email (email),
    KEY idx_students_name (full_name),
    KEY idx_students_course_id (course_id),
    CONSTRAINT fk_students_course
        FOREIGN KEY (course_id) REFERENCES courses (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Admins
-- `password` stores PHP password_hash() output.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id         INT NOT NULL AUTO_INCREMENT,
    username   VARCHAR(100) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin', 'super_admin') NOT NULL DEFAULT 'admin',
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username),
    UNIQUE KEY uq_admins_email (email),
    KEY idx_admins_active_role (is_active, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Student course registrations
-- Many-to-many enrollment history with one row per student/course.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_courses (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id    INT             NOT NULL,
    course_id     INT             NOT NULL,
    status        ENUM('registered', 'dropped', 'completed') NOT NULL DEFAULT 'registered',
    registered_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_student_courses_student_course (student_id, course_id),
    KEY idx_student_courses_student_id (student_id),
    KEY idx_student_courses_course_id (course_id),
    KEY idx_student_courses_status (status),
    CONSTRAINT fk_student_courses_student
        FOREIGN KEY (student_id) REFERENCES students (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_student_courses_course
        FOREIGN KEY (course_id) REFERENCES courses (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Compatibility repairs for databases created from older schemas
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS gf_copy_column_if_exists;
DROP PROCEDURE IF EXISTS gf_modify_column_if_exists;
DROP PROCEDURE IF EXISTS gf_add_column_if_missing;
DROP PROCEDURE IF EXISTS gf_add_index_if_missing;

DELIMITER //

CREATE PROCEDURE gf_add_column_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND COLUMN_NAME = p_column_name
    ) THEN
        SET @gf_sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN ', p_column_definition);
        PREPARE gf_stmt FROM @gf_sql;
        EXECUTE gf_stmt;
        DEALLOCATE PREPARE gf_stmt;
    END IF;
END//

CREATE PROCEDURE gf_modify_column_if_exists(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND COLUMN_NAME = p_column_name
    ) THEN
        SET @gf_sql = CONCAT('ALTER TABLE `', p_table_name, '` MODIFY COLUMN ', p_column_definition);
        PREPARE gf_stmt FROM @gf_sql;
        EXECUTE gf_stmt;
        DEALLOCATE PREPARE gf_stmt;
    END IF;
END//

CREATE PROCEDURE gf_copy_column_if_exists(
    IN p_table_name VARCHAR(64),
    IN p_source_column VARCHAR(64),
    IN p_target_column VARCHAR(64),
    IN p_condition TEXT
)
BEGIN
    DECLARE source_count INT DEFAULT 0;
    DECLARE target_count INT DEFAULT 0;

    SELECT COUNT(*) INTO source_count
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND COLUMN_NAME = p_source_column;

    SELECT COUNT(*) INTO target_count
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND COLUMN_NAME = p_target_column;

    IF source_count > 0 AND target_count > 0 THEN
        SET @gf_sql = CONCAT(
            'UPDATE `', p_table_name, '` SET `', p_target_column, '` = `', p_source_column, '` WHERE ', p_condition
        );
        PREPARE gf_stmt FROM @gf_sql;
        EXECUTE gf_stmt;
        DEALLOCATE PREPARE gf_stmt;
    END IF;
END//

CREATE PROCEDURE gf_add_index_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_index_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND INDEX_NAME = p_index_name
    ) THEN
        SET @gf_sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD ', p_index_definition);
        PREPARE gf_stmt FROM @gf_sql;
        EXECUTE gf_stmt;
        DEALLOCATE PREPARE gf_stmt;
    END IF;
END//

DELIMITER ;

CALL gf_add_column_if_missing('courses', 'capacity', '`capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER `credits`');
CALL gf_add_column_if_missing('courses', 'instructor', '`instructor` VARCHAR(150) NOT NULL DEFAULT ''To be assigned'' AFTER `capacity`');
CALL gf_add_column_if_missing('courses', 'schedule', '`schedule` VARCHAR(120) NOT NULL DEFAULT ''To be announced'' AFTER `instructor`');
CALL gf_add_column_if_missing('courses', 'is_active', '`is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `schedule`');
CALL gf_modify_column_if_exists('courses', 'credits', '`credits` TINYINT UNSIGNED NOT NULL');
CALL gf_modify_column_if_exists('courses', 'capacity', '`capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 30');

CALL gf_add_column_if_missing('students', 'full_name', '`full_name` VARCHAR(150) NULL AFTER `id`');
CALL gf_add_column_if_missing('students', 'email', '`email` VARCHAR(255) NULL AFTER `full_name`');
CALL gf_add_column_if_missing('students', 'password', '`password` VARCHAR(255) NULL AFTER `email`');
CALL gf_add_column_if_missing('students', 'course_id', '`course_id` INT NULL AFTER `password`');
CALL gf_add_column_if_missing('students', 'created_at', '`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL gf_add_column_if_missing('students', 'updated_at', '`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL gf_modify_column_if_exists('students', 'username', '`username` VARCHAR(255) NULL');
CALL gf_modify_column_if_exists('students', 'registration_number', '`registration_number` VARCHAR(100) NULL');
CALL gf_modify_column_if_exists('students', 'phone', '`phone` VARCHAR(20) NULL');
CALL gf_modify_column_if_exists('students', 'address', '`address` TEXT NULL');
CALL gf_modify_column_if_exists('students', 'date_of_birth', '`date_of_birth` DATE NULL');
CALL gf_modify_column_if_exists('students', 'password_hash', '`password_hash` VARCHAR(255) NULL');
CALL gf_modify_column_if_exists('students', 'full_name', '`full_name` VARCHAR(150) NULL');
CALL gf_modify_column_if_exists('students', 'email', '`email` VARCHAR(255) NULL');
CALL gf_modify_column_if_exists('students', 'password', '`password` VARCHAR(255) NULL');

CALL gf_copy_column_if_exists('students', 'name', 'full_name', '`full_name` IS NULL');
CALL gf_copy_column_if_exists('students', 'username', 'full_name', '(`full_name` IS NULL OR `full_name` = '''')');
CALL gf_copy_column_if_exists('students', 'password_hash', 'password', '`password` IS NULL');

CALL gf_add_column_if_missing('admins', 'username', '`username` VARCHAR(100) NULL AFTER `id`');
CALL gf_add_column_if_missing('admins', 'email', '`email` VARCHAR(255) NULL AFTER `username`');
CALL gf_add_column_if_missing('admins', 'password', '`password` VARCHAR(255) NULL AFTER `email`');
CALL gf_add_column_if_missing('admins', 'role', '`role` ENUM(''admin'', ''super_admin'') NOT NULL DEFAULT ''admin''');
CALL gf_add_column_if_missing('admins', 'is_active', '`is_active` TINYINT(1) NOT NULL DEFAULT 1');
CALL gf_add_column_if_missing('admins', 'created_at', '`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL gf_add_column_if_missing('admins', 'updated_at', '`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL gf_modify_column_if_exists('admins', 'username', '`username` VARCHAR(100) NULL');
CALL gf_modify_column_if_exists('admins', 'email', '`email` VARCHAR(255) NULL');
CALL gf_modify_column_if_exists('admins', 'password', '`password` VARCHAR(255) NULL');
CALL gf_modify_column_if_exists('admins', 'password_hash', '`password_hash` VARCHAR(255) NULL');

CALL gf_copy_column_if_exists('admins', 'password_hash', 'password', '`password` IS NULL');

CALL gf_add_column_if_missing('student_courses', 'status', '`status` ENUM(''registered'', ''dropped'', ''completed'') NOT NULL DEFAULT ''registered'' AFTER `course_id`');
CALL gf_add_column_if_missing('student_courses', 'updated_at', '`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL gf_add_index_if_missing('courses', 'idx_courses_active_code', 'INDEX idx_courses_active_code (`is_active`, `code`)');
CALL gf_add_index_if_missing('students', 'idx_students_name', 'INDEX idx_students_name (`full_name`)');
CALL gf_add_index_if_missing('student_courses', 'idx_student_courses_status', 'INDEX idx_student_courses_status (`status`)');

DROP PROCEDURE gf_copy_column_if_exists;
DROP PROCEDURE gf_modify_column_if_exists;
DROP PROCEDURE gf_add_column_if_missing;
DROP PROCEDURE gf_add_index_if_missing;

-- ------------------------------------------------------------
-- Reporting view
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW course_enrollment_summary AS
SELECT
    c.id,
    c.code,
    c.title,
    c.credits,
    c.capacity,
    c.instructor,
    c.schedule,
    COUNT(CASE WHEN sc.status = 'registered' THEN 1 END) AS enrolled,
    GREATEST(c.capacity - COUNT(CASE WHEN sc.status = 'registered' THEN 1 END), 0) AS available,
    CASE
        WHEN COUNT(CASE WHEN sc.status = 'registered' THEN 1 END) >= c.capacity THEN 'full'
        ELSE 'open'
    END AS enrollment_status
FROM courses c
LEFT JOIN student_courses sc ON sc.course_id = c.id
GROUP BY c.id, c.code, c.title, c.credits, c.capacity, c.instructor, c.schedule;

CREATE OR REPLACE VIEW registration_roster AS
SELECT
    sc.id AS registration_id,
    sc.status,
    sc.registered_at,
    s.id AS student_id,
    s.full_name,
    s.email,
    c.id AS course_id,
    c.code AS course_code,
    c.title AS course_title,
    c.credits
FROM student_courses sc
JOIN students s ON s.id = sc.student_id
JOIN courses c ON c.id = sc.course_id;

-- ------------------------------------------------------------
-- Seed data
-- ------------------------------------------------------------
INSERT INTO admins (username, email, password, role, is_active)
VALUES (
    'admin',
    'admin@greenfield.edu',
    '$2y$10$vIZWsBFsxNfSozh51ZdPk.KogmPuZnmheFnluHFpnB/ZJgLHQloxm',
    'super_admin',
    1
)
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    email = VALUES(email),
    role = VALUES(role),
    is_active = VALUES(is_active);

INSERT INTO courses (id, code, title, description, credits, capacity, instructor, schedule, is_active)
VALUES
    (1, 'CS101', 'Introduction to Programming', 'Learn the fundamentals of programming and logic using PHP.', 3, 30, 'Dr. Barini', 'Mon/Wed 9:00-10:30', 1),
    (2, 'WEB202', 'Web Development Fundamentals', 'Build websites with HTML, CSS, JavaScript and server-side PHP.', 4, 25, 'Md. Gracel', 'Tue/Thu 11:00-13:30', 1),
    (3, 'DB303', 'Database Design', 'Design relational databases and interact with MySQL using PHP.', 3, 30, 'Prof. Mwenda', 'Mon/Wed/Fri 14:00-15:00', 1),
    (4, 'SMA2200', 'Calculus I', 'Limits, derivatives, and integrals.', 4, 35, 'Prof. Okello', 'Mon/Wed/Fri 10:00-11:00', 1),
    (5, 'SMA2100', 'System Design and Analysis', 'Learn requirements analysis, system modeling, and design documentation.', 3, 28, 'Prof. Smith', 'Tue/Thu 9:00-10:30', 1),
    (6, 'BUS201', 'Introduction to Abstract Algebra', 'Study binary operations, groups, rings, fields, and proof techniques.', 3, 40, 'Mr. Mwai', 'Wed/Fri 13:00-14:30', 1)
ON DUPLICATE KEY UPDATE
    code = VALUES(code),
    title = VALUES(title),
    description = VALUES(description),
    credits = VALUES(credits),
    capacity = VALUES(capacity),
    instructor = VALUES(instructor),
    schedule = VALUES(schedule),
    is_active = VALUES(is_active);
