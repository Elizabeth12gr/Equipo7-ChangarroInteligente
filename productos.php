<?php
// Agrega esto a tu clase Producto
    public function registrarProducto($negocio_id, $nombre, $descripcion, $precio, $stock) {
        try {
            $query = "INSERT INTO productos (negocio_id, nombre, descripcion, precio, stock) 
                      VALUES (:negocio_id, :nombre, :descripcion, :precio, :stock)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":negocio_id", $negocio_id);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":descripcion", $descripcion);
            $stmt->bindParam(":precio", $precio);
            $stmt->bindParam(":stock", $stock);

            if($stmt->execute()){
                return $this->conn->lastInsertId();
            }
        } catch(PDOException $e) {
            echo "Error al registrar producto: " . $e->getMessage();
        }
        return false;
    }

// Leer (Consultar un producto específico para editarlo)
    public function obtenerProducto($id_productos) {
        $query = "SELECT * FROM productos WHERE id_productos = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id_productos);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar (Editar nombre, precio o descripción del producto)
    public function actualizarProducto($id_productos, $nombre, $descripcion, $precio) {
        try {
            $query = "UPDATE productos SET nombre = :nombre, descripcion = :desc, precio = :precio 
                      WHERE id_productos = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":desc", $descripcion);
            $stmt->bindParam(":precio", $precio);
            $stmt->bindParam(":id", $id_productos);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    // Actualizar (Solo modificar el stock, ideal para el Dev 3 en el panel de inventario)
    public function actualizarStock($id_productos, $nuevo_stock) {
        try {
            $query = "UPDATE productos SET stock = :stock WHERE id_productos = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":stock", $nuevo_stock);
            $stmt->bindParam(":id", $id_productos);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    // Eliminar (Borrar un producto del menú)
    public function eliminarProducto($id_productos) {
        try {
            $query = "DELETE FROM productos WHERE id_productos = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id_productos);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

?>
