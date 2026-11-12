<?php
require_once 'conexion.php';

class Dueno {
    private $conn;

    public function __construct() {
        $database = new Conexion();
        $this->conn = $database->getConnection();
    }

    // Registra un nuevo dueño en la plataforma
    public function registrarDueno($nombre, $telefono, $correo) {
        try {
            $query = "INSERT INTO duenos (nombre, telefono, correo) 
                      VALUES (:nombre, :telefono, :correo)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":telefono", $telefono);
            $stmt->bindParam(":correo", $correo);

            if($stmt->execute()){
                return $this->conn->lastInsertId();
            }
        } catch(PDOException $e) {
            echo "Error al registrar dueño: " . $e->getMessage();
        }
        return false;
    }
}
?>