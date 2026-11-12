<?php
// conexion.php
date_default_timezone_set('America/Mexico_City');

class Conexion {
    private $host     = '127.0.0.1';
    private $db_name  = 'changarro_inteligente';
    private $username = 'root';
    private $password = '';
    private static $pdoCompartido = null;
    public $conn;

    public function getConnection() {
        if (self::$pdoCompartido !== null) {
            $this->conn = self::$pdoCompartido;
            return $this->conn;
        }

        try {
            self::$pdoCompartido = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false
                ]
            );
            $this->conn = self::$pdoCompartido;
        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
