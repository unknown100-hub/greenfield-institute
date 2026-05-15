<?php
require_once __DIR__ . '/../config/db.php';

class Student {
    private $conn;
    private $table_name = "students";

    public $id;
    public $username;
    public $registration_number;
    public $name;
    public $email;
    public $password;
    public $phone;
    public $address;
    public $date_of_birth;
    public $course_id;
    public $course_code;
    public $course_title;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $this->ensureAccountColumns();

        $query = "INSERT INTO " . $this->table_name . " SET username=:username, name=:name, registration_number=:registration_number, password=:password, email=:email, phone='', address='', date_of_birth='2000-01-01', course_id=NULL";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->registration_number = htmlspecialchars(strip_tags($this->registration_number));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":name", $this->username);
        $stmt->bindParam(":registration_number", $this->registration_number);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":email", $this->email);

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

    public function registrationNumberExists() {
        $this->ensureAccountColumns();

        $query = "SELECT id FROM " . $this->table_name . " WHERE registration_number = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->registration_number);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function login() {
        $this->ensureAccountColumns();

        $query = "SELECT s.id, s.username, s.registration_number, s.email, s.password, s.course_id, c.code AS course_code, c.title AS course_title, s.created_at FROM " . $this->table_name . " s LEFT JOIN courses c ON s.course_id = c.id WHERE s.email = ? OR s.registration_number = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->bindParam(2, $this->registration_number);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->name = $row['username'];
            $this->registration_number = $row['registration_number'];
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

        $query = "SELECT s.id, s.username, s.username AS name, s.registration_number, s.email, s.course_id, c.code AS course_code, c.title AS course_title, s.created_at FROM " . $this->table_name . " s LEFT JOIN courses c ON s.course_id = c.id ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $this->ensureAccountColumns();

        $query = "SELECT s.id, s.username, s.registration_number, s.email, s.course_id, c.code AS course_code, c.title AS course_title, s.created_at FROM " . $this->table_name . " s LEFT JOIN courses c ON s.course_id = c.id WHERE s.id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->username = $row['username'];
            $this->name = $row['username'];
            $this->registration_number = $row['registration_number'];
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

            $registrationQuery = "INSERT INTO student_courses (student_id, course_id) VALUES (:student_id, :course_id) ON DUPLICATE KEY UPDATE registered_at = CURRENT_TIMESTAMP";
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
        $query = "UPDATE " . $this->table_name . " SET name=:name, email=:email, phone=:phone, address=:address, date_of_birth=:date_of_birth, course_id=:course_id WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->date_of_birth = htmlspecialchars(strip_tags($this->date_of_birth));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Handle course_id properly - allow null or valid integer
        $courseId = $this->course_id;
        if ($courseId === null || $courseId === '' || $courseId === 'null') {
            $courseId = null;
        } else {
            $courseId = (int) $courseId;
        }

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);

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
        $requiredColumns = [
            'username' => "ALTER TABLE " . $this->table_name . " ADD COLUMN username VARCHAR(255) NULL AFTER id",
            'name' => "ALTER TABLE " . $this->table_name . " ADD COLUMN name VARCHAR(255) NULL AFTER username",
            'registration_number' => "ALTER TABLE " . $this->table_name . " ADD COLUMN registration_number VARCHAR(100) NULL UNIQUE AFTER name",
            'password' => "ALTER TABLE " . $this->table_name . " ADD COLUMN password VARCHAR(255) NULL AFTER registration_number",
        ];

        $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name);
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');

        foreach ($requiredColumns as $column => $sql) {
            if (!in_array($column, $existingColumns, true)) {
                $this->conn->exec($sql);
            }
        }

        foreach ([
            "ALTER TABLE " . $this->table_name . " MODIFY phone VARCHAR(20) NULL",
            "ALTER TABLE " . $this->table_name . " MODIFY address TEXT NULL",
            "ALTER TABLE " . $this->table_name . " MODIFY date_of_birth DATE NULL",
            "ALTER TABLE " . $this->table_name . " MODIFY course_id INT DEFAULT NULL",
        ] as $sql) {
            try {
                $this->conn->exec($sql);
            } catch (PDOException $e) {
                error_log("Student table compatibility update skipped: " . $e->getMessage());
            }
        }
    }

    private function ensureStudentCoursesTable() {
        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS student_courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                course_id INT NOT NULL,
                registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_student_course (student_id, course_id),
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
            )"
        );
    }
}
?>
