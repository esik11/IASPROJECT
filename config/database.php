<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'campus_event_management';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        if ($this->conn === null) {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Test the connection
            $test = $this->conn->query("SELECT 1");
            if (!$test) {
                throw new PDOException("Database connection test failed");
            }
            
                error_log("New database connection established.");
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            throw new PDOException("Database connection failed: " . $e->getMessage());
            }
        }

        return $this->conn;
    }
}