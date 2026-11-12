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

    // Leer (Consultar un dueño por su ID)
    public function obtenerDueno($id_duenos) {
        $query = "SELECT * FROM duenos WHERE id_duenos = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id_duenos);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar (Editar los datos de un dueño por id)
    public function actualizarDueno($id_duenos, $nombre, $telefono, $correo) {
        try {
            $query = "UPDATE duenos SET nombre = :nombre, telefono = :telefono, correo = :correo WHERE id_duenos = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":telefono", $telefono);
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":id", $id_duenos);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    // Eliminar (Borrar un dueño)
    public function eliminarDueno($id_duenos) {
        try {
            $query = "DELETE FROM duenos WHERE id_duenos = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id_duenos);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false; // Puede fallar si tiene negocios asociados (por la llave foránea)
        }
    }
    
}
?>
