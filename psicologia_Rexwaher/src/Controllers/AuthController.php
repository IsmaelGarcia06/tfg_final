<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php'; // Cargar helpers
use Src\Services\RateLimiter;
use Src\Services\Csrf;

class AuthController {
    
    public function showLogin() {
        if (isset($_SESSION['user_id'])) {
            redirect('/dashboard');
        }
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // OMITIMOS RateLimiter temporalmente en entorno de desarrollo para evitar bloqueos
        /*
        $limiter = new RateLimiter();
        if (!$limiter->check($ip, $email)['allowed']) {
            $error = "Demasiados intentos. Inténtalo más tarde.";
            require __DIR__ . '/../Views/auth/login.php';
            return;
        }
        */

        // Validación de Token CSRF
        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            $error = "Error de seguridad. Recarga la página.";
            require __DIR__ . '/../Views/auth/login.php';
            return;
        }

        $pdo = \getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND active = 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Hemos quitado ARGON2ID en el AuthController para evitar los fallos
        if ($user && password_verify($password, $user['password_hash'])) {
            // $limiter->clearAttempts($ip, $email);
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['created_at'] = time();
            
            $ip_parts = explode('.', $_SERVER['REMOTE_ADDR']);
            $ip_fingerprint = implode('.', array_slice($ip_parts, 0, 3));
            $_SESSION['fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $ip_fingerprint);

            if ($user['two_factor_enabled']) {
                $_SESSION['2fa_pending_user_id'] = $user['id'];
                redirect('/auth/2fa');
            }

            redirect('/dashboard');
        } else {
            // $limiter->logFailure($ip, $email);
            $error = "Credenciales incorrectas.";
            require __DIR__ . '/../Views/auth/login.php';
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }

        redirect('/login');
    }
}
