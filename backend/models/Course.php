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

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT id, code, title, description, credits FROM " . $this->table_name . " ORDER BY code";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id, code, title, description, credits FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->code = $row['code'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->credits = $row['credits'];
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
            ];
        }

        return $courses;
    }

    public function syncXmlToDb($xmlPath) {
        $courses = $this->importFromXml($xmlPath);
        foreach ($courses as $courseData) {
            $this->id = $courseData['id'];
            if (!$this->exists()) {
                $query = "INSERT INTO " . $this->table_name . " (id, code, title, description, credits) VALUES (:id, :code, :title, :description, :credits)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $courseData['id'], PDO::PARAM_INT);
                $stmt->bindParam(':code', $courseData['code']);
                $stmt->bindParam(':title', $courseData['title']);
                $stmt->bindParam(':description', $courseData['description']);
                $stmt->bindParam(':credits', $courseData['credits'], PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        return $courses;
    }
}
?>
