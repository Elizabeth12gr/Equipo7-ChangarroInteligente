<?php
// index.php
session_start();

require_once __DIR__ . '/ayudas/validaciones.php';
require_once __DIR__ . '/controlador/login.php';
require_once __DIR__ . '/controlador/negocio.php';
require_once __DIR__ . '/controlador/producto.php';
require_once __DIR__ . '/controlador/pedido.php';
require_once __DIR__ . '/controlador/cliente.php';
require_once __DIR__ . '/controlador/proveedor.php';
require_once __DIR__ . '/controlador/agente.php';

$accion = $_GET['accion'] ?? 'login';

switch ($accion) {
    // --- FLUJO DE ACCESO Y PERFIL DE DUEÑO (RF-01) ---
    case 'procesar_acceso':
        (new AuthController())->procesarAcceso();
        break;
    case 'salir':
        (new AuthController())->salir();
        break;
    case 'perfil':
        (new AuthController())->mostrarPerfil();
        break;
    case 'editar_perfil':
        (new AuthController())->editarPerfil();
        break;
    case 'eliminar_cuenta':
        (new AuthController())->eliminarCuenta();
        break;

    // --- FLUJO DE NEGOCIOS Y ESPACIO ADAPTABLE (RF-01, RF-02) ---
    case 'nuevo_negocio':
        (new NegocioController())->mostrarFormulario();
        break;
    case 'guardar_negocio':
        (new NegocioController())->guardarNegocio();
        break;
    case 'espacio':
        (new NegocioController())->mostrarEspacio();
        break;
    case 'configurar_negocio':
        (new NegocioController())->mostrarConfiguracion();
        break;
    case 'editar_negocio':
        (new NegocioController())->editarNegocio();
        break;
    case 'eliminar_negocio':
        (new NegocioController())->eliminarNegocio();
        break;

    // --- FLUJO DE PRODUCTOS, VOZ, FOTO Y ALERTAS (RF-03, RF-05, RF-06, RF-07, RF-08, RF-16) ---
    case 'productos':
        (new ProductoController())->mostrarCatalogo();
        break;
    case 'guardar_producto':
        (new ProductoController())->guardarProducto();
        break;
    case 'editar_producto':
        (new ProductoController())->actualizarProducto();
        break;
    case 'actualizar_stock':
        (new ProductoController())->actualizarStock();
        break;
    case 'eliminar_producto':
        (new ProductoController())->eliminarProducto();
        break;
    case 'voz_inventario':
        (new ProductoController())->procesarVozInventario();
        break;

    // --- FLUJO DE VENTAS, TICKETS, ANÁLISIS, FINANZAS, AGENDA Y REPORTES ---
    case 'ventas':
        (new PedidoController())->mostrarVentas();
        break;
    case 'guardar_venta':
        (new PedidoController())->registrarVenta();
        break;
    case 'ticket':
        require __DIR__ . '/vista/ticket.php';
        break;
    case 'estado_venta':
        (new PedidoController())->cambiarEstado();
        break;
    case 'eliminar_venta':
        (new PedidoController())->eliminarVenta();
        break;
    case 'voz_venta':
        (new PedidoController())->procesarVozVenta();
        break;
    case 'analisis':
        (new PedidoController())->mostrarAnalisis();
        break;
    case 'finanzas':
        (new PedidoController())->mostrarFinanzas();
        break;
    case 'aplicar_precio':
        (new PedidoController())->aplicarPrecioSugerido();
        break;
    case 'agenda':
        (new PedidoController())->mostrarAgenda();
        break;
    case 'guardar_agenda':
        (new PedidoController())->guardarAgenda();
        break;
    case 'reportes':
        (new PedidoController())->mostrarReportes();
        break;
    case 'exportar_csv':
        (new PedidoController())->exportarCSV();
        break;

    // --- FLUJO DE CLIENTES Y COMUNICACIÓN (RF-11, RF-12) ---
    case 'clientes':
        (new ClienteController())->mostrarClientes();
        break;
    case 'guardar_cliente':
        (new ClienteController())->guardarCliente();
        break;
    case 'editar_cliente':
        (new ClienteController())->actualizarCliente();
        break;
    case 'eliminar_cliente':
        (new ClienteController())->eliminarCliente();
        break;

    // --- FLUJO DE PROVEEDORES Y PRECIOS DE REFERENCIA (RF-15, RF-16) ---
    case 'proveedores':
        (new ProveedorController())->mostrarProveedores();
        break;
    case 'guardar_proveedor':
        (new ProveedorController())->guardarProveedor();
        break;
    case 'eliminar_proveedor':
        (new ProveedorController())->eliminarProveedor();
        break;

    // --- VOZ Y TEXTO ---
    case 'procesar_multimodal':
        (new ProductoController())->procesarMultimodal();
        break;
    case 'guardar_lote_inventario':
        (new ProductoController())->guardarLoteInventario();
        break;

    // --- AGENTE DE WHATSAPP (atiende y agenda) ---
    case 'agente':
        (new AgenteController())->mostrar();
        break;
    case 'guardar_agente':
        (new AgenteController())->guardarConfig();
        break;
    case 'guardar_duraciones':
        (new AgenteController())->guardarDuraciones();
        break;
    case 'estado_conversacion':
        (new AgenteController())->cambiarEstado();
        break;
    case 'enviar_mensaje_wa':
        (new AgenteController())->enviarMensaje();
        break;

    // --- VISTA POR DEFECTO ---
    default:
        (new AuthController())->mostrarLogin();
        break;
}
?>