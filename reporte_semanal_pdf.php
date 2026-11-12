<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'dompdf' . DIRECTORY_SEPARATOR . 'autoload.inc.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'conexion.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ============================ CONFIGURACIÓN ============================
const ESTADOS_VALIDOS    = ['confirmado', 'entregado']; // pedidos que cuentan como venta
const UMBRAL_MERCADO     = 0.15; // 15% arriba/abajo del precio de mercado = alerta
const DIAS_COBERTURA_MIN = 7;    // stock que alcanza para menos de 7 días = alerta
const STOCK_BAJO_FIJO    = 5;    // si no hubo ventas, avisa si quedan <= 5 pzas
const CARPETA_REPORTES   = __DIR__ . '/reportes';
// ========================================================================


/* ------------------------------ UTILIDADES ------------------------------ */

function money(float $n): string
{
    return '$' . number_format($n, 2);
}

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Convierte *texto* (negritas de WhatsApp) a <b>texto</b> seguro para el PDF. */
function textoPdf(string $s): string
{
    return preg_replace('/\*(.+?)\*/', '<b>$1</b>', h($s));
}

function textoPlano(string $s): string
{
    return str_replace('*', '', $s);
}

function fechaLarga(DateTime $d): string
{
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio',
              'agosto','septiembre','octubre','noviembre','diciembre'];
    return $d->format('j') . ' de ' . $meses[(int)$d->format('n') - 1];
}

/** Devuelve [lunes de la semana pasada 00:00, lunes de esta semana 00:00). */
function rangoSemanaAnterior(?DateTime $hoy = null): array
{
    $hoy = $hoy ? clone $hoy : new DateTime('now');
    $lunesActual = (clone $hoy)->modify('monday this week')->setTime(0, 0, 0);
    $lunesPasado = (clone $lunesActual)->modify('-7 days');
    return [$lunesPasado, $lunesActual];
}

/** Deja solo dígitos y agrega lada de México (52) si vienen 10 dígitos. */
function telefonoWhatsapp(?string $tel): ?string
{
    $d = preg_replace('/\D+/', '', (string)$tel);
    if ($d === '') return null;
    if (strlen($d) === 10) return '52' . $d;
    return $d;
}


/* ------------------------------ DATOS ------------------------------ */

function listaEstados(): string
{
    return "'" . implode("','", ESTADOS_VALIDOS) . "'"; // constantes, no input del usuario
}

/** Totales de ingreso/pedidos de un periodo (para comparar con la semana previa). */
function totalesPeriodo(PDO $conn, int $negocioId, DateTime $ini, DateTime $fin): array
{
    $estados = listaEstados();
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT pe.id_pedidos)                AS pedidos,
               COALESCE(SUM(d.cantidad * d.precio_unitario), 0) AS ingreso
        FROM pedidos pe
        LEFT JOIN detalles_pedido d ON d.pedido_id = pe.id_pedidos
        WHERE pe.negocio_id = :neg AND pe.creado_en >= :ini AND pe.creado_en < :fin
          AND pe.estado IN ($estados)
    ");
    $stmt->execute([
        ':neg' => $negocioId,
        ':ini' => $ini->format('Y-m-d H:i:s'),
        ':fin' => $fin->format('Y-m-d H:i:s'),
    ]);
    $r = $stmt->fetch();
    return ['pedidos' => (int)$r['pedidos'], 'ingreso' => (float)$r['ingreso']];
}

