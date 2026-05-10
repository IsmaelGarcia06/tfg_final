<?php

namespace Src\Controllers;

use Src\Services\GoogleAuth;
use Src\Services\Csrf;

class TwoFactorController extends BaseController {

    public function setup() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $secret = GoogleAuth::createSecret();
        $qrUrl = GoogleAuth::getQrUrl($_SESSION['user_name'], $secret);
        $_SESSION['2fa_temp_secret'] = $secret;

        require __DIR__ . '/../Views/auth/2fa_setup.php';
    }

    public function enable() {
        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            $error = "Error de seguridad (CSRF).";
            require __DIR__ . '/../Views/auth/2fa_setup.php';
            return;
        }

        $code = $_POST['code'] ?? '';
        $secret = $_SESSION['2fa_temp_secret'] ?? null;

        if (!$secret || !GoogleAuth::verifyCode($secret, $code)) {
            $error = "Código incorrecto.";
            $qrUrl = GoogleAuth::getQrUrl($_SESSION['user_name'], $secret);
            require __DIR__ . '/../Views/auth/2fa_setup.php';
            return;
        }

        $stmt = $this->db->prepare("UPDATE users SET two_factor_secret = :secret, two_factor_enabled = 1 WHERE id = :id");
        $stmt->execute(['secret' => $secret, 'id' => $_SESSION['user_id']]);

        unset($_SESSION['2fa_temp_secret']);
        header('Location: ' . BASE_URL . '/dashboard?msg=2fa_enabled');
        exit;
    }

    public function showVerify() {
        if (!isset($_SESSION['2fa_pending_user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        require __DIR__ . '/../Views/auth/2fa_verify.php';
    }

    public function verify() {
        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            $error = "Sesión expirada.";
            require __DIR__ . '/../Views/auth/2fa_verify.php';
            return;
        }

        $userId = $_SESSION['2fa_pending_user_id'] ?? null;
        $code = $_POST['code'] ?? '';

        if (!$userId) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if ($user && GoogleAuth::verifyCode($user['two_factor_secret'], $code)) {

            // 1. Establecer sesión PRIMERO
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            // 2. Limpiar temporal
            unset($_SESSION['2fa_pending_user_id']);

            // 3. NO regeneramos ID aquí para evitar perder la cookie en algunos navegadores/configs
            // Ya se regeneró (o debió) en el paso 1 del login.
            // session_regenerate_id(true);

            // 4. Guardar y redirigir
            session_write_close();
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        } else {
            $error = "Código incorrecto.";
            require __DIR__ . '/../Views/auth/2fa_verify.php';
        }
    }

    public function disable() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $stmt = $this->db->prepare("UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);

        header('Location: ' . BASE_URL . '/profile?msg=2fa_disabled');
        exit;
    }
}