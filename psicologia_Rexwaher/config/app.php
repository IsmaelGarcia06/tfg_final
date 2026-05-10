<?php

// ==========================================
// CARGAR VARIABLES DE ENTORNO (.env)
// ==========================================

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// ==========================================
// CONFIGURACIÓN DE RUTAS (BASE URL)
// ==========================================

/*
 * Opción A: Detección Automática
 */
// $scriptName = $_SERVER['SCRIPT_NAME'];
// $scriptDir = dirname(dirname($scriptName)); 
// $basePath = str_replace('\\', '/', $scriptDir);
// if ($basePath === '/' || $basePath === '.') {
//     $basePath = '';
// }

/*
 * Opción B: Manual (Configurada según solicitud)
 * Ruta base fija para evitar problemas con proxies o reescrituras
 */
$basePath = '/practicas2026/psicologia_Rexwaher';

// Definir la Constante Global
define('BASE_URL', $basePath);

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Genera una URL absoluta o relativa correcta basada en la instalación.
 * Uso: url('/login') -> /practicas2026/psicologia_Rexwaher/login
 */
function url($path = '') {
    // Asegurar que el path empiece con / si no está vacío
    if (!empty($path) && $path[0] !== '/') {
        $path = '/' . $path;
    }
    return BASE_URL . $path;
}

/**
 * Redirige a una ruta interna correctamente
 */
function redirect($path) {
    header('Location: ' . url($path));
    exit;
}