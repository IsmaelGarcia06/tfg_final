<?php

namespace Src\Controllers;

use Src\Services\AuditLogger;
use Src\Services\PasswordPolicy;

class PasswordResetController extends BaseController {

    // 1. Mostrar formulario de solicitud
    public function showRequestForm() {
        require __DIR__ . '/../Views/auth/forgot_password.php';
    }

    // 2. Procesar solicitud
    public function sendResetLink() {
        $email = $_POST['email'] ?? '';
        
        // Mensaje genérico siempre
        $message = "Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            require __DIR__ . '/../Views/auth/forgot_password.php';
            return;
        }

        // Verificar si existe (sin revelar)
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email AND active = 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generar Token Criptográfico
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', strtotime('+30 minutes')); // 30 min validez

            // Guardar Hash
            $stmt = $this->db->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (:email, :hash, :exp)");
            $stmt->execute(['email' => $email, 'hash' => $tokenHash, 'exp' => $expires]);

            // Simular envío de email (En producción usar PHPMailer)
            // El link sería: https://dominio.com/password/reset?token=$token&email=$email
            AuditLogger::log('password_reset_requested', $user['id'], ['email' => $email]);
            
            // Para pruebas locales, mostramos el link (QUITAR EN PRODUCCIÓN)
            $debugLink = "/psicologia_Rexwaher/password/reset?token=$token&email=$email";
            $message .= "<br><small>Debug: <a href='$debugLink'>Link Reset</a></small>";
        }

        require __DIR__ . '/../Views/auth/forgot_password.php';
    }

    // 3. Mostrar formulario de cambio
    public function showResetForm() {
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';
        require __DIR__ . '/../Views/auth/reset_password.php';
    }

    // 4. Procesar cambio
    public function resetPassword() {
        $token = $_POST['token'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Validar Token
        $tokenHash = hash('sha256', $token);
        $stmt = $this->db->prepare("SELECT * FROM password_resets WHERE email = :email AND token_hash = :hash AND expires_at > NOW()");
        $stmt->execute(['email' => $email, 'hash' => $tokenHash]);
        $resetRequest = $stmt->fetch();

        if (!$resetRequest) {
            $error = "El enlace es inválido o ha expirado.";
            require __DIR__ . '/../Views/auth/reset_password.php';
            return;
        }

        // Validar Política
        $policy = PasswordPolicy::validate($password);
        if (!$policy['valid']) {
            $error = $policy['message'];
            require __DIR__ . '/../Views/auth/reset_password.php';
            return;
        }

        // Cambiar Contraseña
        $newHash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
        $stmt->execute(['hash' => $newHash, 'email' => $email]);

        // Invalidar token (Un solo uso)
        $this->db->prepare("DELETE FROM password_resets WHERE email = :email")->execute(['email' => $email]);

        // Log y Notificación
        $user = $this->db->query("SELECT id FROM users WHERE email = '$email'")->fetch();
        AuditLogger::log('password_reset_success', $user['id']);
        
        // Aquí se enviaría email al usuario: "Tu contraseña ha sido cambiada."

        header('Location: /psicologia_Rexwaher/login?msg=password_reset');
    }
}