/** Alertas de UN producto. prioridad 1 = más urgente. */
function analizarProducto(array $p): array
{
    $alertas = [];
    $nombre  = $p['nombre'];
    $precio  = (float)$p['precio'];
    $costo   = (float)$p['costo'];
    $mercado = (float)$p['precio_mercado'];
    $stock   = (int)$p['stock'];
    $unid    = (int)$p['unidades'];

    // 1) Vendiendo por debajo del costo
    if ($costo > 0 && $precio < $costo) {
        $perdida = ($costo - $precio) * $unid;
        $alertas[] = [
            'prioridad' => 1, 'tipo' => 'precio_bajo_costo', 'producto' => $nombre,
            'texto' => "Estás vendiendo *{$nombre}* a " . money($precio) .
                       " y te cuesta " . money($costo) . ". Pierdes dinero en cada venta" .
                       ($unid > 0 ? " (≈" . money($perdida) . " esta semana)" : "") .
                       ". Sube el precio.",
        ];
    }

    // 2) Stock
    $ventasDia = $unid / 7;
    $cobertura = $ventasDia > 0 ? $stock / $ventasDia : null;

    if ($stock <= 0 && $unid > 0) {
        $alertas[] = [
            'prioridad' => 1, 'tipo' => 'sin_stock', 'producto' => $nombre,
            'texto' => "Se agotó *{$nombre}* y la semana pasada vendiste {$unid} pzas. Resurte hoy.",
        ];
    } elseif ($stock > 0 && (
        ($cobertura !== null && $cobertura < DIAS_COBERTURA_MIN) ||
        ($cobertura === null && $stock <= STOCK_BAJO_FIJO)
    )) {
        $detalle = $cobertura !== null
            ? "alcanza para ~" . max(1, (int)floor($cobertura)) . " días"
            : "está muy bajo";
        $alertas[] = [
            'prioridad' => 2, 'tipo' => 'stock_bajo', 'producto' => $nombre,
            'texto' => "Te queda poco *{$nombre}* ({$stock} pzas, {$detalle}). Resurte pronto.",
        ];
    }

    // 3) Precio vs mercado
    if ($mercado > 0) {
        $diff = ($precio - $mercado) / $mercado;
        if ($diff <= -UMBRAL_MERCADO) {
            $alertas[] = [
                'prioridad' => 3, 'tipo' => 'precio_bajo_mercado', 'producto' => $nombre,
                'texto' => "*{$nombre}* lo vendes a " . money($precio) . " y el mercado está en " .
                           money($mercado) . " (" . round(abs($diff) * 100) .
                           "% más barato). Puedes subirlo sin perder competitividad.",
            ];
        } elseif ($diff >= UMBRAL_MERCADO) {
            $alertas[] = [
                'prioridad' => 3, 'tipo' => 'precio_alto_mercado', 'producto' => $nombre,
                'texto' => "*{$nombre}* está " . round($diff * 100) . "% arriba del mercado (" .
                           money($precio) . " vs " . money($mercado) .
                           "). Si no rota, considera bajarlo.",
            ];
        }
    }
    return $alertas;
}

