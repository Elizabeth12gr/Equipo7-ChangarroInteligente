<?php
class Conexion {
    private $host = '127.0.0.1';
    private $port = '3319';
    private $db_name = 'changarro_inteligente';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {   
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new Exception("Error de conexión: " . $e->getMessage());
        }
        return $this->conn;
    }
}
?>
