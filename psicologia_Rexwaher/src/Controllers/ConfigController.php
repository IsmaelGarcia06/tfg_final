<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/app.php';

use Src\Services\PasswordPolicy;

class ConfigController extends BaseController {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
            exit;
        }

        $stmt = $this->db->prepare("SELECT name, email, role, google_calendar_id, commission_percentage FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->userId]);
        $user = $stmt->fetch();

        require __DIR__ . '/../Views/config/index.php';
    }

    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'auth_required'], 401);
        }

        $data = $this->getJsonInput();
        
        if (empty($data['name']) || empty($data['email'])) {
            $this->jsonResponse(['error' => 'validation_error', 'message' => 'Nombre y Email son obligatorios'], 422);
        }

        try {
            $sql = "UPDATE users SET name = :name, email = :email WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'id' => $this->userId
            ]);

            $_SESSION['user_name'] = $data['name'];

            $this->jsonResponse(['message' => 'Perfil actualizado correctamente']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'db_error', 'message' => 'Error al actualizar perfil'], 500);
        }
    }

    public function changePassword() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'auth_required'], 401);
        }

        $data = $this->getJsonInput();
        $currentPass = $data['current_password'] ?? '';
        $newPass = $data['new_password'] ?? '';

        if (empty($currentPass) || empty($newPass)) {
            $this->jsonResponse(['error' => 'validation_error', 'message' => 'Complete todos los campos'], 422);
        }

        $policy = PasswordPolicy::validate($newPass);
        if (!$policy['valid']) {
            $this->jsonResponse(['error' => 'validation_error', 'message' => $policy['message']], 400);
        }

        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->userId]);
        $user = $stmt->fetch();

        if (!password_verify($currentPass, $user['password_hash'])) {
            $this->jsonResponse(['error' => 'auth_error', 'message' => 'La contraseña actual es incorrecta'], 400);
        }

        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $stmt->execute(['hash' => $newHash, 'id' => $this->userId]);

        $this->jsonResponse(['message' => 'Contraseña actualizada correctamente']);
    }
}