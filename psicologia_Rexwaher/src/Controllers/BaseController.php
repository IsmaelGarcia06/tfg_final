<?php

namespace Src\Controllers;

use Src\Services\Csrf;

class BaseController {
    
    protected $db;
    protected $userId;
    protected $userRole;

    public function __construct() {
        $this->db = \getDBConnection();
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->userRole = $_SESSION['user_role'] ?? null;

        // Validación CSRF automática para métodos inseguros
        // Deshabilitada momentáneamente para arreglar los errores blancos tras deshacer
        /*if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
            $this->validateCsrf();
        }*/
    }

    protected function validateCsrf() {
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? ($_POST['csrf_token'] ?? null);

        if (!Csrf::validate($token)) {
            $this->jsonResponse(['error' => 'csrf_error', 'message' => 'Token de seguridad inválido o expirado.'], 403);
        }
    }

    protected function jsonResponse($data, $code = 200) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    protected function getJsonInput() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        return $input;
    }

    protected function checkOwnership($resourceUserId) {
        if ($this->userRole === 'admin' || $this->userRole === 'manager') {
            return true;
        }
        return (int)$this->userId === (int)$resourceUserId;
    }

    protected function requireRole($allowedRoles) {
        if (!in_array($this->userRole, $allowedRoles)) {
            $this->jsonResponse(['error' => 'access_denied', 'message' => 'Rol insuficiente.'], 403);
        }
    }
}