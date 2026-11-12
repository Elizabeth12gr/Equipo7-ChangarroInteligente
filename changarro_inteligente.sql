-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-11-2026 a las 16:40:21
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `changarro_inteligente`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_clientes` int(11) NOT NULL,
  `telefono_whatsapp` varchar(20) NOT NULL,
  `nombre_perfil` varchar(100) DEFAULT NULL,
  `nombre_cliente` varchar(100) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_clientes`, `telefono_whatsapp`, `nombre_perfil`, `nombre_cliente`, `fecha_registro`) VALUES
(1, '5212291102030', 'Ana G.', 'Ana García Torres', '2026-11-12 09:39:42'),
(2, '5212292203040', 'Luis R.', 'Luis Alberto Ruiz', '2026-11-12 09:39:42'),
(3, '5212293304050', 'Mariana S.', 'Mariana Sánchez', '2026-11-12 09:39:42'),
(4, '5212294405060', 'Jorge P.', 'Jorge Pérez Hernández', '2026-11-12 09:39:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conversaciones`
--

CREATE TABLE `conversaciones` (
  `id_conversaciones` int(11) NOT NULL,
  `negocio_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `wa_id` varchar(25) NOT NULL,
  `estado` enum('agente','humano') NOT NULL DEFAULT 'agente',
  `motivo_humano` varchar(255) DEFAULT NULL,
  `ultimo_mensaje_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pedido`
--

CREATE TABLE `detalles_pedido` (
  `id_detalles` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalles_pedido`
--

INSERT INTO `detalles_pedido` (`id_detalles`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 1, 2, 42.00),
(2, 1, 2, 1, 48.00),
(3, 2, 4, 1, 88.00),
(4, 2, 5, 1, 45.50),
(5, 3, 4, 2, 88.00),
(6, 4, 6, 1, 38.00),
(7, 4, 7, 1, 95.00),
(8, 5, 8, 2, 29.00),
(9, 5, 9, 1, 34.00),
(10, 5, 10, 1, 48.00),
(11, 6, 7, 1, 95.00),
(12, 6, 10, 1, 52.00),
(13, 7, 11, 1, 180.00),
(14, 7, 12, 1, 250.00),
(15, 8, 13, 1, 300.00),
(16, 8, 15, 1, 195.00),
(17, 9, 14, 1, 220.00),
(18, 10, 16, 1, 350.00),
(19, 10, 17, 1, 650.00),
(20, 11, 18, 1, 400.00),
(21, 11, 20, 1, 240.00),
(22, 12, 19, 1, 380.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `duenos`
--

CREATE TABLE `duenos` (
  `id_duenos` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `duenos`
--

INSERT INTO `duenos` (`id_duenos`, `nombre`, `telefono`, `correo`, `password`, `fecha_registro`) VALUES
(1, 'Carlos Mendoza', '2291000001', 'laesquina@changarro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-11-12 09:39:42'),
(2, 'Fernanda López', '2291000002', 'elpuerto@changarro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-11-12 09:39:42'),
(3, 'Valeria Morales', '2291000003', 'glamour@changarro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-11-12 09:39:42'),
(4, 'Sofía Domínguez', '2291000004', 'salonveracruz@changarro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-11-12 09:39:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id_mensajes` bigint(20) NOT NULL,
  `conversacion_id` int(11) NOT NULL,
  `rol` enum('cliente','agente','dueno') NOT NULL,
  `contenido` text NOT NULL,
  `wa_message_id` varchar(100) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `negocios`
--

CREATE TABLE `negocios` (
  `id_negocios` int(11) NOT NULL,
  `dueno_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `mensaje_bienvenida` text DEFAULT NULL,
  `horario` varchar(100) DEFAULT NULL,
  `agente_activo` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_phone_id` varchar(40) DEFAULT NULL,
  `hora_apertura` time NOT NULL DEFAULT '09:00:00',
  `hora_cierre` time NOT NULL DEFAULT '20:00:00',
  `dias_atencion` varchar(20) NOT NULL DEFAULT '1,2,3,4,5,6',
  `capacidad_simultanea` tinyint(4) NOT NULL DEFAULT 1,
  `duracion_default_min` smallint(6) NOT NULL DEFAULT 30,
  `intervalo_min` smallint(6) NOT NULL DEFAULT 30,
  `anticipacion_min` smallint(6) NOT NULL DEFAULT 60,
  `agente_instrucciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `negocios`
--

INSERT INTO `negocios` (`id_negocios`, `dueno_id`, `nombre`, `tipo`, `telefono_contacto`, `ubicacion`, `mensaje_bienvenida`, `horario`, `agente_activo`, `whatsapp_phone_id`, `hora_apertura`, `hora_cierre`, `dias_atencion`, `capacidad_simultanea`, `duracion_default_min`, `intervalo_min`, `anticipacion_min`, `agente_instrucciones`) VALUES
(2, 1, 'Abarrotes La Esquina', 'abarrotes', NULL, 'Av. Independencia #450, Centro, Veracruz', NULL, '8:00 AM - 10:00 PM', 0, NULL, '09:00:00', '20:00:00', '1,2,3,4,5,6', 1, 30, 30, 60, NULL),
(3, 2, 'Mini Súper El Puerto', 'abarrotes', NULL, 'Av. Díaz Mirón #1120, Col. Centro, Veracruz', NULL, '7:30 AM - 10:30 PM', 0, NULL, '09:00:00', '20:00:00', '1,2,3,4,5,6', 1, 30, 30, 60, NULL),
(4, 3, 'Estética Glamour Boca', 'estetica', NULL, 'Blvd. Adolfo Ruiz Cortines #1200, Boca del Río', NULL, '9:00 AM - 8:00 PM', 0, NULL, '09:00:00', '20:00:00', '1,2,3,4,5,6', 1, 30, 30, 60, NULL),
(5, 4, 'Salón & Spa Veracruz', 'estetica', NULL, 'Av. Cristóbal Colón #340, Fracc. Reforma, Veracruz', NULL, '10:00 AM - 8:00 PM', 0, NULL, '09:00:00', '20:00:00', '1,2,3,4,5,6', 1, 30, 30, 60, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedidos` int(11) NOT NULL,
  `negocio_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','transferencia','terminal','tarjeta') DEFAULT 'efectivo',
  `tipo_pedido` enum('a domicilio','tienda') DEFAULT NULL,
  `estado` enum('pendiente','confirmado','entregado','cancelado') DEFAULT 'pendiente',
  `creado_en` datetime DEFAULT current_timestamp(),
  `fecha_entrega` datetime DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `fecha_pedido` datetime GENERATED ALWAYS AS (`creado_en`) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedidos`, `negocio_id`, `cliente_id`, `total`, `metodo_pago`, `tipo_pedido`, `estado`, `creado_en`, `fecha_entrega`, `notas`) VALUES
(1, 2, 1, 132.00, 'efectivo', 'tienda', 'entregado', '2026-11-12 09:39:42', '2026-10-22 09:39:42', 'Recoger en tienda - Listo en 5 min'),
(2, 2, 2, 160.50, 'tarjeta', 'a domicilio', 'entregado', '2026-11-12 09:39:42', '2026-10-29 09:39:42', 'Envío a domicilio (2.4 km) - Envío: $27 - Llegada est: 18 min'),
(3, 2, 3, 176.00, 'efectivo', 'tienda', 'entregado', '2026-11-12 09:39:42', '2026-11-07 09:39:42', 'Venta en mostrador POS'),
(4, 3, 2, 133.00, 'efectivo', 'tienda', 'entregado', '2026-11-12 09:39:42', '2026-10-24 09:39:42', 'Recoger en tienda - Listo en 5 min'),
(5, 3, 4, 171.00, 'tarjeta', 'a domicilio', 'entregado', '2026-11-12 09:39:42', '2026-11-01 09:39:42', 'Envío a domicilio (3.1 km) - Envío: $31 - Llegada est: 20 min'),
(6, 3, 1, 147.00, 'efectivo', 'tienda', 'entregado', '2026-11-12 09:39:42', '2026-11-09 09:39:42', 'Venta en mostrador POS'),
(7, 4, 1, 430.00, 'tarjeta', 'tienda', 'entregado', '2026-11-12 09:39:42', '2026-10-26 09:39:42', 'Cita Corte de Cabello + Manicure Gelish'),
(8, 4, 3, 495.00, 'efectivo', 'tienda', 'entregado', '2026-11-12 09:39:42', '2026-11-03 09:39:42', 'Cita Pedicure Spa + Shampoo Hidratante'),
(9, 4, 4, 220.00, 'tarjeta', 'tienda', 'entregado', '2026-11-12 09:39:42', '2026-11-09 09:39:42', 'Cita Planchado de Ceja y Pestañas'),
(10, 5, 3, 1000.00, 'tarjeta', 'tienda', 'entregado', '2026-11-12 09:39:42', '2026-10-24 09:39:42', 'Cita Alaciado Express + Tinte Completo'),
(11, 5, 1, 640.00, 'efectivo', 'tienda', 'entregado', '2026-11-12 09:39:42', '2026-11-01 09:39:42', 'Cita Masaje Relajante + Crema Reparadora'),
(12, 5, 2, 380.00, 'tarjeta', 'tienda', 'entregado', '2026-11-12 09:39:42', '2026-11-07 09:39:42', 'Cita Limpieza Facial Profunda');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_productos` int(11) NOT NULL,
  `negocio_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `costo` decimal(10,2) NOT NULL,
  `precio_mercado` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `duracion_min` smallint(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_productos`, `negocio_id`, `nombre`, `descripcion`, `precio`, `costo`, `precio_mercado`, `stock`, `duracion_min`) VALUES
(1, 2, 'Refresco Cola 2.5L', NULL, 42.00, 0.00, 0.00, 35, NULL),
(2, 2, 'Pan de Caja Blanco Grande', NULL, 48.00, 0.00, 0.00, 22, NULL),
(3, 2, 'Leche Entera 1L', NULL, 27.50, 0.00, 0.00, 45, NULL),
(4, 2, 'Huevo Blanco (Cartera 30 pzas)', NULL, 88.00, 0.00, 0.00, 18, NULL),
(5, 2, 'Aceite Vegetal 900ml', NULL, 45.00, 0.00, 0.00, 28, NULL),
(6, 3, 'Agua Purificada Garrafón 20L', NULL, 38.00, 0.00, 0.00, 25, NULL),
(7, 3, 'Café Soluble Frasco 200g', NULL, 95.00, 0.00, 0.00, 16, NULL),
(8, 3, 'Arroz Súper Extra 1kg', NULL, 29.00, 0.00, 0.00, 40, NULL),
(9, 3, 'Frijol Negro 1kg', NULL, 34.00, 0.00, 0.00, 38, NULL),
(10, 3, 'Galletas Surtidas Paquete Familiar', NULL, 52.00, 0.00, 0.00, 24, NULL),
(11, 4, 'Corte de Cabello Dama/Caballero', NULL, 180.00, 0.00, 0.00, 99, NULL),
(12, 4, 'Manicure + Gelish', NULL, 250.00, 0.00, 0.00, 99, NULL),
(13, 4, 'Pedicure Spa', NULL, 300.00, 0.00, 0.00, 99, NULL),
(14, 4, 'Planchado de Ceja y Pestañas', NULL, 220.00, 0.00, 0.00, 99, NULL),
(15, 4, 'Shampoo Hidratante 500ml', NULL, 195.00, 0.00, 0.00, 20, NULL),
(16, 5, 'Alaciado Express y Peinado', NULL, 350.00, 0.00, 0.00, 99, NULL),
(17, 5, 'Tinte Completo y Matiz', NULL, 650.00, 0.00, 0.00, 99, NULL),
(18, 5, 'Masaje Relajante Espalda 45 min', NULL, 400.00, 0.00, 0.00, 99, NULL),
(19, 5, 'Limpieza Facial Profunda', NULL, 380.00, 0.00, 0.00, 99, NULL),
(20, 5, 'Crema Reparadora Capilar 300ml', NULL, 240.00, 0.00, 0.00, 15, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedores` int(11) NOT NULL,
  `negocio_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_clientes`),
  ADD UNIQUE KEY `telefono_whatsapp` (`telefono_whatsapp`);

--
-- Indices de la tabla `conversaciones`
--
ALTER TABLE `conversaciones`
  ADD PRIMARY KEY (`id_conversaciones`),
  ADD UNIQUE KEY `uq_negocio_wa` (`negocio_id`,`wa_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD PRIMARY KEY (`id_detalles`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `duenos`
--
ALTER TABLE `duenos`
  ADD PRIMARY KEY (`id_duenos`),
  ADD UNIQUE KEY `telefono` (`telefono`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id_mensajes`),
  ADD UNIQUE KEY `uq_wa_message` (`wa_message_id`),
  ADD KEY `conversacion_id` (`conversacion_id`,`id_mensajes`);

--
-- Indices de la tabla `negocios`
--
ALTER TABLE `negocios`
  ADD PRIMARY KEY (`id_negocios`),
  ADD UNIQUE KEY `uq_whatsapp_phone_id` (`whatsapp_phone_id`),
  ADD KEY `dueno_id` (`dueno_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedidos`),
  ADD KEY `negocio_id` (`negocio_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_productos`),
  ADD KEY `negocio_id` (`negocio_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedores`),
  ADD KEY `negocio_id` (`negocio_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_clientes` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `conversaciones`
--
ALTER TABLE `conversaciones`
  MODIFY `id_conversaciones` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  MODIFY `id_detalles` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `duenos`
--
ALTER TABLE `duenos`
  MODIFY `id_duenos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id_mensajes` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `negocios`
--
ALTER TABLE `negocios`
  MODIFY `id_negocios` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedidos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_productos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedores` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `conversaciones`
--
ALTER TABLE `conversaciones`
  ADD CONSTRAINT `conv_fk_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_clientes`) ON DELETE SET NULL,
  ADD CONSTRAINT `conv_fk_negocio` FOREIGN KEY (`negocio_id`) REFERENCES `negocios` (`id_negocios`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD CONSTRAINT `detalles_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id_pedidos`),
  ADD CONSTRAINT `detalles_pedido_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id_productos`);

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `msg_fk_conv` FOREIGN KEY (`conversacion_id`) REFERENCES `conversaciones` (`id_conversaciones`) ON DELETE CASCADE;

--
-- Filtros para la tabla `negocios`
--
ALTER TABLE `negocios`
  ADD CONSTRAINT `negocios_ibfk_1` FOREIGN KEY (`dueno_id`) REFERENCES `duenos` (`id_duenos`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`negocio_id`) REFERENCES `negocios` (`id_negocios`),
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_clientes`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`negocio_id`) REFERENCES `negocios` (`id_negocios`);

--
-- Filtros para la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD CONSTRAINT `proveedores_ibfk_1` FOREIGN KEY (`negocio_id`) REFERENCES `negocios` (`id_negocios`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
