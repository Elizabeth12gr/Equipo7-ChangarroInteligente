-- Creación de tablas para la Fondita Inteligente (MVP)

CREATE TABLE negocios (
    id_negocios INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono_contacto VARCHAR(20),
    ubicacion VARCHAR(100),
    mensaje_bienvenida TEXT,
    horario VARCHAR(100)
);

CREATE TABLE clientes (
    id_clientes INT AUTO_INCREMENT PRIMARY KEY,
    telefono_whatsapp VARCHAR(20) UNIQUE NOT NULL,
    nombre_perfil VARCHAR(100),
    nombre_cliente VARCHAR(100),
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE productos (
    id_productos INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10, 2) NOT NULL,
    disponible BOOLEAN DEFAULT TRUE
);

CREATE TABLE pedidos (
    id_pedidos INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    que_pidio TEXT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    estado ENUM('pendiente', 'confirmado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    metodo_pago ENUM('efectivo', 'transferencia', 'terminal') DEFAULT 'efectivo',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id_clientes)
);
