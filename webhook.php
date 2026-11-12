<?php
date_default_timezone_set('America/Mexico_City');
header('Content-Type: text/xml; charset=utf-8');
require_once __DIR__ . '/conexion.php';

function responderTwiml($mensaje) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Message>' . htmlspecialchars($mensaje, ENT_XML1, 'UTF-8') . '</Message></Response>';
    exit;
}

// Programa una notificación automática hacia bot.js (puerto 3001)
function programarNotificacion($telefono, $mensaje, $delaySegundos = 10) {
    $ch = curl_init('http://127.0.0.1:3001/notificar');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'telefono'      => $telefono,
        'mensaje'       => $mensaje,
        'delaySegundos' => $delaySegundos
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
    @curl_exec($ch);
    @curl_close($ch);
}

// Envía el ticket PDF como archivo adjunto al chat de WhatsApp mediante bot.js
function enviarTicketPdfWhatsapp($telefono, $pedidoId, $nombreNegocio) {
    $folio = str_pad($pedidoId, 5, '0', STR_PAD_LEFT);
    $ch = curl_init('http://127.0.0.1:3001/enviar-pdf');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'telefono'      => $telefono,
        'urlPdf'        => "http://127.0.0.1/Changarro_Perfiles/index.php?accion=ticket&id={$pedidoId}&formato=pdf",
        'nombreArchivo' => "Ticket_{$folio}.pdf",
        'caption'       => "🧾 Comprobante de compra #{$folio} · {$nombreNegocio}"
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000);
    @curl_exec($ch);
    @curl_close($ch);
}

// Calcula distancia en km entre dos coordenadas (Haversine)
function calcularDistanciaKm($lat1, $lon1, $lat2, $lon2) {
    $radioTierra = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($radioTierra * $c, 2);
}

// Coordenadas de los negocios en Heroica Veracruz / Boca del Río
function obtenerCoordenadasNegocio($negocioId) {
    $ubicaciones = [
        2 => ['lat' => 19.1685, 'lng' => -96.1390],
        3 => ['lat' => 19.1810, 'lng' => -96.1420],
        4 => ['lat' => 19.1982, 'lng' => -96.1365],
        5 => ['lat' => 19.1765, 'lng' => -96.1255],
        6 => ['lat' => 19.1395, 'lng' => -96.1065],
        7 => ['lat' => 19.1835, 'lng' => -96.1350],
        8 => ['lat' => 19.2005, 'lng' => -96.1385],
    ];
    return $ubicaciones[$negocioId] ?? ['lat' => 19.1800, 'lng' => -96.1350];
}

// Detecta si el ítem es un servicio (corte, manicure, pedicure, uñas, etc.)
function esServicio($nombreItem) {
    $palabrasServicio = [
        'corte', 'manicure', 'pedicure', 'uña', 'unas', 'peinado',
        'barba', 'tinte', 'alaciado', 'facial', 'masaje', 'depilacion',
        'depilación', 'ceja', 'pestaña', 'pestana', 'planchado', 'gelish',
        'perfilado', 'servicio', 'cita'
    ];
    $texto = mb_strtolower($nombreItem, 'UTF-8');
    foreach ($palabrasServicio as $palabra) {
        if (mb_strpos($texto, $palabra) !== false) {
            return true;
        }
    }
    return false;
}

// Convierte texto de horario (ej. "9:00 AM - 9:00 PM") a minutos desde medianoche
function parsearRangoHorario($horarioTexto) {
    if (preg_match('/(\d{1,2}:\d{2}\s*(?:AM|PM)?)\s*-\s*(\d{1,2}:\d{2}\s*(?:AM|PM)?)/i', $horarioTexto, $m)) {
        $inicioTs = strtotime("1970-01-01 " . trim($m[1]));
        $finTs    = strtotime("1970-01-01 " . trim($m[2]));
        if ($inicioTs !== false && $finTs !== false) {
            $minInicio = ((int)date('H', $inicioTs) * 60) + (int)date('i', $inicioTs);
            $minFin    = ((int)date('H', $finTs) * 60) + (int)date('i', $finTs);
            return [$minInicio, $minFin];
        }
    }
    return [9 * 60, 20 * 60];
}

