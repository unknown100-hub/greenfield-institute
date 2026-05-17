<?php
require_once __DIR__ . '/../config/db.php';

class Course {
    private $conn;
    private $table_name = "courses";

    public $id;
    public $code;
    public $title;
    public $description;
    public $credits;
    public $capacity;
    public $instructor;
    public $schedule;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        $this->ensureCourseColumns();

        $query = "SELECT
                    c.id,
                    c.code,
                    c.title,
                    c.description,
                    c.description AS `desc`,
                    c.credits,
                    c.capacity,
                    c.instructor,
                    c.schedule,
                    COUNT(sc.id) AS enrolled,
                    GREATEST(c.capacity - COUNT(sc.id), 0) AS available
                  FROM " . $this->table_name . " c
                  LEFT JOIN student_courses sc ON sc.course_id = c.id
                  GROUP BY c.id, c.code, c.title, c.description, c.credits, c.capacity, c.instructor, c.schedule
                  ORDER BY c.code";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $this->ensureCourseColumns();

        $query = "SELECT
                    c.id,
                    c.code,
                    c.title,
                    c.description,
                    c.credits,
                    c.capacity,
                    c.instructor,
                    c.schedule,
                    COUNT(sc.id) AS enrolled
                  FROM " . $this->table_name . " c
                  LEFT JOIN student_courses sc ON sc.course_id = c.id
                  WHERE c.id = ?
                  GROUP BY c.id, c.code, c.title, c.description, c.credits, c.capacity, c.instructor, c.schedule
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->code = $row['code'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->credits = $row['credits'];
            $this->capacity = $row['capacity'];
            $this->instructor = $row['instructor'];
            $this->schedule = $row['schedule'];
        }
    }

    public function exists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function importFromXml($xmlPath) {
        if (!file_exists($xmlPath)) {
            return [];
        }

        $xml = simplexml_load_file($xmlPath);
        $courses = [];

        foreach ($xml->course as $courseNode) {
            $courses[] = [
                'id' => (int)$courseNode->id,
                'code' => (string)$courseNode->code,
                'title' => (string)$courseNode->title,
                'description' => (string)$courseNode->description,
                'credits' => (int)$courseNode->credits,
                'capacity' => isset($courseNode->capacity) ? (int)$courseNode->capacity : 30,
                'instructor' => isset($courseNode->instructor) ? (string)$courseNode->instructor : 'To be assigned',
                'schedule' => isset($courseNode->schedule) ? (string)$courseNode->schedule : 'To be announced',
            ];
        }

        return $courses;
    }

    public function syncXmlToDb($xmlPath) {
        $this->ensureCourseColumns();

        $courses = $this->importFromXml($xmlPath);
        foreach ($courses as $courseData) {
            $this->id = $courseData['id'];
            if (!$this->exists()) {
                $query = "INSERT INTO " . $this->table_name . " (id, code, title, description, credits, capacity, instructor, schedule)
                          VALUES (:id, :code, :title, :description, :credits, :capacity, :instructor, :schedule)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $courseData['id'], PDO::PARAM_INT);
                $stmt->bindParam(':code', $courseData['code']);
                $stmt->bindParam(':title', $courseData['title']);
                $stmt->bindParam(':description', $courseData['description']);
                $stmt->bindParam(':credits', $courseData['credits'], PDO::PARAM_INT);
                $stmt->bindParam(':capacity', $courseData['capacity'], PDO::PARAM_INT);
                $stmt->bindParam(':instructor', $courseData['instructor']);
                $stmt->bindParam(':schedule', $courseData['schedule']);
                $stmt->execute();
            }
        }

        $stmt = $this->readAll();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ensureCourseColumns() {
        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
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

        $requiredColumns = [
            'capacity' => "ALTER TABLE " . $this->table_name . " ADD COLUMN capacity SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER credits",
            'instructor' => "ALTER TABLE " . $this->table_name . " ADD COLUMN instructor VARCHAR(150) NOT NULL DEFAULT 'To be assigned' AFTER capacity",
            'schedule' => "ALTER TABLE " . $this->table_name . " ADD COLUMN schedule VARCHAR(120) NOT NULL DEFAULT 'To be announced' AFTER instructor",
            'is_active' => "ALTER TABLE " . $this->table_name . " ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER schedule",
            'created_at' => "ALTER TABLE " . $this->table_name . " ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ALTER TABLE " . $this->table_name . " ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        ];

        $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name);
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');

        foreach ($requiredColumns as $column => $sql) {
            if (!in_array($column, $existingColumns, true)) {
                $this->conn->exec($sql);
            }
        }

        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS student_courses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                course_id INT NOT NULL,
                status ENUM('registered', 'dropped', 'completed') NOT NULL DEFAULT 'registered',
                registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_student_courses_student_course (student_id, course_id),
                KEY idx_student_courses_course_id (course_id)
            )"
        );
    }
}
?>
