<?php

namespace Src\Middlewares;

class AdminMiddleware {
    public function handle() {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            
            if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => 'access_denied', 'message' => 'Requiere permisos de Administrador.']);
                return false;
            }
            
            echo "Acceso Denegado";
            return false;
        }
        return true;
    }
}
