<?php

namespace Src\Controllers\Api;

use Src\Controllers\BaseController;

class CommissionController extends BaseController {

    public function update($professionalId) {
        // Solo managers o admins pueden cambiar comisiones
        if ($_SESSION['user_role'] !== 'manager' && $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'forbidden', 'message' => 'No tienes permisos para realizar esta acción']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['commission_percentage'])) {
            http_response_code(400);
            echo json_encode(['error' => 'bad_request', 'message' => 'Falta el porcentaje de comisión']);
            return;
        }

        $percentage = floatval($data['commission_percentage']);
        if ($percentage < 0 || $percentage > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'bad_request', 'message' => 'El porcentaje debe estar entre 0 y 100']);
            return;
        }

        $db = \getDBConnection();
        
        // Verificar que el usuario existe y es un profesional
        $stmt = $db->prepare("SELECT id, role FROM users WHERE id = ?");
        $stmt->execute([$professionalId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'message' => 'Usuario no encontrado']);
            return;
        }

        // Actualizar comisión
        $stmt = $db->prepare("UPDATE users SET commission_percentage = ? WHERE id = ?");
        $stmt->execute([$percentage, $professionalId]);

        echo json_encode(['success' => true, 'message' => 'Comisión actualizada correctamente']);
    }
    
    public function get($professionalId) {
         // Solo managers o admins pueden ver comisiones de otros, o el propio profesional
        if ($_SESSION['user_role'] !== 'manager' && $_SESSION['user_role'] !== 'admin' && $_SESSION['user_id'] != $professionalId) {
            http_response_code(403);
            echo json_encode(['error' => 'forbidden', 'message' => 'No tienes permisos para ver esta información']);
            return;
        }

        $db = \getDBConnection();
        $stmt = $db->prepare("SELECT commission_percentage FROM users WHERE id = ?");
        $stmt->execute([$professionalId]);
        $result = $stmt->fetch();

        if (!$result) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'message' => 'Usuario no encontrado']);
            return;
        }

        echo json_encode(['commission_percentage' => $result['commission_percentage']]);
    }
}
