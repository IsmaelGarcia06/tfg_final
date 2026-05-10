<?php

namespace Src\Middlewares;

class AuthMiddleware {
    public function handle() {
        if (!isset($_SESSION['user_id'])) {
            if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'auth_required', 'message' => 'Debe iniciar sesión.']);
                return false;
            }
            
            redirect('/login');
            exit;
        }
        return true;
    }
}
