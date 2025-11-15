<?php
// Class/Database.php
// This is a sample Database class if you don't already have one

class Database {
    private $host = '127.0.0.1';
    private $db_name = 'appointment';
    private $username = 'root';
    private $password = '';
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Execute a query and return results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Execute a statement (INSERT, UPDATE, DELETE)
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch(PDOException $e) {
            error_log("Execute error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the last inserted ID
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollBack();
    }
    
    /**
     * Get connection
     */
    public function getConnection() {
        return $this->conn;
    }
}