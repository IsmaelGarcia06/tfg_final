<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/app.php';

use Src\Services\PasswordPolicy;

class ProfileController extends BaseController {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
            exit;
        }

        // Obtener datos del usuario actual
        $stmt = $this->db->prepare("SELECT name, email, role, two_factor_enabled, google_access_token FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->userId]);
        $user = $stmt->fetch();

        require __DIR__ . '/../Views/profile/index.php';
    }

    public function update() {
        if (!isset($_SESSION['user_id'])) $this->jsonResponse(['error' => 'auth_required'], 401);

        $data = $this->getJsonInput();
        
        if (empty($data['name']) || empty($data['email'])) {
            $this->jsonResponse(['error' => 'validation_error', 'message' => 'Nombre y Email requeridos'], 422);
        }

        try {
            $stmt = $this->db->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'id' => $this->userId
            ]);

            $_SESSION['user_name'] = $data['name']; // Actualizar sesión
            $this->jsonResponse(['message' => 'Perfil actualizado']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'db_error', 'message' => 'Error al actualizar'], 500);
        }
    }

    public function changePassword() {
        if (!isset($_SESSION['user_id'])) $this->jsonResponse(['error' => 'auth_required'], 401);

        $data = $this->getJsonInput();
        $current = $data['current_password'] ?? '';
        $new = $data['new_password'] ?? '';

        // Validar política
        $policy = PasswordPolicy::validate($new);
        if (!$policy['valid']) {
            $this->jsonResponse(['error' => 'validation_error', 'message' => $policy['message']], 400);
        }

        // Verificar actual
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->userId]);
        $user = $stmt->fetch();

        if (!password_verify($current, $user['password_hash'])) {
            $this->jsonResponse(['error' => 'auth_error', 'message' => 'Contraseña actual incorrecta'], 400);
        }

        // Actualizar
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $stmt->execute(['hash' => $hash, 'id' => $this->userId]);

        $this->jsonResponse(['message' => 'Contraseña cambiada']);
    }
}