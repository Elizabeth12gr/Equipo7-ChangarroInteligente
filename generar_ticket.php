<?php

// Usamos dirname() para evitar problemas de diagonales en Windows
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'dompdf' . DIRECTORY_SEPARATOR . 'autoload.inc.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'modelo' . DIRECTORY_SEPARATOR . 'detalle_pedido.php';

use Dompdf\Dompdf;

// Verificamos que llegue el ID del pedido
if (!isset($_GET['id_pedido'])) {
    die("Error: No se proporcionó el ID del pedido.");
}

$pedido_id = isset($_GET['id_pedido']) ? $_GET['id_pedido'] : 1;
$detalleModel = new DetallePedido();
// Llamamos a la función que TÚ programaste como Dev 2
$productos = $detalleModel->obtenerDetallesPorPedido($pedido_id);

// Armamos el HTML del ticket
// Armamos el HTML del ticket con un mejor diseño
// Armamos el HTML con diseño estricto de ticket térmico
$html = '
<style>
    @page {
        margin: 0; /* Quitamos los márgenes por defecto para aprovechar el ancho */
    }
    body {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 11px; /* Letra pequeña típica de ticket */
        color: #000;
        margin: 0;
        padding: 10px;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .bold { font-weight: bold; }
    
    .header h1 { 
        font-size: 16px; 
        margin: 0 0 5px 0; 
        text-transform: uppercase;
    }
    .header p { 
        margin: 2px 0; 
        font-size: 10px; 
    }
    
    .divider {
        border-top: 1px dashed #000;
        margin: 8px 0;
    }
    
    .info-box {
        width: 100%;
        margin-bottom: 5px;
        font-size: 10px;
    }
    .info-box td {
        padding: 1px;
        border: none;
    }
    
    .items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
        margin: 5px 0;
    }
    .items-table th {
        border-bottom: 1px dashed #000;
        padding-bottom: 4px;
        text-align: left;
    }
    .items-table td {
        padding: 4px 0;
        vertical-align: top;
    }
    
    .col-qty { width: 12%; }
    .col-name { width: 48%; }
    .col-price { width: 20%; text-align: right; }
    .col-total { width: 20%; text-align: right; }
    
    .total-container {
        width: 100%;
        text-align: right;
        margin-top: 5px;
        font-size: 14px;
    }
    
    .footer {
        text-align: center;
        margin-top: 15px;
        font-size: 10px;
    }
</style>

<body>
    <div class="header text-center">
        <h1>Mi Changarro</h1>
        <p>Sucursal Centro</p>
        <p>Heroica Veracruz, Ver.</p>
    </div>
    
    <table class="info-box">
        <tr>
            <td class="text-left">Ticket: #'.$pedido_id.'</td>
            <td class="text-right">'.date('d/m/Y H:i').'</td>
        </tr>
        <tr>
            <td class="text-left">Caja: 1</td>
            <td class="text-right">Atendió: Admin</td>
        </tr>
    </table>
    
    <div class="divider"></div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-qty">Cant</th>
                <th class="col-name">Artículo</th>
                <th class="col-price">P.Unit</th>
                <th class="col-total">Monto</th>
            </tr>
        </thead>
        <tbody>';

$total = 0;
foreach ($productos as $item) {
    $subtotal = $item['cantidad'] * $item['precio_unitario'];
    $total += $subtotal;
    $html .= '
            <tr>
                <td class="col-qty">'.$item['cantidad'].'x</td>
                <td class="col-name">'.$item['nombre'].'</td>
                <td class="col-price">$'.number_format($item['precio_unitario'], 2).'</td>
                <td class="col-total">$'.number_format($subtotal, 2).'</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="divider"></div>
    
    <div class="total-container bold">
        TOTAL: $'.number_format($total, 2).'
    </div>
    
    <div class="footer text-center">
        <p>¡Gracias por tu preferencia!</p>
        <p>*** COPIA DE CLIENTE ***</p>
    </div>
</body>';

// Instanciamos y generamos el PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);

// EL SECRETO PARA EL FORMATO TICKET TÉRMICO (80mm) EN UNA SOLA HOJA
// array(0, 0, ancho, alto). El ancho 226.77 equivale a 80mm aprox. 
// El alto lo ponemos exagerado (1000) para que no haga saltos de página.
$customPaper = array(0, 0, 226.77, 1000); 
$dompdf->setPaper($customPaper);

$dompdf->render();

// Forzamos la descarga o lo mostramos en el navegador
$dompdf->stream("ticket_".$pedido_id.".pdf", array("Attachment" => false));

?>