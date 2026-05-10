<?php

// 1. Cabeceras de Seguridad
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https://api.qrserver.com; font-src 'self' https://cdn.jsdelivr.net;");

require_once __DIR__ . '/../config/app.php'; 

// Cargar dependencias de Composer (incluyendo Google API)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// 2. Configuración de Cookies
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => 0,                
    'path' => BASE_URL ?: '/', 
    'domain' => '',                 
    'secure' => $isSecure,          
    'httponly' => true,             
    'samesite' => 'Lax'          
]);

session_start();

// Guardar nonce
$nonce = base64_encode(random_bytes(16));
define('CSP_NONCE', $nonce);

// 3. Control de Sesión
$max_inactivity = 1200; 
$max_session_life = 43200; 

$ip_parts = explode('.', $_SERVER['REMOTE_ADDR']);
$ip_fingerprint = implode('.', array_slice($ip_parts, 0, 3)); 
$fingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $ip_fingerprint);

if (isset($_SESSION['user_id'])) {
    $now = time();
    $baseRedirect = BASE_URL;

    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > $max_inactivity)) {
        session_unset(); session_destroy();
        header("Location: $baseRedirect/login?error=timeout"); exit;
    }

    if (isset($_SESSION['created_at']) && ($now - $_SESSION['created_at'] > $max_session_life)) {
        session_unset(); session_destroy();
        header("Location: $baseRedirect/login?error=expired"); exit;
    }

    if (isset($_SESSION['fingerprint']) && $_SESSION['fingerprint'] !== $fingerprint) {
        session_unset(); session_destroy();
        header("Location: $baseRedirect/login?error=security"); exit;
    }
}

$_SESSION['last_activity'] = time();
if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
    $_SESSION['fingerprint'] = $fingerprint;
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Src\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

require_once __DIR__ . '/../config/db.php';

use Src\Core\Router;
use Src\Controllers\AuthController;
use Src\Controllers\DashboardController;
use Src\Controllers\PatientViewController;
use Src\Controllers\CalendarViewController;
use Src\Controllers\ProfessionalsViewController;
use Src\Controllers\FinancialViewController;
use Src\Controllers\ConfigController;
use Src\Controllers\AdminController;
use Src\Controllers\CommissionsController;
use Src\Controllers\EmailController;
use Src\Controllers\IntegrationController;
use Src\Controllers\PasswordResetController;
use Src\Controllers\SecurityController;
use Src\Controllers\TwoFactorController;
use Src\Controllers\ProfileController;
use Src\Controllers\ItAdminController;
use Src\Controllers\Api\PatientController;
use Src\Controllers\Api\SessionController;
use Src\Controllers\Api\CommissionController;
use Src\Controllers\Api\NoteController;
use Src\Controllers\Api\PackController;
use Src\Controllers\Api\AdminApiController;
use Src\Controllers\Api\ServiceController; // IMPORTANTE: Agregado
use Src\Middlewares\AuthMiddleware;
use Src\Services\Logger;

// RUTA BASE FORZADA A LA ESTRUCTURA DESEADA
$basePath = BASE_URL;
$router = new Router($basePath);

// --- Rutas WEB ---

// Auth
$router->add('GET', '/login', [AuthController::class, 'showLogin']);
$router->add('POST', '/login', [AuthController::class, 'login']);
$router->add('POST', '/logout', [AuthController::class, 'logout']);

// 2FA
$router->add('GET', '/auth/2fa', [TwoFactorController::class, 'showVerify']);
$router->add('POST', '/auth/2fa/verify', [TwoFactorController::class, 'verify']);
$router->add('GET', '/auth/2fa/setup', [TwoFactorController::class, 'setup'], [AuthMiddleware::class]);
$router->add('POST', '/auth/2fa/enable', [TwoFactorController::class, 'enable'], [AuthMiddleware::class]);
$router->add('POST', '/auth/2fa/disable', [TwoFactorController::class, 'disable'], [AuthMiddleware::class]);

// Recuperación de Contraseña
$router->add('GET', '/password/forgot', [PasswordResetController::class, 'showRequestForm']);
$router->add('POST', '/password/email', [PasswordResetController::class, 'sendResetLink']);
$router->add('GET', '/password/reset', [PasswordResetController::class, 'showResetForm']);
$router->add('POST', '/password/reset', [PasswordResetController::class, 'resetPassword']);

