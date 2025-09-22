<?php
Class Database{
    protected $conn;
    private $host='localhost';
    private $user='root';
    private $pass='';
    private $db='appointment';

    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db}", $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            die(json_encode(["Connection error: " . $exception->getMessage()]));
        }
    }
}
?>