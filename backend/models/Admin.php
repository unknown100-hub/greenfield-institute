<?php
require_once __DIR__ . '/../config/db.php';

class Admin {
    private $conn;
    private $table_name = "admins";

    public $id;
    public $username;
    public $password;
    public $email;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function ensureDefaultAdmin() {
        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $username = 'admin';
        $email = 'admin@greenfield.edu';
        $password = password_hash('admin123', PASSWORD_DEFAULT);

        $query = "INSERT INTO " . $this->table_name . " (username, password, email)
                  VALUES (:username, :password, :email)
                  ON DUPLICATE KEY UPDATE password=:password_update, email=:email_update";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_update', $password);
        $stmt->bindParam(':email_update', $email);
        return $stmt->execute();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET username=:username, password=:password, email=:email";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Hash password
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":email", $this->email);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function login() {
        $this->ensureDefaultAdmin();

        $query = "SELECT id, username, password, email FROM " . $this->table_name . " WHERE username = ? OR email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->bindParam(2, $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->password = $row['password'];
            $this->email = $row['email'];

            return true;
        }

        return false;
    }

    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num > 0) {
            return true;
        }

        return false;
    }
}
?>
