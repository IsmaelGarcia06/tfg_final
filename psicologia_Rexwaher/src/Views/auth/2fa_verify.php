<?php use Src\Services\Csrf; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación 2FA</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f0f2f5; margin: 0; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        input { width: 100%; padding: 0.75rem; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; text-align: center; font-size: 1.5rem; letter-spacing: 5px; }
        button { width: 100%; padding: 0.75rem; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <h2>Verificación de Seguridad</h2>
    <p>Ingresa el código de tu aplicación autenticadora.</p>

    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/auth/2fa/verify" method="POST">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">

        <input type="text" name="code" maxlength="6" required autofocus autocomplete="off">
        <button type="submit">Verificar</button>
    </form>
    <p style="margin-top: 1rem;"><a href="<?= BASE_URL ?>/login">Cancelar</a></p>
</div>
</body>
</html>