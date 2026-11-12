<?php
require_once 'conexion.php';

class Negocio {
    private $conn;

    public function __construct() {
        $database = new Conexion();
        $this->conn = $database->getConnection();
    }

    // Crea un nuevo negocio y lo asigna a un dueño
    public function registrarNegocio($dueno_id, $nombre, $tipo, $telefono_contacto, $ubicacion) {
        try {
            $query = "INSERT INTO negocios (dueno_id, nombre, tipo, telefono_contacto, ubicacion) 
                      VALUES (:dueno_id, :nombre, :tipo, :telefono_contacto, :ubicacion)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":dueno_id", $dueno_id);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":tipo", $tipo);
            $stmt->bindParam(":telefono_contacto", $telefono_contacto);
            $stmt->bindParam(":ubicacion", $ubicacion);

            if($stmt->execute()){
                return $this->conn->lastInsertId();
            }
        } catch(PDOException $e) {
            echo "Error al registrar negocio: " . $e->getMessage();
        }
        return false;
    }
}
?>