<?php
class Database {

    private $host = "localhost";
    private $port = 4306;
    private $db_name = "greenfield institute";
    private $fallback_db_name = "smartfield institute";
    private $username = "root";
    private $password = "";

    public $conn;

    public function connect() {

        $this->conn = null;

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch(PDOException $e) {
                $fallbackDsn = "mysql:host={$this->host};port={$this->port};dbname={$this->fallback_db_name};charset=utf8mb4";
                $this->conn = new PDO($fallbackDsn, $this->username, $this->password, $options);
            }

        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
        }

        return $this->conn;
    }
}
