<?php

namespace Src\Controllers\Api;

use Src\Controllers\BaseController;
use Src\Services\PasswordPolicy;

class AdminApiController extends BaseController {

    public function __construct() {
        parent::__construct();
        if ($this->userRole !== 'admin') {
            $this->jsonResponse(['error' => 'access_denied'], 403);
        }
    }

    // --- USUARIOS ---
    public function createUser() {
        $data = $this->getJsonInput();

        // Validar política de contraseña
        $policy = PasswordPolicy::validate($data['password']);
        if (!$policy['valid']) {
            $this->jsonResponse(['error' => 'validation_error', 'message' => $policy['message']], 400);
        }

        // Uso de ARGON2ID para máxima seguridad
        $hash = password_hash($data['password'], PASSWORD_ARGON2ID);
        
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)");
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'hash' => $hash,
            'role' => $data['role']
        ]);
        
        $this->jsonResponse(['message' => 'Usuario creado']);
    }

    // --- SERVICIOS (Antes Tarifas) ---
    public function createService() {
        $data = $this->getJsonInput();
        // data: { name, price, type, session_count, duration }
        
        $stmt = $this->db->prepare("INSERT INTO services (name, price, type, session_count, duration_minutes) VALUES (:name, :price, :type, :count, :duration)");
        $stmt->execute([
            'name' => $data['name'],
            'price' => $data['price'],
            'type' => $data['type'],
            'count' => $data['session_count'] ?? 1,
            'duration' => $data['duration'] ?? 60
        ]);
        $this->jsonResponse(['message' => 'Servicio creado']);
    }

    // --- COMISIONES PROFESIONALES (Tariffs) ---
    public function createCommissionType() {
        $data = $this->getJsonInput();
        $stmt = $this->db->prepare("INSERT INTO tariffs (name, percentage, description) VALUES (:name, :perc, :desc)");
        $stmt->execute([
            'name' => $data['name'],
            'perc' => $data['percentage'],
            'desc' => $data['description'] ?? ''
        ]);
        $this->jsonResponse(['message' => 'Tipo de comisión creado']);
    }

    public function assignCommission() {
        $data = $this->getJsonInput();
        // data: { user_id, tariff_id, start_date }
        
        $sqlLast = "SELECT id FROM professional_tariffs 
                    WHERE user_id = :uid AND (end_date IS NULL OR end_date >= :start)
                    ORDER BY start_date DESC LIMIT 1";
        $stmt = $this->db->prepare($sqlLast);
        $stmt->execute(['uid' => $data['user_id'], 'start' => $data['start_date']]);
        $last = $stmt->fetch();

        if ($last) {
            $endDate = date('Y-m-d', strtotime($data['start_date'] . ' -1 day'));
            $upd = $this->db->prepare("UPDATE professional_tariffs SET end_date = :end WHERE id = :id");
            $upd->execute(['end' => $endDate, 'id' => $last['id']]);
        }

        $stmt = $this->db->prepare("INSERT INTO professional_tariffs (user_id, tariff_id, start_date) VALUES (:uid, :tid, :start)");
        $stmt->execute([
            'uid' => $data['user_id'],
            'tid' => $data['tariff_id'],
            'start' => $data['start_date']
        ]);

        $this->jsonResponse(['message' => 'Comisión asignada correctamente']);
    }
}