// Valida fecha (DD/MM/AAAA) y hora (HH:MM), que no sea pasada, dentro de horario y sin empalmes
function validarCitaCompleta($pdo, $negocioId, $horarioNegocio, $entradaUsuario, $carritoActual) {
    if (!preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/', trim($entradaUsuario), $m)) {
        return [
            'ok'    => false,
            'error' => "⚠️ Formato incorrecto.\nEscribe la *fecha y hora* así: *DD/MM/AAAA HH:MM*\n(Ejemplo: *" . date('d/m/Y', strtotime('+1 day')) . " 16:30*)"
        ];
    }

    $dia  = (int)$m[1];
    $mes  = (int)$m[2];
    $anio = (int)$m[3];
    $hora = (int)$m[4];
    $min  = (int)$m[5];

    if (!checkdate($mes, $dia, $anio) || $hora < 0 || $hora > 23 || $min < 0 || $min > 59) {
        return [
            'ok'    => false,
            'error' => "⚠️ La fecha o la hora no son válidas. Revisa el día del mes y usa hora de 00:00 a 23:59."
        ];
    }

    $fechaNorm = sprintf('%02d/%02d/%04d', $dia, $mes, $anio);
    $horaNorm  = sprintf('%02d:%02d', $hora, $min);
    $citaClave = "$fechaNorm $horaNorm";
    $fechaSql  = sprintf('%04d-%02d-%02d %02d:%02d:00', $anio, $mes, $dia, $hora, $min);

    $diasEs    = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $tsCita    = mktime($hora, $min, 0, $mes, $dia, $anio);
    $nombreDia = $diasEs[(int)date('w', $tsCita)];

    if ($tsCita <= (time() + 300)) {
        return [
            'ok'    => false,
            'error' => "⏳ No puedes agendar en una fecha u hora que ya pasó.\nHoy es *" . date('d/m/Y H:i') . "*. Elige una fecha y hora futura:"
        ];
    }

    [$minApertura, $minCierre] = parsearRangoHorario($horarioNegocio);
    $minSolicitado = ($hora * 60) + $min;

    if ($minSolicitado < $minApertura || $minSolicitado > ($minCierre - 30)) {
        return [
            'ok'    => false,
            'error' => "🕒 Ese horario está fuera de servicio.\nEl horario de atención de este negocio es *$horarioNegocio* (última cita 30 min antes del cierre).\n\nEscribe otra fecha y hora dentro de ese horario:"
        ];
    }

    foreach ($carritoActual as $item) {
        if (!empty($item['cita_clave']) && $item['cita_clave'] === $citaClave) {
            return [
                'ok'    => false,
                'error' => "🚫 Ya tienes otro servicio agendado en tu carrito para el *$nombreDia $citaClave*.\nElige una hora diferente:"
            ];
        }
    }

    $stmtDup = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE negocio_id = ? AND estado != 'cancelado' AND notas LIKE ?");
    $stmtDup->execute([$negocioId, "%$citaClave%"]);
    if ((int)$stmtDup->fetchColumn() > 0) {
        return [
            'ok'    => false,
            'error' => "🚫 Lo sentimos, ya existe una cita reservada para el *$nombreDia $citaClave* en este local.\n\nPor favor escribe otra fecha u hora disponible:"
        ];
    }

    return [
        'ok'          => true,
        'cita_clave'  => $citaClave,
        'cita_sql'    => $fechaSql,
        'cita_bonita' => "$nombreDia $fechaNorm a las $horaNorm hrs"
    ];
}

