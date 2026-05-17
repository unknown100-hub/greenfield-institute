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
                username VARCHAR(100) NULL UNIQUE,
                password VARCHAR(255) NULL,
                email VARCHAR(255) NULL UNIQUE,
                role ENUM('admin', 'super_admin') NOT NULL DEFAULT 'admin',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $this->ensureAccountColumns();

        $username = 'admin';
        $email = 'admin@greenfield.edu';
        $password = password_hash('admin123', PASSWORD_DEFAULT);

        $query = "INSERT INTO " . $this->table_name . " (username, password, email, role)
                  VALUES (:username, :password, :email, 'super_admin')
                  ON DUPLICATE KEY UPDATE password=:password_update, email=:email_update, role='super_admin'";
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

    private function ensureAccountColumns() {
        $requiredColumns = [
            'username' => "ALTER TABLE " . $this->table_name . " ADD COLUMN username VARCHAR(100) NULL AFTER id",
            'email' => "ALTER TABLE " . $this->table_name . " ADD COLUMN email VARCHAR(255) NULL AFTER username",
            'password' => "ALTER TABLE " . $this->table_name . " ADD COLUMN password VARCHAR(255) NULL AFTER email",
            'role' => "ALTER TABLE " . $this->table_name . " ADD COLUMN role ENUM('admin', 'super_admin') NOT NULL DEFAULT 'admin'",
            'is_active' => "ALTER TABLE " . $this->table_name . " ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1",
            'created_at' => "ALTER TABLE " . $this->table_name . " ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ];

        $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name);
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');

        foreach ($requiredColumns as $column => $sql) {
            if (!in_array($column, $existingColumns, true)) {
                $this->conn->exec($sql);
            }
        }

        $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name);
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');

        foreach ([
            'username' => "ALTER TABLE " . $this->table_name . " MODIFY username VARCHAR(100) NULL",
            'email' => "ALTER TABLE " . $this->table_name . " MODIFY email VARCHAR(255) NULL",
            'password' => "ALTER TABLE " . $this->table_name . " MODIFY password VARCHAR(255) NULL",
            'password_hash' => "ALTER TABLE " . $this->table_name . " MODIFY password_hash VARCHAR(255) NULL",
        ] as $legacyColumn => $sql) {
            if (!in_array($legacyColumn, $existingColumns, true)) {
                continue;
            }

            try {
                $this->conn->exec($sql);
            } catch (PDOException $e) {
                error_log("Admin table compatibility update skipped: " . $e->getMessage());
            }
        }

        if (in_array('password_hash', $existingColumns, true) && in_array('password', $existingColumns, true)) {
            try {
                $this->conn->exec("UPDATE " . $this->table_name . " SET password = password_hash WHERE password IS NULL AND password_hash IS NOT NULL");
            } catch (PDOException $e) {
                error_log("Legacy admin password migration skipped: " . $e->getMessage());
            }
        }
    }
}
?>