/** Reúne TODA la información del reporte (sin generar el PDF). */
function obtenerDatosReporte(PDO $conn, int $negocioId, ?DateTime $hoy = null): array
{
    [$inicio, $fin] = rangoSemanaAnterior($hoy);
    $ultimoDia = (clone $fin)->modify('-1 day');
    $estados   = listaEstados();

    // Negocio + dueño (teléfono para el bot)
    $stmt = $conn->prepare("
        SELECT n.id_negocios, n.nombre AS negocio, n.tipo, n.ubicacion, n.telefono_contacto,
               d.nombre AS dueno, d.telefono AS telefono_dueno
        FROM negocios n
        JOIN duenos d ON d.id_duenos = n.dueno_id
        WHERE n.id_negocios = :neg
    ");
    $stmt->execute([':neg' => $negocioId]);
    $negocio = $stmt->fetch();
    if (!$negocio) {
        throw new Exception("No existe el negocio con id {$negocioId}");
    }

    // Productos con sus ventas de la semana
    $stmt = $conn->prepare("
        SELECT p.id_productos, p.nombre, p.precio, p.costo, p.precio_mercado, p.stock,
               COALESCE(v.unidades, 0) AS unidades,
               COALESCE(v.ingreso, 0)  AS ingreso
        FROM productos p
        LEFT JOIN (
            SELECT d.producto_id,
                   SUM(d.cantidad)                     AS unidades,
                   SUM(d.cantidad * d.precio_unitario) AS ingreso
            FROM detalles_pedido d
            JOIN pedidos pe ON pe.id_pedidos = d.pedido_id
            WHERE pe.negocio_id = :neg_v
              AND pe.creado_en >= :ini AND pe.creado_en < :fin
              AND pe.estado IN ($estados)
            GROUP BY d.producto_id
        ) v ON v.producto_id = p.id_productos
        WHERE p.negocio_id = :neg_p
    ");
    $stmt->execute([
        ':neg_v' => $negocioId, ':neg_p' => $negocioId,
        ':ini' => $inicio->format('Y-m-d H:i:s'),
        ':fin' => $fin->format('Y-m-d H:i:s'),
    ]);
    $productos = $stmt->fetchAll();

    $actual = totalesPeriodo($conn, $negocioId, $inicio, $fin);
    $previa = totalesPeriodo($conn, $negocioId, (clone $inicio)->modify('-7 days'), $inicio);

    $costoTotal = 0.0; $unidades = 0;
    foreach ($productos as $p) {
        $costoTotal += (float)$p['costo'] * (int)$p['unidades'];
        $unidades   += (int)$p['unidades'];
    }
    $ingreso  = $actual['ingreso'];
    $ganancia = $ingreso - $costoTotal;
    $margen   = $ingreso > 0 ? ($ganancia / $ingreso) * 100 : 0;
    $variacion = $previa['ingreso'] > 0
        ? (($ingreso - $previa['ingreso']) / $previa['ingreso']) * 100
        : null;

    $vendidos = array_values(array_filter($productos, fn($p) => (int)$p['unidades'] > 0));
    usort($vendidos, fn($a, $b) => (float)$b['ingreso'] <=> (float)$a['ingreso']);
    $top = array_slice($vendidos, 0, 5);

    $alertas = [];
    foreach ($productos as $p) {
        $alertas = array_merge($alertas, analizarProducto($p));
    }
    usort($alertas, fn($a, $b) => $a['prioridad'] <=> $b['prioridad']);

    $recomendacion = $alertas
        ? $alertas[0]['texto']
        : 'Todo en orden: tu stock y tus precios están sanos. ¡Sigue así!';

    return [
        'negocio_id'        => $negocioId,
        'negocio'           => $negocio['negocio'],
        'tipo'              => $negocio['tipo'],
        'ubicacion'         => $negocio['ubicacion'],
        'dueno'             => $negocio['dueno'],
        'telefono_whatsapp' => telefonoWhatsapp($negocio['telefono_dueno'] ?: $negocio['telefono_contacto']),
        'inicio'            => $inicio,
        'fin'               => $ultimoDia,
        'ingreso'           => $ingreso,
        'ganancia'          => $ganancia,
        'margen'            => $margen,
        'pedidos'           => $actual['pedidos'],
        'unidades'          => $unidades,
        'variacion'         => $variacion,
        'top'               => $top,
        'alertas'           => $alertas,
        'recomendacion'     => $recomendacion,
    ];
}


/* ------------------------------ DISEÑO (HTML -> PDF) ------------------------------ */

function etiquetaAlerta(string $tipo): array
{
    // [etiqueta, color de fondo, color de texto]
    return match ($tipo) {
        'precio_bajo_costo'   => ['PÉRDIDA',      '#FFE0E6', '#C2185B'],
        'sin_stock'           => ['SIN STOCK',    '#FFE0E6', '#C2185B'],
        'stock_bajo'          => ['STOCK BAJO',   '#FFF1CC', '#A06A00'],
        'precio_bajo_mercado' => ['PRECIO BAJO',  '#E3F0FF', '#1B5FA8'],
        'precio_alto_mercado' => ['PRECIO ALTO',  '#E3F0FF', '#1B5FA8'],
        default               => ['AVISO',        '#EEEEEE', '#555555'],
    };
}

function renderHtmlReporte(array $r): string
{
    $periodo = fechaLarga($r['inicio']) . ' al ' . fechaLarga($r['fin']) . ' de ' . $r['fin']->format('Y');
    $maxIng  = 0.0;
    foreach ($r['top'] as $p) { $maxIng = max($maxIng, (float)$p['ingreso']); }
    $otras = array_slice($r['alertas'], 1, 6);

    // Variación vs semana previa
    if ($r['variacion'] === null) {
        $varTxt = 'Sin datos previos'; $varColor = '#888888';
    } elseif ($r['variacion'] >= 0) {
        $varTxt = '▲ ' . number_format($r['variacion'], 1) . '% vs semana previa'; $varColor = '#1E9E5A';
    } else {
        $varTxt = '▼ ' . number_format(abs($r['variacion']), 1) . '% vs semana previa'; $varColor = '#D6336C';
    }

    ob_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #1F2340; font-size: 11px; margin: 0; }
    .wrap { padding: 0 38px; }
    .header { background: #1F2340; color: #fff; padding: 30px 38px 26px 38px; }
    .brand { font-size: 10px; letter-spacing: 3px; color: #FFB400; font-weight: bold; }
    .title { font-size: 25px; font-weight: bold; margin: 6px 0 4px 0; }
    .sub { font-size: 11px; color: #C9CCE6; }
    .stripe { height: 6px; background: #FF2A6D; }
    .hola { font-size: 13px; margin: 22px 0 14px 0; color: #444A70; }
    table.kpis { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
    table.kpis td { width: 25%; background: #F6F7FC; border: 1px solid #E3E6F4; border-radius: 10px; padding: 12px 12px; vertical-align: top; }
    .kpi-label { font-size: 8.5px; letter-spacing: 1px; color: #7A80A8; font-weight: bold; }
    .kpi-val { font-size: 19px; font-weight: bold; margin-top: 5px; color: #1F2340; }
    .kpi-sub { font-size: 8.5px; margin-top: 4px; }
    .reco { background: #FF2A6D; color: #fff; border-radius: 12px; padding: 18px 20px; margin: 20px 0 6px 0; border: 2px solid #1F2340; }
    .reco-tag { font-size: 9px; letter-spacing: 2px; font-weight: bold; color: #FFE3EC; }
    .reco-txt { font-size: 14px; font-weight: bold; margin-top: 7px; line-height: 1.45; }
    h2 { font-size: 13px; margin: 24px 0 10px 0; color: #1F2340; }
    h2 span { color: #FF2A6D; }
    table.top { width: 100%; border-collapse: collapse; }
    table.top td { padding: 7px 4px; border-bottom: 1px solid #EDEFF8; vertical-align: middle; }
    .rank { width: 22px; height: 22px; background: #FFB400; color: #1F2340; border-radius: 11px; text-align: center; font-weight: bold; font-size: 10px; line-height: 22px; }
    .bar-bg { background: #EDEFF8; height: 8px; border-radius: 4px; }
    .bar { background: #FF2A6D; height: 8px; border-radius: 4px; }
    .num { text-align: right; font-weight: bold; }
    .muted { color: #7A80A8; font-size: 9px; }
    .alerta { border: 1px solid #E3E6F4; border-radius: 9px; padding: 9px 12px; margin-bottom: 7px; }
    .badge { font-size: 8px; font-weight: bold; padding: 2px 7px; border-radius: 8px; letter-spacing: 1px; }
    .ok { background: #E6F7EE; border: 1px solid #BFE8D0; color: #1E7A4A; border-radius: 9px; padding: 12px 14px; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; background: #F6F7FC; border-top: 1px solid #E3E6F4; padding: 10px 38px; font-size: 8.5px; color: #7A80A8; }
</style>
</head>
<body>

<div class="header">
    <div class="brand">CHANGARRO INTELIGENTE</div>
    <div class="title">Resumen semanal de ventas</div>
    <div class="sub"><?= h($r['negocio']) ?> &nbsp;·&nbsp; <?= h($periodo) ?></div>
</div>
<div class="stripe"></div>

<div class="wrap">
    <div class="hola">Hola <b><?= h($r['dueno']) ?></b>, así le fue a tu negocio la semana pasada.</div>

    <table class="kpis">
        <tr>
            <td>
                <div class="kpi-label">VENTAS</div>
                <div class="kpi-val"><?= money($r['ingreso']) ?></div>
                <div class="kpi-sub" style="color:<?= $varColor ?>"><?= h($varTxt) ?></div>
            </td>
            <td>
                <div class="kpi-label">GANANCIA ESTIMADA</div>
                <div class="kpi-val"><?= money($r['ganancia']) ?></div>
                <div class="kpi-sub" style="color:#7A80A8">Margen <?= number_format($r['margen'], 1) ?>%</div>
            </td>
            <td>
                <div class="kpi-label">PEDIDOS</div>
                <div class="kpi-val"><?= (int)$r['pedidos'] ?></div>
                <div class="kpi-sub" style="color:#7A80A8">
                    <?= $r['pedidos'] > 0 ? 'Ticket prom. ' . money($r['ingreso'] / $r['pedidos']) : '—' ?>
                </div>
            </td>
            <td>
                <div class="kpi-label">PIEZAS VENDIDAS</div>
                <div class="kpi-val"><?= (int)$r['unidades'] ?></div>
                <div class="kpi-sub" style="color:#7A80A8">en la semana</div>
            </td>
        </tr>
    </table>

    <div class="reco">
        <div class="reco-tag">LA RECOMENDACIÓN DE LA SEMANA</div>
        <div class="reco-txt"><?= textoPdf($r['recomendacion']) ?></div>
    </div>

    <h2><span>■</span> Lo más vendido</h2>
    <?php if ($r['top']): ?>
    <table class="top">
        <?php foreach ($r['top'] as $i => $p):
            $pct = $maxIng > 0 ? max(4, round(((float)$p['ingreso'] / $maxIng) * 100)) : 0; ?>
        <tr>
            <td style="width:30px"><div class="rank"><?= $i + 1 ?></div></td>
            <td style="width:150px"><b><?= h($p['nombre']) ?></b><br><span class="muted"><?= (int)$p['unidades'] ?> pzas · stock <?= (int)$p['stock'] ?></span></td>
            <td><div class="bar-bg"><div class="bar" style="width:<?= $pct ?>%"></div></div></td>
            <td class="num" style="width:90px"><?= money((float)$p['ingreso']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <div class="muted">No se registraron ventas la semana pasada.</div>
    <?php endif; ?>

    <h2><span>■</span> Otras cosas a revisar</h2>
    <?php if ($otras): foreach ($otras as $a):
        [$etq, $bg, $fg] = etiquetaAlerta($a['tipo']); ?>
        <div class="alerta">
            <span class="badge" style="background:<?= $bg ?>;color:<?= $fg ?>"><?= $etq ?></span>
            &nbsp; <?= textoPdf($a['texto']) ?>
        </div>
    <?php endforeach; else: ?>
        <div class="ok">✔ No hay más alertas. Tu inventario y tus precios están en orden.</div>
    <?php endif; ?>
</div>

<div class="footer">
    Generado el <?= date('d/m/Y H:i') ?> &nbsp;·&nbsp; Changarro Inteligente
    <?= $r['ubicacion'] ? ' &nbsp;·&nbsp; ' . h($r['ubicacion']) : '' ?>
</div>

</body>
</html>
<?php
    return ob_get_clean();
}


/* ------------------------------ FUNCIÓN PRINCIPAL ------------------------------ */

/**
 * Genera el PDF del reporte semanal.
 *
 * @param PDO           $conn       Conexión (Conexion::getConnection()).
 * @param int           $negocioId  id del negocio (negocios.id_negocios).
 * @param bool          $guardar    true = también lo guarda en /reportes.
 * @param DateTime|null $hoy        Opcional, para pruebas.
 */
function generarPdfReporteSemanal(PDO $conn, int $negocioId, bool $guardar = false, ?DateTime $hoy = null): array
{
    $datos = obtenerDatosReporte($conn, $negocioId, $hoy);
    $html  = renderHtmlReporte($datos);

    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', false);
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    $pdf = $dompdf->output();

    $nombreArchivo = 'reporte_semanal_' . $datos['inicio']->format('Y-m-d') . "_negocio{$negocioId}.pdf";
    $ruta = null;
    if ($guardar) {
        if (!is_dir(CARPETA_REPORTES)) {
            mkdir(CARPETA_REPORTES, 0775, true);
        }
        $ruta = CARPETA_REPORTES . '/' . $nombreArchivo;
        file_put_contents($ruta, $pdf);
    }

    $mensaje = "📊 Hola {$datos['dueno']}, aquí está tu resumen semanal de {$datos['negocio']}.\n" .
               "💰 Vendiste " . money($datos['ingreso']) . " la semana pasada.\n" .
               "💡 " . textoPlano($datos['recomendacion']);

    return [
        'negocio_id'        => $negocioId,
        'telefono_whatsapp' => $datos['telefono_whatsapp'],
        'nombre_archivo'    => $nombreArchivo,
        'ruta'              => $ruta,
        'pdf'               => $pdf,
        'mensaje'           => $mensaje,
        'recomendacion'     => textoPlano($datos['recomendacion']),
        'periodo'           => [
            'inicio' => $datos['inicio']->format('Y-m-d'),
            'fin'    => $datos['fin']->format('Y-m-d'),
        ],
    ];
}


/* ===================== ENDPOINT OPCIONAL (acceso directo por HTTP) ===================== */
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    try {
        $negocioId = isset($_GET['negocio_id']) ? (int)$_GET['negocio_id'] : 0;
        if ($negocioId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Falta el parámetro negocio_id'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $conn = (new Conexion())->getConnection();
        $guardar = isset($_GET['guardar']);
        $r = generarPdfReporteSemanal($conn, $negocioId, $guardar);

        if (($_GET['formato'] ?? '') === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'negocio_id'        => $r['negocio_id'],
                'telefono_whatsapp' => $r['telefono_whatsapp'],
                'nombre_archivo'    => $r['nombre_archivo'],
                'ruta'              => $r['ruta'],
                'mensaje'           => $r['mensaje'],
                'recomendacion'     => $r['recomendacion'],
                'periodo'           => $r['periodo'],
                'pdf_base64'        => base64_encode($r['pdf']),
            ], JSON_UNESCAPED_UNICODE);
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $r['nombre_archivo'] . '"');
            echo $r['pdf'];
        }
    } catch (Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}