if (!isset($pdo)) {
    if (class_exists('Conexion')) {
        $db = new Conexion();
        $pdo = method_exists($db, 'getConnection') ? $db->getConnection() : $db->conectar();
    } else {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=changarro_inteligente;charset=utf8mb4", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
}

$mensaje     = trim($_POST['Body'] ?? '');
$from        = $_POST['From'] ?? '';
$profileName = trim($_POST['ProfileName'] ?? 'Cliente');
$telefono    = str_replace('whatsapp:', '', $from);
$latUsuario  = $_POST['Latitude'] ?? null;
$lngUsuario  = $_POST['Longitude'] ?? null;

if ($telefono === '') {
    responderTwiml("Error: Número no recibido.");
}

// Manejo de sesión por archivo JSON
$dirSesiones = __DIR__ . '/sesiones';
if (!is_dir($dirSesiones)) {
    mkdir($dirSesiones, 0777, true);
}
$archivoSesion = $dirSesiones . '/' . preg_replace('/[^0-9]/', '', $telefono) . '.json';
$sesion = file_exists($archivoSesion) ? json_decode(file_get_contents($archivoSesion), true) : [];

// 1. Buscar si el cliente ya está registrado en MySQL
$stmtCli = $pdo->prepare("SELECT * FROM clientes WHERE telefono_whatsapp = ? LIMIT 1");
$stmtCli->execute([$telefono]);
$cliente = $stmtCli->fetch();

// 2. Si NO existe en MySQL, pedir nombre completo
if (!$cliente && ($sesion['paso'] ?? '') !== 'REGISTRANDO_NOMBRE') {
    $sesion['paso'] = 'REGISTRANDO_NOMBRE';
    file_put_contents($archivoSesion, json_encode($sesion));
    responderTwiml("👋 ¡Hola! Bienvenido a *Changarro Inteligente*.\n\nVeo que es tu primera vez aquí. Para registrarte, por favor escribe tu *nombre completo*:");
}

// 3. Guardar nuevo cliente en MySQL
if (!$cliente && ($sesion['paso'] ?? '') === 'REGISTRANDO_NOMBRE') {
    $nombreCliente = $mensaje;
    $insertCli = $pdo->prepare("INSERT INTO clientes (telefono_whatsapp, nombre_perfil, nombre_cliente) VALUES (?, ?, ?)");
    $insertCli->execute([$telefono, $profileName, $nombreCliente]);

    $sesion = ['paso' => 'ELIGIENDO_CATEGORIA', 'carrito' => []];
    file_put_contents($archivoSesion, json_encode($sesion));

    responderTwiml("✅ ¡Listo, *$nombreCliente*! Quedaste registrado en el sistema.\n\n¿Qué tipo de negocio buscas hoy?\n*1.* 🛒 Abarrotes\n*2.* 💇‍♀️ Estética");
}

// Reiniciar al menú principal
$nombreMostrar = $cliente['nombre_cliente'] ?: $profileName;
if (in_array(strtolower($mensaje), ['hola', 'menu', 'menú', 'inicio'])) {
    $sesion = ['paso' => 'ELIGIENDO_CATEGORIA', 'carrito' => []];
    file_put_contents($archivoSesion, json_encode($sesion));
    responderTwiml("👋 ¡Hola de nuevo, *$nombreMostrar*!\n\n¿Qué tipo de negocio buscas hoy?\n*1.* 🛒 Abarrotes\n*2.* 💇‍♀️ Estética");
}

$paso = $sesion['paso'] ?? 'ELIGIENDO_CATEGORIA';

function obtenerTextoCatalogo($pdo, $negocioId, $tipoNegocio, &$mapaProductos) {
    $stmtProd = $pdo->prepare("SELECT id_productos, nombre, precio, stock FROM productos WHERE negocio_id = ? AND stock > 0");
    $stmtProd->execute([$negocioId]);
    $productos = $stmtProd->fetchAll();

    if (!$productos) return '';

    $titulo = ($tipoNegocio === 'estetica') ? "💇‍♀️ *Servicios y productos disponibles:*" : "📦 *Catálogo disponible:*";
    $catTexto = "$titulo\n";
    $mapaProductos = [];
    foreach ($productos as $i => $prod) {
        $num = $i + 1;
        $mapaProductos[$num] = $prod['id_productos'];
        if (esServicio($prod['nombre'])) {
            $catTexto .= "*$num.* {$prod['nombre']} - *\${$prod['precio']}* (Agendar cita)\n";
        } else {
            $catTexto .= "*$num.* {$prod['nombre']} - *\${$prod['precio']}* (Stock: {$prod['stock']})\n";
        }
    }
    return $catTexto;
}

// PASO A: Elegir categoría
if ($paso === 'ELIGIENDO_CATEGORIA') {
    $tipo = '';
    if ($mensaje === '1' || stripos($mensaje, 'abarrote') !== false) $tipo = 'abarrotes';
    if ($mensaje === '2' || stripos($mensaje, 'estetica') !== false || stripos($mensaje, 'estética') !== false) $tipo = 'estetica';

    if ($tipo === '') {
        responderTwiml("Por favor responde *1* para Abarrotes o *2* para Estética.");
    }

    $stmtNeg = $pdo->prepare("SELECT id_negocios, nombre, ubicacion, horario FROM negocios WHERE tipo = ?");
    $stmtNeg->execute([$tipo]);
    $negocios = $stmtNeg->fetchAll();

    if (!$negocios) {
        responderTwiml("Por el momento no hay negocios registrados en *$tipo*. Escribe *Menu* para volver.");
    }

    $listaTexto = "🏪 *Negocios disponibles en " . ucfirst($tipo) . ":*\n\n";
    $mapaNegocios = [];
    foreach ($negocios as $i => $neg) {
        $num = $i + 1;
        $mapaNegocios[$num] = $neg['id_negocios'];
        $listaTexto .= "*$num.* {$neg['nombre']} 📍 {$neg['ubicacion']} (Horario: {$neg['horario']})\n";
    }
    $listaTexto .= "\nResponde con el *número* del negocio que deseas ver:";

    $sesion['paso'] = 'ELIGIENDO_NEGOCIO';
    $sesion['tipo_negocio'] = $tipo;
    $sesion['mapa_negocios'] = $mapaNegocios;
    file_put_contents($archivoSesion, json_encode($sesion));
    responderTwiml($listaTexto);
}

// PASO B: Elegir negocio
if ($paso === 'ELIGIENDO_NEGOCIO') {
    $opcion = (int)$mensaje;
    $negocioId = $sesion['mapa_negocios'][$opcion] ?? null;

    if (!$negocioId) {
        responderTwiml("Opción no válida. Responde con el número del negocio de la lista o escribe *Menu*.");
    }

    $stmtNegInfo = $pdo->prepare("SELECT * FROM negocios WHERE id_negocios = ? LIMIT 1");
    $stmtNegInfo->execute([$negocioId]);
    $negocioInfo = $stmtNegInfo->fetch();

    $tipoNegocio = $sesion['tipo_negocio'] ?? 'abarrotes';
    $mapaProductos = [];
    $catTexto = obtenerTextoCatalogo($pdo, $negocioId, $tipoNegocio, $mapaProductos);

    if ($catTexto === '') {
        $sesion['paso'] = 'ELIGIENDO_CATEGORIA';
        file_put_contents($archivoSesion, json_encode($sesion));
        responderTwiml("Este negocio aún no tiene opciones disponibles. Escribe *Menu* para elegir otro.");
    }

    $sesion['paso'] = 'ELIGIENDO_PRODUCTO';
    $sesion['negocio_id'] = $negocioId;
    $sesion['negocio_nombre'] = $negocioInfo['nombre'];
    $sesion['negocio_ubicacion'] = $negocioInfo['ubicacion'];
    $sesion['negocio_horario'] = $negocioInfo['horario'];
    $sesion['mapa_productos'] = $mapaProductos;
    $sesion['carrito'] = [];
    file_put_contents($archivoSesion, json_encode($sesion));

    $encabezado = "🏪 *{$negocioInfo['nombre']}*\n📍 Ubicación: *{$negocioInfo['ubicacion']}*\n🕒 Horario: *{$negocioInfo['horario']}*\n\n";
    responderTwiml($encabezado . $catTexto . "\nResponde con el *número* de lo que deseas seleccionar:");
}

// PASO C: Elegir producto/servicio o pasar a entrega/pago con "Listo"
if ($paso === 'ELIGIENDO_PRODUCTO') {
    $cmd = strtolower($mensaje);

    if ($cmd === 'vaciar') {
        $sesion['carrito'] = [];
        file_put_contents($archivoSesion, json_encode($sesion));
        responderTwiml("🗑️ Carrito vaciado. Elige un *número* del catálogo para agregar o escribe *Menu*.");
    }

    if (in_array($cmd, ['listo', 'confirmar', 'pagar', 'pedir', 'finalizar'])) {
        $carrito = $sesion['carrito'] ?? [];
        if (empty($carrito)) {
            responderTwiml("Aún no has seleccionado nada. Primero elige un *número* de la lista.");
        }

        $tieneServicios = false;
        $tieneProductos = false;
        foreach ($carrito as $item) {
            if (!empty($item['es_servicio'])) {
                $tieneServicios = true;
            } else {
                $tieneProductos = true;
            }
        }

        if ($tieneServicios) {
            $sesion['tipo_pedido']  = 'tienda';
            $sesion['costo_envio']  = 0;
            $sesion['distancia_km'] = 0;
            $sesion['tiempo_est']   = 0;
            $sesion['paso']         = 'ELIGIENDO_PAGO';
            file_put_contents($archivoSesion, json_encode($sesion));

            $notaMixta = $tieneProductos
                ? "📅 *Tu cita y tus productos se entregarán juntos en sucursal el día de tu reservación (sin costo de envío).*\n"
                : "📅 *Tu cita se realizará en sucursal.*\n";

            $ubic = $sesion['negocio_ubicacion'] ?? '';
            responderTwiml($notaMixta . "📍 Ubicación: *$ubic*\n\n💳 *Selecciona tu método de pago:*\n*1.* 💵 Efectivo\n*2.* 💳 Tarjeta\n\nResponde *1* o *2*:");
        }

        $sesion['paso'] = 'ELIGIENDO_ENTREGA';
        file_put_contents($archivoSesion, json_encode($sesion));

        responderTwiml("🚚 *¿Cómo deseas recibir tu pedido?*\n\n*1.* 🏪 Recoger en tienda (Listo en 5 min)\n*2.* 🛵 Envío a domicilio (Calculado desde tu ubicación)\n\nResponde *1* o *2*:");
    }

    $opcion = (int)$mensaje;
    $productoId = $sesion['mapa_productos'][$opcion] ?? null;

    if (!$productoId) {
        responderTwiml("Opción no válida. Responde con el *número* de la lista, escribe *Listo* para continuar o *Menu* para salir.");
    }

    $stmtP = $pdo->prepare("SELECT * FROM productos WHERE id_productos = ? LIMIT 1");
    $stmtP->execute([$productoId]);
    $producto = $stmtP->fetch();

    if (!$producto || $producto['stock'] < 1) {
        responderTwiml("Esa opción ya no está disponible. Elige otro número de la lista.");
    }

    if (esServicio($producto['nombre'])) {
        $sesion['paso'] = 'AGENDANDO_CITA';
        $sesion['producto_seleccionado'] = $producto;
        file_put_contents($archivoSesion, json_encode($sesion));

        $horarioLocal = $sesion['negocio_horario'] ?? '9:00 AM - 8:00 PM';
        $ubicLocal    = $sesion['negocio_ubicacion'] ?? '';
        $ejemploFecha = date('d/m/Y', strtotime('+1 day')) . ' 16:00';

        responderTwiml(
            "💇‍♀️ Seleccionaste: *{$producto['nombre']}* (\${$producto['precio']})\n" .
            "📍 Lugar: *{$sesion['negocio_nombre']}* ({$ubicLocal})\n" .
            "🕒 Horario de atención: *$horarioLocal*\n\n" .
            "📅 *Escribe la fecha (DD/MM/AAAA) y la hora (HH:MM) para tu cita:*\n" .
            "(Ejemplo: *$ejemploFecha*)"
        );
    }

    $yaEnCarrito = $sesion['carrito'][$productoId]['cantidad'] ?? 0;
    $maxDisponible = $producto['stock'] - $yaEnCarrito;

    if ($maxDisponible <= 0) {
        responderTwiml("Ya agregaste el máximo disponible ({$producto['stock']}) de *{$producto['nombre']}*. Elige otro o escribe *Listo*.");
    }

    $sesion['paso'] = 'ELIGIENDO_CANTIDAD';
    $sesion['producto_seleccionado'] = $producto;
    file_put_contents($archivoSesion, json_encode($sesion));

    responderTwiml("🛒 Seleccionaste: *{$producto['nombre']}* (\${$producto['precio']} c/u)\nDisponibles para agregar: *$maxDisponible*\n\n¿Cuántas piezas deseas llevar? (Escribe solo el número, ej. *2*):");
}

// PASO C2: Validar y registrar fecha/hora de la cita
if ($paso === 'AGENDANDO_CITA') {
    $prod = $sesion['producto_seleccionado'] ?? null;

    if (!$prod) {
        $sesion['paso'] = 'ELIGIENDO_PRODUCTO';
        file_put_contents($archivoSesion, json_encode($sesion));
        responderTwiml("Vuelve a elegir el número del servicio.");
    }

    $horarioNegocio = $sesion['negocio_horario'] ?? '9:00 AM - 8:00 PM';
    $validacion = validarCitaCompleta($pdo, $sesion['negocio_id'], $horarioNegocio, $mensaje, $sesion['carrito'] ?? []);

    if (!$validacion['ok']) {
        responderTwiml($validacion['error']);
    }

    $keyCarrito = 'srv_' . $prod['id_productos'] . '_' . time();
    $sesion['carrito'][$keyCarrito] = [
        'producto_id' => $prod['id_productos'],
        'nombre'      => $prod['nombre'],
        'precio'      => $prod['precio'],
        'cantidad'    => 1,
        'es_servicio' => true,
        'cita_clave'  => $validacion['cita_clave'],
        'cita_sql'    => $validacion['cita_sql'],
        'cita'        => $validacion['cita_bonita']
    ];

    $resumenCarrito = "📋 *Tu selección actual:*\n";
    $totalAcumulado = 0;
    foreach ($sesion['carrito'] as $item) {
        $sub = $item['precio'] * $item['cantidad'];
        $totalAcumulado += $sub;
        if (!empty($item['es_servicio'])) {
            $resumenCarrito .= "• 💇‍♀️ {$item['nombre']} (*{$item['cita']}*) = \$$sub\n";
        } else {
            $resumenCarrito .= "• {$item['cantidad']}x {$item['nombre']} = \$$sub\n";
        }
    }
    $resumenCarrito .= "*Subtotal:* \$$totalAcumulado MXN\n\n";

    $tipoNegocio = $sesion['tipo_negocio'] ?? 'estetica';
    $mapaProductos = [];
    $catTexto = obtenerTextoCatalogo($pdo, $sesion['negocio_id'], $tipoNegocio, $mapaProductos);

    $sesion['paso'] = 'ELIGIENDO_PRODUCTO';
    $sesion['mapa_productos'] = $mapaProductos;
    unset($sesion['producto_seleccionado']);
    file_put_contents($archivoSesion, json_encode($sesion));

    responderTwiml("✅ Cita reservada para el *{$validacion['cita_bonita']}*.\n\n" . $resumenCarrito . $catTexto . "\n👉 Responde con otro *número* si deseas agregar otro servicio o producto, o escribe *Listo* para continuar:");
}

// PASO D: Recibir cantidad de producto físico y sumarla al carrito
if ($paso === 'ELIGIENDO_CANTIDAD') {
    $cantidad = (int)$mensaje;
    $prod = $sesion['producto_seleccionado'] ?? null;

    if (!$prod) {
        $sesion['paso'] = 'ELIGIENDO_PRODUCTO';
        file_put_contents($archivoSesion, json_encode($sesion));
        responderTwiml("Vuelve a elegir el número del producto del catálogo.");
    }

    $prodId = $prod['id_productos'];
    $yaEnCarrito = $sesion['carrito'][$prodId]['cantidad'] ?? 0;
    $maxDisponible = $prod['stock'] - $yaEnCarrito;

    if ($cantidad < 1 || $cantidad > $maxDisponible) {
        responderTwiml("⚠️ Cantidad no válida. Escribe un número entre *1* y *$maxDisponible*:");
    }

    $sesion['carrito'][$prodId] = [
        'producto_id' => $prodId,
        'nombre'      => $prod['nombre'],
        'precio'      => $prod['precio'],
        'cantidad'    => $yaEnCarrito + $cantidad,
        'es_servicio' => false
    ];

    $resumenCarrito = "🛍️ *Tu carrito actual:*\n";
    $totalAcumulado = 0;
    foreach ($sesion['carrito'] as $item) {
        $sub = $item['precio'] * $item['cantidad'];
        $totalAcumulado += $sub;
        if (!empty($item['es_servicio'])) {
            $resumenCarrito .= "• 💇‍♀️ {$item['nombre']} (*{$item['cita']}*) = \$$sub\n";
        } else {
            $resumenCarrito .= "• {$item['cantidad']}x {$item['nombre']} = \$$sub\n";
        }
    }
    $resumenCarrito .= "*Subtotal:* \$$totalAcumulado MXN\n\n";

    $tipoNegocio = $sesion['tipo_negocio'] ?? 'abarrotes';
    $mapaProductos = [];
    $catTexto = obtenerTextoCatalogo($pdo, $sesion['negocio_id'], $tipoNegocio, $mapaProductos);

    $sesion['paso'] = 'ELIGIENDO_PRODUCTO';
    $sesion['mapa_productos'] = $mapaProductos;
    unset($sesion['producto_seleccionado']);
    file_put_contents($archivoSesion, json_encode($sesion));

    responderTwiml("✅ Agregado al carrito.\n\n" . $resumenCarrito . $catTexto . "\n👉 Responde con otro *número* para agregar más productos/servicios, o escribe *Listo* para continuar:");
}

// PASO E: Elegir tipo de entrega (Tienda vs Domicilio para productos físicos)
if ($paso === 'ELIGIENDO_ENTREGA') {
    if ($mensaje === '1' || stripos($mensaje, 'tienda') !== false || stripos($mensaje, 'recoger') !== false) {
        $sesion['tipo_pedido']  = 'tienda';
        $sesion['costo_envio']  = 0;
        $sesion['distancia_km'] = 0;
        $sesion['tiempo_est']   = 5;
        $sesion['paso']         = 'ELIGIENDO_PAGO';
        file_put_contents($archivoSesion, json_encode($sesion));

        responderTwiml("🏪 *Entrega:* Recoger en tienda (sin costo extra).\n\n💳 *Selecciona tu método de pago:*\n*1.* 💵 Efectivo\n*2.* 💳 Tarjeta\n\nResponde *1* o *2*:");
    }

    if ($mensaje === '2' || stripos($mensaje, 'domicilio') !== false || stripos($mensaje, 'envio') !== false || stripos($mensaje, 'envío') !== false) {
        $latCliente = $latUsuario ? (float)$latUsuario : 19.1738;
        $lngCliente = $lngUsuario ? (float)$lngUsuario : -96.1342;

        $coordNeg = obtenerCoordenadasNegocio($sesion['negocio_id']);
        $distanciaKm = calcularDistanciaKm($latCliente, $lngCliente, $coordNeg['lat'], $coordNeg['lng']);

        $costoEnvio = max(18, round(15 + ($distanciaKm * 5), 0));
        $tiempoMin  = max(12, (int)round(8 + ($distanciaKm * 4)));

        $sesion['tipo_pedido']  = 'a domicilio';
        $sesion['costo_envio']  = $costoEnvio;
        $sesion['distancia_km'] = $distanciaKm;
        $sesion['tiempo_est']   = $tiempoMin;
        $sesion['paso']         = 'ELIGIENDO_PAGO';
        file_put_contents($archivoSesion, json_encode($sesion));

        responderTwiml(
            "🛵 *Cálculo de Envío a Domicilio*\n" .
            "📍 Origen: Tu ubicación (Heroica Veracruz)\n" .
            "📏 Distancia a la tienda: *{$distanciaKm} km*\n" .
            "💰 Costo de envío: *\${$costoEnvio} MXN*\n" .
            "⏱️ Tiempo estimado de llegada: *{$tiempoMin} minutos*\n\n" .
            "💳 *Selecciona tu método de pago:*\n" .
            "*1.* 💵 Efectivo\n" .
            "*2.* 💳 Tarjeta\n\n" .
            "Responde *1* o *2* para confirmar:"
        );
    }

    responderTwiml("Opción no válida. Responde *1* para Recoger en tienda o *2* para Envío a domicilio.");
}

// PASO F: Elegir método de pago (Efectivo / Tarjeta), guardar en MySQL y enviar Ticket PDF
if ($paso === 'ELIGIENDO_PAGO') {
    $metodoPago = '';
    $metodoTexto = '';
    if ($mensaje === '1' || stripos($mensaje, 'efectivo') !== false) {
        $metodoPago = 'Efectivo';
        $metodoTexto = 'Efectivo';
    } elseif ($mensaje === '2' || stripos($mensaje, 'tarjeta') !== false) {
        $metodoPago = 'Tarjeta';
        $metodoTexto = 'Tarjeta';
    } else {
        responderTwiml("Por favor responde *1* para Efectivo o *2* para Tarjeta.");
    }

    $carrito    = $sesion['carrito'] ?? [];
    $tipoPedido = $sesion['tipo_pedido'] ?? 'tienda';
    $costoEnvio = $sesion['costo_envio'] ?? 0;
    $tiempoEst  = $sesion['tiempo_est'] ?? 5;
    $distKm     = $sesion['distancia_km'] ?? 0;

    try {
        $pdo->beginTransaction();

        $subtotalProductos = 0;
        $lineasTicket = "";
        $citasNotas = [];
        $tieneProductosFisicos = false;
        $tieneServicios = false;
        $fechaEntregaSql = null;

        foreach ($carrito as $key => $item) {
            $prodId = $item['producto_id'] ?? $key;
            $stmtCheck = $pdo->prepare("SELECT nombre, precio, stock FROM productos WHERE id_productos = ? FOR UPDATE");
            $stmtCheck->execute([$prodId]);
            $prodActual = $stmtCheck->fetch();

            if (!$prodActual || $prodActual['stock'] < $item['cantidad']) {
                $pdo->rollBack();
                $disp = $prodActual ? $prodActual['stock'] : 0;
                responderTwiml("⚠️ Sin disponibilidad para *{$item['nombre']}* (quedan $disp). Escribe *Menu* para volver.");
            }

            $sub = $prodActual['precio'] * $item['cantidad'];
            $subtotalProductos += $sub;

            if (!empty($item['es_servicio'])) {
                $tieneServicios = true;
                if ($fechaEntregaSql === null && !empty($item['cita_sql'])) {
                    $fechaEntregaSql = $item['cita_sql'];
                }
                $lineasTicket .= "• 💇‍♀️ {$prodActual['nombre']} — *{$item['cita']}* (\$$sub)\n";
                $citasNotas[] = "Cita {$prodActual['nombre']} [{$item['cita_clave']}]: {$item['cita']}";
            } else {
                $tieneProductosFisicos = true;
                $lineasTicket .= "• {$item['cantidad']}x {$prodActual['nombre']} (\$$sub)\n";
            }
        }

        $totalFinal = $subtotalProductos + $costoEnvio;

        if ($tieneServicios && $tieneProductosFisicos) {
            $notas = implode(" | ", $citasNotas) . " | Entrega de productos en cita";
        } elseif ($tieneServicios) {
            $notas = implode(" | ", $citasNotas);
        } elseif ($tipoPedido === 'a domicilio') {
            $notas = "Envío a domicilio ({$distKm} km) - Envío: \${$costoEnvio} - Llegada est: {$tiempoEst} min";
        } else {
            $notas = "Recoger en tienda - Listo en 5 min";
        }

        $insPed = $pdo->prepare("INSERT INTO pedidos (negocio_id, cliente_id, total, metodo_pago, tipo_pedido, estado, fecha_entrega, notas) VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?)");
        $insPed->execute([$sesion['negocio_id'], $cliente['id_clientes'], $totalFinal, $metodoPago, $tipoPedido, $fechaEntregaSql, $notas]);
        $pedidoId = $pdo->lastInsertId();

        $insDet = $pdo->prepare("INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
        $updStock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id_productos = ?");

        foreach ($carrito as $key => $item) {
            $prodId = $item['producto_id'] ?? $key;
            $insDet->execute([$pedidoId, $prodId, $item['cantidad'], $item['precio']]);
            $updStock->execute([$item['cantidad'], $prodId]);
        }

        $pdo->commit();

        $stmtN = $pdo->prepare("SELECT nombre, ubicacion FROM negocios WHERE id_negocios = ?");
        $stmtN->execute([$sesion['negocio_id']]);
        $negRow = $stmtN->fetch();
        $nombreNegocio = $negRow['nombre'] ?? 'el negocio';
        $ubicacionNeg  = $negRow['ubicacion'] ?? '';

        // 1. Mandar el archivo PDF adjunto directo al chat de WhatsApp del cliente
        enviarTicketPdfWhatsapp($telefono, $pedidoId, $nombreNegocio);

        // 2. Preparar mensaje de confirmación y notificación programada
        if ($tieneServicios) {
            $tituloConfirmacion = $tieneProductosFisicos
                ? "🎉 *¡Cita y Pedido #$pedidoId confirmados!*"
                : "🎉 *¡Cita #$pedidoId agendada con éxito!*";

            $extraProductos = $tieneProductosFisicos
                ? "\n🛍️ *Tus productos estarán listos en mostrador el día de tu cita.*\n"
                : "\n";

            $msgFinal = "$tituloConfirmacion\n\n" .
                        "🏪 *Lugar:* $nombreNegocio\n" .
                        "📍 *Ubicación:* $ubicacionNeg\n\n" .
                        "$lineasTicket" .
                        "$extraProductos" .
                        "💳 *Método de pago:* $metodoTexto\n" .
                        "💰 *Total:* \$$totalFinal MXN\n\n" .
                        "📄 *En unos segundos recibirás tu ticket en PDF adjunto aquí mismo.*\n" .
                        "Te esperamos puntual en tu horario reservado. Escribe *Menu* para volver.";

            programarNotificacion(
                $telefono,
                "📅🔔 *Confirmación de Cita #$pedidoId en $nombreNegocio*\n📍 Ubicación: *$ubicacionNeg*\n\nTu horario quedó apartado sin empalmes. ¡Te esperamos!",
                10
            );
        } elseif ($tipoPedido === 'tienda') {
            $msgFinal = "🎉 *¡Pedido #$pedidoId confirmado!*\n\n" .
                        "🏪 *Tienda:* $nombreNegocio (📍 $ubicacionNeg)\n" .
                        "$lineasTicket\n" .
                        "🚚 *Entrega:* Recoger en tienda\n" .
                        "💳 *Método de pago:* $metodoTexto\n" .
                        "💰 *Total:* \$$totalFinal MXN\n\n" .
                        "📄 *En unos segundos recibirás tu ticket en PDF adjunto aquí mismo.*\n" .
                        "⏳ *Tu compra estará lista en 5 minutos.*\n\n" .
                        "Escribe *Menu* si deseas hacer otro pedido.";

            programarNotificacion(
                $telefono,
                "✅🔔 *¡Pedido #$pedidoId listo para recoger en tienda!*\n\nTu compra en *$nombreNegocio* (📍 $ubicacionNeg) ya está preparada y esperándote en mostrador. 🛍️",
                10
            );
        } else {
            $msgFinal = "🎉 *¡Pedido #$pedidoId confirmado!*\n\n" .
                        "🏪 *Tienda:* $nombreNegocio\n" .
                        "$lineasTicket" .
                        "🛵 *Costo de envío ({$distKm} km):* \$$costoEnvio MXN\n" .
                        "⏱️ *Tiempo estimado de llegada:* {$tiempoEst} minutos\n" .
                        "💳 *Método de pago:* $metodoTexto\n" .
                        "💰 *Total a pagar:* \$$totalFinal MXN\n\n" .
                        "📄 *En unos segundos recibirás tu ticket en PDF adjunto aquí mismo.*\n" .
                        "Escribe *Menu* si deseas hacer otro pedido.";

            programarNotificacion(
                $telefono,
                "🛵🔔 *¡Tu pedido #$pedidoId de $nombreNegocio va en camino!*\n\nTiempo estimado de llegada a tu ubicación: *{$tiempoEst} minutos*.",
                10
            );
        }

        $sesion = ['paso' => 'ELIGIENDO_CATEGORIA', 'carrito' => []];
        file_put_contents($archivoSesion, json_encode($sesion));

        responderTwiml($msgFinal);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        responderTwiml("❌ Error al registrar tu solicitud: " . $e->getMessage());
    }
}
?>