<?php
// config_agente.php
// Configuración del agente de WhatsApp.
// NO subas tus llaves a GitHub: crea el archivo config_agente.local.php (misma carpeta)
// que devuelva un arreglo con los valores que quieras sobrescribir. Ejemplo:
//
//   <?php return [
//       'anthropic_key' => 'sk-ant-...',
//       'wa_token'      => 'EAAG...',
//       'app_secret'    => '...',
//       'verify_token'  => 'una-frase-larga-que-tu-inventes',
//   ];

$config = [
    // --- WhatsApp Cloud API (Meta) ---
    'verify_token'   => getenv('WA_VERIFY_TOKEN')   ?: 'cambia-este-token',
    'app_secret'     => getenv('WA_APP_SECRET')     ?: '',   // valida que el webhook venga de Meta
    'wa_token'       => getenv('WA_ACCESS_TOKEN')   ?: '',   // token permanente (usuario del sistema)
    'graph_version'  => 'v21.0',
    'graph_base'     => 'https://graph.facebook.com',        // solo cambia en pruebas
    'permitir_sin_firma' => false,                            // true SOLO para pruebas locales

    // --- Claude (Anthropic API) ---
    'anthropic_key'  => getenv('ANTHROPIC_API_KEY') ?: '',
    'modelo'         => 'claude-sonnet-5-5',                  // más barato/rápido: 'claude-haiku-5-5'
    'max_tokens'     => 1024,
    'anthropic_url'  => 'https://api.anthropic.com/v1/messages', // solo cambia en pruebas

    // --- Comportamiento ---
    'historial_max'      => 20,   // mensajes previos que se envían al modelo
    'max_mensajes_hora'  => 40,   // límite por cliente para evitar abusos y gasto
    'max_pasos_herramientas' => 6,
    'max_dias_futuro'    => 90,
    'log'                => __DIR__ . '/agente/agente.log',
];

$local = __DIR__ . '/config_agente.local.php';
if (is_file($local)) {
    $config = array_merge($config, require $local);
}
return $config;