// Dashboard
$router->add('GET', '/', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->add('GET', '/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Vistas Funcionales
$router->add('GET', '/patients', [PatientViewController::class, 'index'], [AuthMiddleware::class]);
$router->add('GET', '/patients/create', [PatientViewController::class, 'create'], [AuthMiddleware::class]);
$router->add('GET', '/patients/{id}/edit', [PatientViewController::class, 'edit'], [AuthMiddleware::class]);

$router->add('GET', '/calendar', [CalendarViewController::class, 'index'], [AuthMiddleware::class]);
$router->add('GET', '/professionals', [ProfessionalsViewController::class, 'index'], [AuthMiddleware::class]);
$router->add('GET', '/financial', [FinancialViewController::class, 'index'], [AuthMiddleware::class]);

// Configuración
$router->add('GET', '/config', [ConfigController::class, 'index'], [AuthMiddleware::class]);
$router->add('POST', '/config/profile', [ConfigController::class, 'updateProfile'], [AuthMiddleware::class]);
$router->add('POST', '/config/password', [ConfigController::class, 'changePassword'], [AuthMiddleware::class]);

// Mi Cuenta
$router->add('GET', '/profile', [ProfileController::class, 'index'], [AuthMiddleware::class]);
$router->add('POST', '/profile/update', [ProfileController::class, 'update'], [AuthMiddleware::class]);
$router->add('POST', '/profile/password', [ProfileController::class, 'changePassword'], [AuthMiddleware::class]);

// Administración
$router->add('GET', '/admin/users', [AdminController::class, 'usersIndex'], [AuthMiddleware::class]);
$router->add('GET', '/admin/services', [AdminController::class, 'servicesIndex'], [AuthMiddleware::class]);
$router->add('GET', '/admin/users/{id}/commissions', [AdminController::class, 'userCommissions'], [AuthMiddleware::class]);
$router->add('GET', '/admin/commissions', [CommissionsController::class, 'index'], [AuthMiddleware::class]);
$router->add('GET', '/admin/email', [EmailController::class, 'index'], [AuthMiddleware::class]);
$router->add('GET', '/admin/security', [SecurityController::class, 'index'], [AuthMiddleware::class]);

// IT Admin
$router->add('GET', '/it/dashboard', [ItAdminController::class, 'index'], [AuthMiddleware::class]);
$router->add('POST', '/api/it/settings', [ItAdminController::class, 'updateSettings'], [AuthMiddleware::class]);
$router->add('POST', '/api/it/logs/clear', [ItAdminController::class, 'clearLogs'], [AuthMiddleware::class]);
$router->add('POST', '/api/it/test-error', [ItAdminController::class, 'testError'], [AuthMiddleware::class]);

// Integraciones
$router->add('GET', '/auth/google', [IntegrationController::class, 'connectGoogle'], [AuthMiddleware::class]);
$router->add('GET', '/auth/google/callback', [IntegrationController::class, 'googleCallback'], [AuthMiddleware::class]);
$router->add('POST', '/auth/google/disconnect', [IntegrationController::class, 'disconnectGoogle'], [AuthMiddleware::class]);

// --- Rutas API ---
// Admin API
$router->add('POST', '/api/admin/users', [AdminApiController::class, 'createUser'], [AuthMiddleware::class]);
$router->add('POST', '/api/admin/services', [AdminApiController::class, 'createService'], [AuthMiddleware::class]);
$router->add('POST', '/api/admin/commissions', [AdminApiController::class, 'createCommissionType'], [AuthMiddleware::class]);
$router->add('POST', '/api/admin/commissions/assign', [AdminApiController::class, 'assignCommission'], [AuthMiddleware::class]);
$router->add('POST', '/api/admin/security/unblock', [SecurityController::class, 'unblock'], [AuthMiddleware::class]);

// Email API
$router->add('POST', '/api/email/templates', [EmailController::class, 'updateTemplate'], [AuthMiddleware::class]);
$router->add('POST', '/api/email/run', [EmailController::class, 'runAutomation'], [AuthMiddleware::class]);
$router->add('POST', '/api/email/templates/toggle', [EmailController::class, 'toggleTemplate'], [AuthMiddleware::class]);

// Pacientes
$router->add('GET', '/api/patients', [PatientController::class, 'index'], [AuthMiddleware::class]);
$router->add('POST', '/api/patients', [PatientController::class, 'store'], [AuthMiddleware::class]);
$router->add('GET', '/api/patients/{id}', [PatientController::class, 'show'], [AuthMiddleware::class]);
$router->add('PUT', '/api/patients/{id}', [PatientController::class, 'update'], [AuthMiddleware::class]);
$router->add('POST', '/api/patients/{id}/members', [PatientController::class, 'addMember'], [AuthMiddleware::class]);
$router->add('GET', '/api/patients/{id}/appointments', [PatientController::class, 'getAppointments'], [AuthMiddleware::class]);

// Bonos
$router->add('GET', '/api/patients/{id}/packs', [PackController::class, 'getActivePacks'], [AuthMiddleware::class]);
$router->add('GET', '/api/packs/available', [PackController::class, 'getAvailablePacks'], [AuthMiddleware::class]);
$router->add('POST', '/api/packs', [PackController::class, 'store'], [AuthMiddleware::class]);

// Servicios (Públicos para usuarios logueados)
$router->add('GET', '/api/services', [ServiceController::class, 'index'], [AuthMiddleware::class]);

// Notas y Documentos
$router->add('GET', '/api/patients/{id}/notes', [NoteController::class, 'index'], [AuthMiddleware::class]);
$router->add('POST', '/api/notes', [NoteController::class, 'store'], [AuthMiddleware::class]);
$router->add('GET', '/api/patients/{id}/documents', [NoteController::class, 'getDocuments'], [AuthMiddleware::class]);
$router->add('POST', '/api/documents', [NoteController::class, 'uploadDocument'], [AuthMiddleware::class]);

// Sesiones
$router->add('GET', '/api/sessions', [SessionController::class, 'index'], [AuthMiddleware::class]);
$router->add('POST', '/api/sessions', [SessionController::class, 'store'], [AuthMiddleware::class]);
$router->add('PUT', '/api/sessions/{id}', [SessionController::class, 'update'], [AuthMiddleware::class]);

// Comisiones
$router->add('PUT', '/api/professionals/{id}/commission', [CommissionController::class, 'update'], [AuthMiddleware::class]);
$router->add('GET', '/api/professionals/{id}/commission', [CommissionController::class, 'get'], [AuthMiddleware::class]);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
