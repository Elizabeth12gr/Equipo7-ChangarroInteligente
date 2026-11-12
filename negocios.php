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

    // Leer (Consultar los datos del negocio)
    public function obtenerNegocio($id_negocios) {
        $query = "SELECT * FROM negocios WHERE id_negocios = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id_negocios);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Leer (Consultar todos los negocios de un dueño específico)
    public function obtenerNegociosPorDueno($dueno_id) {
        $query = "SELECT * FROM negocios WHERE dueno_id = :dueno_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":dueno_id", $dueno_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizar (Editar horarios, mensajes o teléfonos del negocio)
    public function actualizarNegocio($id_negocios, $nombre, $tipo, $telefono_contacto, $ubicacion, $mensaje_bienvenida, $horario) {
        try {
            $query = "UPDATE negocios SET nombre = :nombre, tipo = :tipo, telefono_contacto = :tel, 
                      ubicacion = :ubi, mensaje_bienvenida = :msj, horario = :horario 
                      WHERE id_negocios = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":tipo", $tipo);
            $stmt->bindParam(":tel", $telefono_contacto);
            $stmt->bindParam(":ubi", $ubicacion);
            $stmt->bindParam(":msj", $mensaje_bienvenida);
            $stmt->bindParam(":horario", $horario);
            $stmt->bindParam(":id", $id_negocios);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    // Eliminar (Borrar un negocio)
    public function eliminarNegocio($id_negocios) {
        try {
            $query = "DELETE FROM negocios WHERE id_negocios = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id_negocios);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
