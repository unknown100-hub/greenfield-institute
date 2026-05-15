<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Admin.php';

class AdminController {
    private $db;
    private $admin;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->admin = $this->db ? new Admin($this->db) : null;
        if ($this->admin) {
            $this->admin->ensureDefaultAdmin();
        }
    }

    public function register($data) {
        if (!$this->admin) {
            return ['success' => false, 'message' => 'Database connection failed. Start MySQL in XAMPP and check backend/config/db.php.'];
        }

        // Validate input
        if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        // Check if username already exists
        $this->admin->username = $data['username'];
        if ($this->admin->usernameExists()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        // Validate password strength
        if (strlen($data['password']) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        // Set admin properties
        $this->admin->username = $data['username'];
        $this->admin->password = $data['password'];
        $this->admin->email = $data['email'];

        // Create admin
        if ($this->admin->create()) {
            return ['success' => true, 'message' => 'Admin registered successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to register admin.'];
        }
    }

    public function login($data) {
        if (!$this->admin) {
            return ['success' => false, 'message' => 'Database connection failed. Start MySQL in XAMPP and check backend/config/db.php.'];
        }

        // Validate input
        if (empty($data['identifier']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Email or username and password are required.'];
        }

        // Set admin properties
        $this->admin->username = $data['identifier'];
        $this->admin->email = $data['identifier'];

        // Check if admin exists and get password
        if ($this->admin->login()) {
            // Verify password
            if (password_verify($data['password'], $this->admin->password)) {
                // Start session
                $this->startSession();
                $_SESSION['admin_id'] = $this->admin->id;
                $_SESSION['admin_username'] = $this->admin->username;

                return ['success' => true, 'message' => 'Login successful.'];
            } else {
                return ['success' => false, 'message' => 'Invalid password.'];
            }
        } else {
            return ['success' => false, 'message' => 'Admin not found.'];
        }
    }

    public function logout() {
        $this->startSession();
        session_destroy();
        return ['success' => true, 'message' => 'Logout successful.'];
    }

    public function isLoggedIn() {
        $this->startSession();
        return isset($_SESSION['admin_id']);
    }

    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionPath = __DIR__ . '/../tmp/sessions';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0777, true);
            }
            session_save_path($sessionPath);
            session_start();
        }
    }
}
?>
