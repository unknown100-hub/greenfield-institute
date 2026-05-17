<?php
require_once __DIR__ . '/../config/db.php';

class Student {
    private $conn;
    private $table_name = "students";

    public $id;
    public $name;
    public $email;
    public $password;
    public $course_id;
    public $course_code;
    public $course_title;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $this->ensureAccountColumns();

        $query = "INSERT INTO " . $this->table_name . " SET full_name=:full_name, email=:email, password=:password, course_id=NULL";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(":full_name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function emailExists() {
        $this->ensureAccountColumns();

        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();

        return $num > 0;
    }

    public function login() {
        $this->ensureAccountColumns();

        $query = "SELECT s.id, s.full_name AS name, s.email, s.password, s.course_id, c.code AS course_code, c.title AS course_title, s.created_at FROM " . $this->table_name . " s LEFT JOIN courses c ON s.course_id = c.id WHERE s.email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->course_id = $row['course_id'];
            $this->course_code = $row['course_code'];
            $this->course_title = $row['course_title'];
            $this->created_at = $row['created_at'];
            return true;
        }

        return false;
    }

    public function readAll() {
        $this->ensureAccountColumns();

        $query = "SELECT s.id, s.full_name AS name, s.full_name AS username, s.email, s.course_id, c.code AS course_code, c.title AS course_title, s.created_at FROM " . $this->table_name . " s LEFT JOIN courses c ON s.course_id = c.id ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $this->ensureAccountColumns();

        $query = "SELECT s.id, s.full_name AS name, s.email, s.course_id, c.code AS course_code, c.title AS course_title, s.created_at FROM " . $this->table_name . " s LEFT JOIN courses c ON s.course_id = c.id WHERE s.id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->course_id = $row['course_id'];
            $this->course_code = $row['course_code'];
            $this->course_title = $row['course_title'];
            $this->created_at = $row['created_at'];
        }
    }

    public function registerCourse() {
        $this->ensureStudentCoursesTable();
        $this->conn->beginTransaction();

        try {
            $query = "UPDATE " . $this->table_name . " SET course_id=:course_id WHERE id=:id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":course_id", $this->course_id, PDO::PARAM_INT);
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $registrationQuery = "INSERT INTO student_courses (student_id, course_id, status)
                                  VALUES (:student_id, :course_id, 'registered')
                                  ON DUPLICATE KEY UPDATE status = 'registered', updated_at = CURRENT_TIMESTAMP";
            $registrationStmt = $this->conn->prepare($registrationQuery);
            $registrationStmt->bindParam(":student_id", $this->id, PDO::PARAM_INT);
            $registrationStmt->bindParam(":course_id", $this->course_id, PDO::PARAM_INT);
            $registrationStmt->execute();

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Course registration failed: " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET full_name=:full_name, email=:email, course_id=:course_id WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Handle course_id properly - allow null or valid integer
        $courseId = $this->course_id;
        if ($courseId === null || $courseId === '' || $courseId === 'null') {
            $courseId = null;
        } else {
            $courseId = (int) $courseId;
        }

        // Bind values
        $stmt->bindParam(":full_name", $this->name);
        $stmt->bindParam(":email", $this->email);

        if ($courseId === null) {
            $stmt->bindValue(":course_id", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":course_id", $courseId, PDO::PARAM_INT);
        }

        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id, PDO::PARAM_INT);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    private function ensureAccountColumns() {
        $this->ensureCoursesTable();
        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(150) NULL,
                email VARCHAR(255) NULL UNIQUE,
                password VARCHAR(255) NULL,
                course_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
            )"
        );

        $requiredColumns = [
            'full_name' => "ALTER TABLE " . $this->table_name . " ADD COLUMN full_name VARCHAR(150) NULL AFTER id",
            'email' => "ALTER TABLE " . $this->table_name . " ADD COLUMN email VARCHAR(255) NULL AFTER full_name",
            'password' => "ALTER TABLE " . $this->table_name . " ADD COLUMN password VARCHAR(255) NULL AFTER email",
            'course_id' => "ALTER TABLE " . $this->table_name . " ADD COLUMN course_id INT NULL AFTER password",
            'created_at' => "ALTER TABLE " . $this->table_name . " ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ];

        $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name);
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');

        foreach ($requiredColumns as $column => $sql) {
            if (!in_array($column, $existingColumns, true)) {
                $this->conn->exec($sql);
            }
        }

        foreach ([
            "ALTER TABLE " . $this->table_name . " MODIFY course_id INT DEFAULT NULL",
        ] as $sql) {
            try {
                $this->conn->exec($sql);
            } catch (PDOException $e) {
                error_log("Student table compatibility update skipped: " . $e->getMessage());
            }
        }

        $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name);
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');

        foreach ([
            'username' => "ALTER TABLE " . $this->table_name . " MODIFY username VARCHAR(255) NULL",
            'registration_number' => "ALTER TABLE " . $this->table_name . " MODIFY registration_number VARCHAR(100) NULL",
            'phone' => "ALTER TABLE " . $this->table_name . " MODIFY phone VARCHAR(20) NULL",
            'address' => "ALTER TABLE " . $this->table_name . " MODIFY address TEXT NULL",
            'date_of_birth' => "ALTER TABLE " . $this->table_name . " MODIFY date_of_birth DATE NULL",
            'password_hash' => "ALTER TABLE " . $this->table_name . " MODIFY password_hash VARCHAR(255) NULL",
        ] as $legacyColumn => $sql) {
            if (!in_array($legacyColumn, $existingColumns, true)) {
                continue;
            }

            try {
                $this->conn->exec($sql);
            } catch (PDOException $e) {
                error_log("Legacy student column compatibility update skipped: " . $e->getMessage());
            }
        }

        if (in_array('name', $existingColumns, true) && in_array('full_name', $existingColumns, true)) {
            try {
                $this->conn->exec("UPDATE " . $this->table_name . " SET full_name = name WHERE full_name IS NULL AND name IS NOT NULL");
            } catch (PDOException $e) {
                error_log("Legacy student name migration skipped: " . $e->getMessage());
            }
        }

        if (in_array('username', $existingColumns, true) && in_array('full_name', $existingColumns, true)) {
            try {
                $this->conn->exec("UPDATE " . $this->table_name . " SET full_name = username WHERE (full_name IS NULL OR full_name = '') AND username IS NOT NULL");
            } catch (PDOException $e) {
                error_log("Legacy student username migration skipped: " . $e->getMessage());
            }
        }

        if (in_array('password_hash', $existingColumns, true) && in_array('password', $existingColumns, true)) {
            try {
                $this->conn->exec("UPDATE " . $this->table_name . " SET password = password_hash WHERE password IS NULL AND password_hash IS NOT NULL");
            } catch (PDOException $e) {
                error_log("Legacy student password migration skipped: " . $e->getMessage());
            }
        }
    }

    private function ensureStudentCoursesTable() {
        $this->ensureAccountColumns();
        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS student_courses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                course_id INT NOT NULL,
                status ENUM('registered', 'dropped', 'completed') NOT NULL DEFAULT 'registered',
                registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_student_course (student_id, course_id),
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
            )"
        );

        $stmt = $this->conn->query("SHOW COLUMNS FROM student_courses");
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');

        foreach ([
            'status' => "ALTER TABLE student_courses ADD COLUMN status ENUM('registered', 'dropped', 'completed') NOT NULL DEFAULT 'registered' AFTER course_id",
            'updated_at' => "ALTER TABLE student_courses ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        ] as $column => $sql) {
            if (!in_array($column, $existingColumns, true)) {
                $this->conn->exec($sql);
            }
        }
    }

    private function ensureCoursesTable() {
        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                credits TINYINT UNSIGNED NOT NULL,
                capacity SMALLINT UNSIGNED NOT NULL DEFAULT 30,
                instructor VARCHAR(150) NOT NULL DEFAULT 'To be assigned',
                schedule VARCHAR(120) NOT NULL DEFAULT 'To be announced',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        );

        $stmt = $this->conn->query("SHOW COLUMNS FROM courses");
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');

        foreach ([
            'capacity' => "ALTER TABLE courses ADD COLUMN capacity SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER credits",
            'instructor' => "ALTER TABLE courses ADD COLUMN instructor VARCHAR(150) NOT NULL DEFAULT 'To be assigned' AFTER capacity",
            'schedule' => "ALTER TABLE courses ADD COLUMN schedule VARCHAR(120) NOT NULL DEFAULT 'To be announced' AFTER instructor",
            'is_active' => "ALTER TABLE courses ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER schedule",
        ] as $column => $sql) {
            if (!in_array($column, $existingColumns, true)) {
                $this->conn->exec($sql);
            }
        }

        $this->conn->exec(
            "INSERT INTO courses (id, code, title, description, credits, capacity, instructor, schedule) VALUES
                (1, 'CS101', 'Introduction to Programming', 'Learn the fundamentals of programming and logic using PHP.', 3, 30, 'Dr. Barini', 'Mon/Wed 9:00-10:30'),
                (2, 'WEB202', 'Web Development Fundamentals', 'Build websites with HTML, CSS, JavaScript and server-side PHP.', 4, 25, 'Md. Gracel', 'Tue/Thu 11:00-13:30'),
                (3, 'DB303', 'Database Design', 'Design relational databases and interact with MySQL using PHP.', 3, 30, 'Prof. Mwenda', 'Mon/Wed/Fri 14:00-15:00')
            ON DUPLICATE KEY UPDATE
                code = VALUES(code),
                title = VALUES(title),
                description = VALUES(description),
                credits = VALUES(credits),
                capacity = VALUES(capacity),
                instructor = VALUES(instructor),
                schedule = VALUES(schedule)"
        );
    }
}
?>
