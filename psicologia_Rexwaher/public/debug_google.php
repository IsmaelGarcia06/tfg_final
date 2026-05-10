<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

// Permitir a admin y profesionales
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager', 'professional'])) {
    die("Acceso denegado. Debes estar logueado como usuario autorizado.");
}

$db = \getDBConnection();

// Obtener usuario actual
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    die("Usuario no autenticado");
}

// Obtener datos de Google del usuario
$stmt = $db->prepare("SELECT id, name, email, google_refresh_token, google_access_token, google_token_expires_at, google_calendar_id FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    die("Usuario no encontrado");
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Debug Google Calendar</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; }
        .info { background: white; padding: 15px; border-left: 4px solid #0066cc; margin: 10px 0; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .ok { border-left-color: #4caf50; background: #f1f8f6; }
        .error { border-left-color: #f44336; background: #fef5f5; }
        .warning { border-left-color: #ff9800; background: #fff8f0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table th { background: #0066cc; color: white; padding: 12px; text-align: left; }
        table td { padding: 12px; border-bottom: 1px solid #eee; }
        table tr:hover { background: #f9f9f9; }
        .btn { display: inline-block; padding: 10px 20px; background: #0066cc; color: white; text-decoration: none; border-radius: 4px; margin: 5px 0; }
        .btn:hover { background: #0052a3; }
        h1 { color: #333; }
        h2 { color: #0066cc; margin-top: 30px; }
        hr { border: none; border-top: 1px solid #ddd; margin: 20px 0; }
        .status-ok { display: inline-block; padding: 5px 10px; background: #4caf50; color: white; border-radius: 3px; }
        .status-error { display: inline-block; padding: 5px 10px; background: #f44336; color: white; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Debug: Google Calendar Sync</h1>

    <div class="info ok">
        <strong>✅ Usuario Autenticado:</strong> <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>) - Rol: <?= $_SESSION['user_role'] ?>
    </div>

    <h2>1️⃣ Estado de Conexión a Google</h2>

    <div class="info <?= !empty($user['google_refresh_token']) ? 'ok' : 'error' ?>">
        <strong>Refresh Token:</strong>
        <?= !empty($user['google_refresh_token']) ? '<span class="status-ok">✅ Presente</span>' : '<span class="status-error">❌ NO CONFIGURADO</span>' ?>
        <?php if (!empty($user['google_refresh_token'])): ?>
            <br><small>Primeros 30 caracteres: <code><?= substr($user['google_refresh_token'], 0, 30) ?>...</code></small>
        <?php endif; ?>
    </div>

    <div class="info <?= !empty($user['google_access_token']) ? 'ok' : 'error' ?>">
        <strong>Access Token:</strong>
        <?= !empty($user['google_access_token']) ? '<span class="status-ok">✅ Presente</span>' : '<span class="status-error">❌ NO CONFIGURADO</span>' ?>
        <?php if (!empty($user['google_access_token'])): ?>
            <br><small>Tamaño: <?= strlen($user['google_access_token']) ?> caracteres</small><br>
            <small>Primeros 100 caracteres:</small>
            <pre><?= htmlspecialchars(substr($user['google_access_token'], 0, 100)) ?>...</pre>
        <?php endif; ?>
    </div>

    <div class="info">
        <strong>Token Expira:</strong>
        <?php if ($user['google_token_expires_at']): ?>
            <code><?= $user['google_token_expires_at'] ?></code>
            <?php
            $expires = new DateTime($user['google_token_expires_at']);
            $now = new DateTime();
            $diff = $expires->diff($now);
            echo $expires > $now ?
                '<br><span class="status-ok">✅ Válido todavía</span>' :
                '<br><span class="status-error">❌ EXPIRADO - Necesita refrescar</span>';
            ?>
        <?php else: ?>
            <span class="status-error">❌ No configurado</span>
        <?php endif; ?>
    </div>

    <div class="info">
        <strong>Calendar ID:</strong>
        <code><?= htmlspecialchars($user['google_calendar_id'] ?? 'primary') ?></code>
    </div>

    <h2>2️⃣ Credenciales de Google OAuth en Servidor</h2>

    <div class="info <?= !empty(getenv('GOOGLE_CLIENT_ID')) ? 'ok' : 'error' ?>">
        <strong>GOOGLE_CLIENT_ID:</strong>
        <?php if (!empty(getenv('GOOGLE_CLIENT_ID'))): ?>
            <span class="status-ok">✅ Configurado</span><br>
            <code><?= getenv('GOOGLE_CLIENT_ID') ?></code>
        <?php else: ?>
            <span class="status-error">❌ NO CONFIGURADO</span>
        <?php endif; ?>
    </div>

    <div class="info <?= !empty(getenv('GOOGLE_CLIENT_SECRET')) ? 'ok' : 'error' ?>">
        <strong>GOOGLE_CLIENT_SECRET:</strong>
        <?php if (!empty(getenv('GOOGLE_CLIENT_SECRET'))): ?>
            <span class="status-ok">✅ Configurado</span><br>
            <small>(No se muestra por seguridad)</small>
        <?php else: ?>
            <span class="status-error">❌ NO CONFIGURADO</span>
        <?php endif; ?>
    </div>

    <div class="info <?= file_exists(__DIR__ . '/../config/.env') ? 'ok' : 'error' ?>">
        <strong>Archivo .env:</strong>
        <?= file_exists(__DIR__ . '/../config/.env') ? '<span class="status-ok">✅ Existe</span>' : '<span class="status-error">❌ NO ENCONTRADO</span>' ?>
        <br><small>Ubicación: <code>/config/.env</code></small>
    </div>

    <h2>3️⃣ Diagnóstico y Recomendaciones</h2>

    <?php
    $issues = [];
    $recommendations = [];

    if (empty($user['google_refresh_token'])) {
        $issues[] = "No hay token de refresco de Google. El usuario no se conectó correctamente a Google Calendar.";
        $recommendations[] = "Ir a Mi Perfil → Integraciones → Conectar Google Calendar y autenticarse.";
    }

    if (empty(getenv('GOOGLE_CLIENT_ID')) || empty(getenv('GOOGLE_CLIENT_SECRET'))) {
        $issues[] = "Las credenciales de Google OAuth no están configuradas en el servidor.";
        $recommendations[] = "Verificar que /config/.env tenga GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET correctos.";
    }

    if (empty($issues)):
        ?>
        <div class="info ok">
            <strong>✅ TODO ESTÁ BIEN</strong><br>
            Todos los requisitos están configurados correctamente. Las citas deberían sincronizarse con Google Calendar.
        </div>
    <?php else: ?>
        <?php foreach ($issues as $issue): ?>
            <div class="info error">
                <strong>❌ PROBLEMA:</strong> <?= $issue ?>
            </div>
        <?php endforeach; ?>

        <div class="info warning">
            <strong>💡 RECOMENDACIONES:</strong>
            <ol>
                <?php foreach ($recommendations as $rec): ?>
                    <li><?= $rec ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    <?php endif; ?>

    <h2>4️⃣ Últimas Citas Creadas</h2>

    <?php
    $sessions = $db->query("SELECT s.id, s.start_time, s.status, s.google_event_id, p.name as patient_name 
                               FROM sessions s 
                               JOIN patients p ON s.patient_id = p.id 
                               WHERE s.professional_id = {$_SESSION['user_id']} 
                               ORDER BY s.created_at DESC 
                               LIMIT 10")->fetchAll();

    if (!empty($sessions)):
        ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Paciente</th>
                <th>Fecha/Hora</th>
                <th>Estado</th>
                <th>Google Sync</th>
            </tr>
            <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td><?= htmlspecialchars($s['patient_name']) ?></td>
                    <td><?= $s['start_time'] ?></td>
                    <td><?= ucfirst($s['status']) ?></td>
                    <td>
                        <?php if (!empty($s['google_event_id'])): ?>
                            <span class="status-ok">✅ Sincronizado</span><br>
                            <small><code><?= substr($s['google_event_id'], 0, 35) ?>...</code></small>
                        <?php else: ?>
                            <span class="status-error">❌ No sincronizado</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="info warning">
            <strong>ℹ️ Sin citas:</strong> No hay citas aún. Crea una para verificar que se sincronice con Google.
        </div>
    <?php endif; ?>

    <h2>5️⃣ Acciones Rápidas</h2>

    <?php if (empty($user['google_refresh_token'])): ?>
        <p>
            <a href="/psicologia_Rexwaher/auth/google" class="btn">👉 Conectar Google Calendar</a>
        </p>
        <p>Después de conectarte, vuelve aquí para verificar.</p>
    <?php else: ?>
        <p class="info ok">
            <strong>✅ Ya estás conectado a Google Calendar.</strong>
            Las nuevas citas deberían aparecer automáticamente.
        </p>
        <p>
            <a href="/psicologia_Rexwaher/calendar" class="btn">📅 Ir al Calendario</a>
        </p>
    <?php endif; ?>

</div>
</body>
</html>