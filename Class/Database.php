<?php
class Database {
    protected $conn;
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $db = 'appointment';

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4", 
                $this->user, 
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            die(json_encode([
                "success" => false,
                "message" => "Database connection failed. Please try again later."
            ]));
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>