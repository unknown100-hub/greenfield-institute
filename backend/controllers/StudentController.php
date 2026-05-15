<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/CourseController.php';

class StudentController {
    private $db;
    private $student;
    private $courseController;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->student = $this->db ? new Student($this->db) : null;
        $this->courseController = new CourseController();
    }

    public function register($data) {
        if (!$this->student) {
            return ['success' => false, 'message' => 'Database connection failed. Start MySQL in XAMPP and check backend/config/db.php.'];
        }

        // Validate input
        if (empty($data['username']) || empty($data['registration_number']) || empty($data['password']) || empty($data['email'])) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        // Check if email already exists
        $this->student->email = $data['email'];
        if ($this->student->emailExists()) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        // Check if registration number already exists
        $this->student->registration_number = $data['registration_number'];
        if ($this->student->registrationNumberExists()) {
            return ['success' => false, 'message' => 'Registration number already exists.'];
        }

        if (strlen($data['password']) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        // Set student properties
        $this->student->username = $data['username'];
        $this->student->registration_number = $data['registration_number'];
        $this->student->password = $data['password'];
        $this->student->email = $data['email'];

        // Create student
        if ($this->student->create()) {
            return [
                'success' => true,
                'message' => 'Student registered successfully.',
                'data' => [
                    'id' => $this->db->lastInsertId(),
                    'username' => $this->student->username,
                    'name' => $this->student->username,
                    'registration_number' => $this->student->registration_number,
                    'email' => $this->student->email
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to register student.'];
        }
    }

    public function login($data) {
        if (!$this->student) {
            return ['success' => false, 'message' => 'Database connection failed. Start MySQL in XAMPP and check backend/config/db.php.'];
        }

        if (empty($data['identifier']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Email or registration number and password are required.'];
        }

        $this->student->email = $data['identifier'];
        $this->student->registration_number = $data['identifier'];

        if ($this->student->login()) {
            if (!password_verify($data['password'], $this->student->password)) {
                return ['success' => false, 'message' => 'Invalid password.'];
            }

            return [
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'id' => $this->student->id,
                    'username' => $this->student->username,
                    'name' => $this->student->name,
                    'registration_number' => $this->student->registration_number,
                    'email' => $this->student->email,
                    'course_id' => $this->student->course_id,
                    'course_code' => $this->student->course_code,
                    'course_title' => $this->student->course_title,
                    'created_at' => $this->student->created_at
                ]
            ];
        }

        return ['success' => false, 'message' => 'No registration found with those details.'];
    }

    public function getAllStudents() {
        if (!$this->student) {
            return [];
        }

        $stmt = $this->student->readAll();
        $students = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $students[] = $row;
        }

        return $students;
    }

    public function getStudent($id) {
        if (!$this->student) {
            return null;
        }

        $this->student->id = $id;
        $this->student->readOne();

        if (!empty($this->student->name)) {
            return [
                'id' => $this->student->id,
                'username' => $this->student->username,
                'name' => $this->student->name,
                'registration_number' => $this->student->registration_number,
                'email' => $this->student->email,
                'course_id' => $this->student->course_id,
                'course_code' => $this->student->course_code,
                'course_title' => $this->student->course_title,
                'created_at' => $this->student->created_at
            ];
        } else {
            return null;
        }
    }

    public function updateStudent($id, $data) {
        if (!$this->student) {
            return ['success' => false, 'message' => 'Database connection failed. Start MySQL in XAMPP and check backend/config/db.php.'];
        }

        $this->student->id = $id;

        // Check if student exists
        $this->student->readOne();
        if (empty($this->student->name)) {
            return ['success' => false, 'message' => 'Student not found.'];
        }

        // Validate input
        if (empty($data['name']) || empty($data['email']) || empty($data['phone']) || empty($data['address']) || empty($data['date_of_birth'])) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        // Check if email already exists (excluding current student)
        $original_email = $this->student->email;
        $this->student->email = $data['email'];
        if ($this->student->emailExists() && $data['email'] !== $original_email) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        // Validate date of birth
        $dob = DateTime::createFromFormat('Y-m-d', $data['date_of_birth']);
        if (!$dob || $dob->format('Y-m-d') !== $data['date_of_birth']) {
            return ['success' => false, 'message' => 'Invalid date of birth format. Use YYYY-MM-DD.'];
        }

        // Check if student is at least 16 years old
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        if ($age < 16) {
            return ['success' => false, 'message' => 'Student must be at least 16 years old.'];
        }

        // Validate course selection if provided
        $courseId = isset($data['course_id']) ? $data['course_id'] : null;
        if ($courseId && !$this->courseController->validateCourseId($courseId)) {
            return ['success' => false, 'message' => 'Selected course is not valid.'];
        }

        // Set student properties
        $this->student->name = $data['name'];
        $this->student->email = $data['email'];
        $this->student->phone = $data['phone'];
        $this->student->address = $data['address'];
        $this->student->date_of_birth = $data['date_of_birth'];
        $this->student->course_id = $courseId;

        // Update student
        if ($this->student->update()) {
            return ['success' => true, 'message' => 'Student updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update student.'];
        }
    }

    public function deleteStudent($id) {
        if (!$this->student) {
            return ['success' => false, 'message' => 'Database connection failed. Start MySQL in XAMPP and check backend/config/db.php.'];
        }

        $this->student->id = $id;

        // Check if student exists
        $this->student->readOne();
        if (empty($this->student->name)) {
            return ['success' => false, 'message' => 'Student not found.'];
        }

        // Delete student
        if ($this->student->delete()) {
            return ['success' => true, 'message' => 'Student deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete student.'];
        }
    }

    public function registerCourse($data) {
        if (!$this->student) {
            return ['success' => false, 'message' => 'Database connection failed. Start MySQL in XAMPP and check backend/config/db.php.'];
        }

        if (empty($data['student_id']) || empty($data['course_id'])) {
            return ['success' => false, 'message' => 'Student and course are required.'];
        }

        if (!$this->courseController->validateCourseId($data['course_id'])) {
            return ['success' => false, 'message' => 'Selected course is not valid.'];
        }

        $this->student->id = (int)$data['student_id'];
        $this->student->readOne();

        if (empty($this->student->name)) {
            return ['success' => false, 'message' => 'Student not found. Please register again.'];
        }

        $this->student->course_id = (int)$data['course_id'];

        if ($this->student->registerCourse()) {
            $student = $this->getStudent($this->student->id);
            return [
                'success' => true,
                'message' => 'Course registered successfully.',
                'data' => $student
            ];
        }

        return ['success' => false, 'message' => 'Failed to register course.'];
    }
}
?>
