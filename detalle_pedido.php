<?php
require_once 'conexion.php';

class DetallePedido {
    private $conn;
    private $table_name = "detalles_pedido";

    public function __construct() {
        $database = new Conexion();
        $this->conn = $database->getConnection();
    }

    // Registra un producto individual dentro de un pedido
    public function registrarDetalle($pedido_id, $producto_id, $cantidad, $precio_unitario) {
        try {
            $query = "INSERT INTO " . $this->table_name . " (pedido_id, producto_id, cantidad, precio_unitario) 
                      VALUES (:pedido_id, :producto_id, :cantidad, :precio_unitario)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":pedido_id", $pedido_id);
            $stmt->bindParam(":producto_id", $producto_id);
            $stmt->bindParam(":cantidad", $cantidad);
            $stmt->bindParam(":precio_unitario", $precio_unitario);

            return $stmt->execute();
        } catch(PDOException $e) {
            echo "Error al registrar detalle: " . $e->getMessage();
            return false;
        }
    }

    // Obtiene los detalles de un pedido específico (Útil para que el Dev 3 muestre un recibo o resumen)
    public function obtenerDetallesPorPedido($pedido_id) {
        try {
            // Hacemos JOIN con la tabla de productos para obtener el nombre del platillo/artículo
            $query = "SELECT dp.cantidad, dp.precio_unitario, p.nombre 
                      FROM " . $this->table_name . " dp
                      INNER JOIN productos p ON dp.producto_id = p.id_productos
                      WHERE dp.pedido_id = :pedido_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":pedido_id", $pedido_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Error al obtener detalles: " . $e->getMessage();
            return [];
        }
    }

    // Leer (Saber cuántas veces se ha vendido un producto específico en total)
    public function obtenerVentasPorProducto($producto_id) {
        $query = "SELECT SUM(cantidad) as total_vendido FROM detalles_pedido WHERE producto_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $producto_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Eliminar (Borrar todos los detalles de un pedido antes de borrar el pedido en sí para evitar error de llave foránea)
    public function eliminarDetallesDePedido($pedido_id) {
        try {
            $query = "DELETE FROM detalles_pedido WHERE pedido_id = :pedido_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":pedido_id", $pedido_id);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
