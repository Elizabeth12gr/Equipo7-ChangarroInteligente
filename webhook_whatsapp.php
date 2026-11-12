<?php
// webhook_whatsapp.php — URL que se registra en Meta (WhatsApp Cloud API).
// Debe ser accesible por HTTPS desde internet (ver README_AGENTE.md).
require_once __DIR__ . '/agente/util.php';
require_once __DIR__ . '/agente/Agente.php';

$cfg = require __DIR__ . '/config_agente.php';

// ---- 1) Verificación inicial del webhook (GET) -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $modo  = $_GET['hub_mode']         ?? $_GET['hub.mode']         ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $reto  = $_GET['hub_challenge']    ?? $_GET['hub.challenge']    ?? '';
    if ($modo === 'subscribe' && hash_equals($cfg['verify_token'], (string)$token)) {
        header('Content-Type: text/plain');
        echo $reto;
    } else {
        http_response_code(403);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// ---- 2) Validar firma de Meta (X-Hub-Signature-256) --------------------
$crudo = file_get_contents('php://input');
if ($cfg['app_secret'] !== '') {
    $esperada = 'sha256=' . hash_hmac('sha256', $crudo, $cfg['app_secret']);
    $recibida = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if (!hash_equals($esperada, $recibida)) {
        agente_log('Firma inválida; petición rechazada.');
        http_response_code(403);
        exit;
    }
} elseif (!$cfg['permitir_sin_firma']) {
    agente_log('Falta app_secret en config_agente; petición rechazada.');
    http_response_code(500);
    exit;
}

$datos = json_decode($crudo, true);
if (!is_array($datos)) {
    http_response_code(400);
    exit;
}

// ---- 3) Responder 200 de inmediato (Meta reintenta si tardamos) --------
ignore_user_abort(true);
set_time_limit(120);
ob_start();
echo 'OK';
header('Connection: close');
header('Content-Length: ' . ob_get_length());
ob_end_flush();
@flush();
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// ---- 4) Procesar mensajes ----------------------------------------------
try {
    $agente = new Agente($cfg);
    $wa     = new WhatsAppClient($cfg);

    foreach (($datos['entry'] ?? []) as $entry) {
        foreach (($entry['changes'] ?? []) as $cambio) {
            $v = $cambio['value'] ?? [];
            $phoneId = (string)($v['metadata']['phone_number_id'] ?? '');
            $nombre  = (string)($v['contacts'][0]['profile']['name'] ?? '');
            if ($phoneId === '') continue;

            foreach (($v['messages'] ?? []) as $m) {
                $de   = (string)($m['from'] ?? '');
                $id   = (string)($m['id'] ?? '');
                $tipo = (string)($m['type'] ?? '');
                if ($de === '' || $id === '') continue;

                if ($tipo === 'text') {
                    $texto = (string)($m['text']['body'] ?? '');
                } elseif ($tipo === 'button') {
                    $texto = (string)($m['button']['text'] ?? '');
                } elseif ($tipo === 'interactive') {
                    $texto = (string)($m['interactive']['button_reply']['title']
                          ?? $m['interactive']['list_reply']['title'] ?? '');
                } else {
                    // Audio, imagen, etc.: aún no soportados.
                    $wa->enviarTexto($phoneId, $de, 'Por ahora solo puedo leer mensajes de texto. ¿Me escribes lo que necesitas? 😊');
                    continue;
                }
                $agente->procesar($phoneId, $de, $nombre, $texto, $id);
            }
        }
    }
} catch (Throwable $e) {
    agente_log('webhook: ' . $e->getMessage());
}
