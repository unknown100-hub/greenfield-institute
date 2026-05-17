<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Course.php';

class CourseController {
    private $db;
    private $course;
    private $xmlPath;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->course = $this->db ? new Course($this->db) : null;
        $this->xmlPath = __DIR__ . '/../data/course_catalog.xml';
    }

    public function getAllCourses() {
        $courses = [];

        if ($this->db) {
            $courses = $this->course->syncXmlToDb($this->xmlPath);
        }

        if (empty($courses)) {
            $courses = $this->importFromXml();

            if (!empty($this->db)) {
                $stmt = $this->course->readAll();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $courses[] = $row;
                }
            }
        }

        return $courses;
    }

    public function getCourse($id) {
        if (!$this->course) {
            foreach ($this->importFromXml() as $course) {
                if ((int)$course['id'] === (int)$id) {
                    return $course;
                }
            }

            return null;
        }

        $this->course->id = $id;
        $this->course->readOne();
        if (!empty($this->course->title)) {
            return [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'title' => $this->course->title,
                'description' => $this->course->description,
                'desc' => $this->course->description,
                'credits' => $this->course->credits,
                'capacity' => $this->course->capacity,
                'instructor' => $this->course->instructor,
                'schedule' => $this->course->schedule,
            ];
        }
        return null;
    }

    public function getCoursesXml() {
        header('Content-Type: application/xml');
        if (file_exists($this->xmlPath)) {
            readfile($this->xmlPath);
            return true;
        }
        return false;
    }

    public function validateCourseId($id) {
        if (empty($id) || !is_numeric($id)) {
            return false;
        }
        if (!$this->course) {
            foreach ($this->importFromXml() as $course) {
                if ((int)$course['id'] === (int)$id) {
                    return true;
                }
            }

            return false;
        }

        $this->course->id = (int)$id;
        return $this->course->exists();
    }

    private function importFromXml() {
        if (!file_exists($this->xmlPath)) {
            return [];
        }

        $xml = simplexml_load_file($this->xmlPath);
        $courses = [];

        foreach ($xml->course as $courseNode) {
            $courses[] = [
                'id' => (int)$courseNode->id,
                'code' => (string)$courseNode->code,
                'title' => (string)$courseNode->title,
                'description' => (string)$courseNode->description,
                'desc' => (string)$courseNode->description,
                'credits' => (int)$courseNode->credits,
                'capacity' => isset($courseNode->capacity) ? (int)$courseNode->capacity : 30,
                'instructor' => isset($courseNode->instructor) ? (string)$courseNode->instructor : 'To be assigned',
                'schedule' => isset($courseNode->schedule) ? (string)$courseNode->schedule : 'To be announced',
            ];
        }

        return $courses;
    }
}
?>
