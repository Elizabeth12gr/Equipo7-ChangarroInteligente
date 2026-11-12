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
?>