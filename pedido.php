<?php
require_once 'conexion.php';

class Pedido {
    private $conn;

    public function __construct() {
        $database = new Conexion();
        $this->conn = $database->getConnection();
    }

    // Crea el pedido, guarda los detalles y descuenta el stock de forma segura
    public function crearPedidoCompleto($negocio_id, $cliente_id, $total, $metodo_pago, $lista_productos) {
        try {
            // 1. Iniciamos la transacción (Si algo falla más adelante, nada de esto se guarda)
            $this->conn->beginTransaction();

            // 2. Insertamos el registro general en la tabla pedidos
            $queryPedido = "INSERT INTO pedidos (negocio_id, cliente_id, total, metodo_pago) 
                            VALUES (:negocio_id, :cliente_id, :total, :metodo_pago)";
            $stmtPedido = $this->conn->prepare($queryPedido);
            $stmtPedido->execute([
                ':negocio_id' => $negocio_id,
                ':cliente_id' => $cliente_id,
                ':total' => $total,
                ':metodo_pago' => $metodo_pago
            ]);
            
            // Obtenemos el ID del pedido que se acaba de crear
            $pedido_id = $this->conn->lastInsertId();

            // 3. Preparamos las consultas para los detalles y el inventario
            $queryDetalle = "INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) 
                             VALUES (:pedido_id, :producto_id, :cantidad, :precio_unitario)";
            $stmtDetalle = $this->conn->prepare($queryDetalle);

            // Nota: Se valida que el stock sea mayor o igual a la cantidad solicitada
            $queryStock = "UPDATE productos SET stock = stock - :cantidad 
                           WHERE id_productos = :producto_id AND stock >= :cantidad";
            $stmtStock = $this->conn->prepare($queryStock);

            // 4. Recorremos el carrito de compras (lista de productos)
            foreach ($lista_productos as $item) {
                // A) Guardamos en detalles_pedido
                $stmtDetalle->execute([
                    ':pedido_id' => $pedido_id,
                    ':producto_id' => $item['id_producto'],
                    ':cantidad' => $item['cantidad'],
                    ':precio_unitario' => $item['precio_unitario']
                ]);

                // B) Descontamos del inventario (productos)
                $stmtStock->execute([
                    ':cantidad' => $item['cantidad'],
                    ':producto_id' => $item['id_producto']
                ]);

                // Validamos si realmente se pudo descontar. Si rowCount es 0, significa que no había stock suficiente.
                if ($stmtStock->rowCount() == 0) {
                    throw new Exception("Stock insuficiente para el producto ID: " . $item['id_producto']);
                }
            }

            // 5. Si todo salió bien, confirmamos los cambios de forma permanente
            $this->conn->commit();
            return $pedido_id;

        } catch (Exception $e) {
            // Si hubo CUALQUIER error (incluyendo la falta de stock), revertimos todo
            $this->conn->rollBack();
            echo "Error al procesar el pedido: " . $e->getMessage();
            return false;
        }
    }
}
?>