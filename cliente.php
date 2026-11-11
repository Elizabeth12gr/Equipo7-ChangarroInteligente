<?php
require_once 'conexion.php';

class Cliente {
    private $conn;
    private $table_name = "clientes";

    public function __construct() {
        $database = new Conexion();
        $this->conn = $database->getConnection();
    }

    // Busca si un cliente ya existe por su numero de WhatsApp
    public function buscarCliente($telefono_whatsapp) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE telefono_whatsapp = :telefono_whatsapp LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':telefono_whatsapp', $telefono_whatsapp);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC); 
    }

    // Registra un nuevo cliente con su numero, nombre del perfil de wsp y el nombre que el cliente proporcione
    public function registrarCliente($telefono_whatsapp, $nombre_perfil, $nombre_cliente) {
        try {
        $query = "INSERT INTO " . $this->table_name . " SET telefono_whatsapp=:telefono_whatsapp, nombre_perfil=:nombre_perfil, nombre_cliente=:nombre_cliente";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":telefono_whatsapp", $telefono_whatsapp);
        $stmt->bindParam(":nombre_perfil", $nombre_perfil);
        $stmt->bindParam(":nombre_cliente, $nombre_cliente")

        if($stmt->execute()){
            return $this->conn->lastInsertId(); 
        }
    } catch(PDOException $e) {
        // El código SQLSTATE 23000 indica una violación de integridad (como un duplicado por ejemplo)
        if ($e->getCode() == 23000) {
            return false; // El cliente ya existe, (osea que fallamos de forma controlada)
        }
        // Si es otro error de PDO, lo imprimimos (o lo guardamos en un log)
        echo "Error en base de datos: " . $e->getMessage();
    }
    return false;
    }
}
?>