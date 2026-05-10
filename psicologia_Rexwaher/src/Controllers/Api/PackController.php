<?php

namespace Src\Controllers\Api;

use Src\Controllers\BaseController;

class PackController extends BaseController {

    // Obtener bonos activos de un paciente
    public function getActivePacks($patientId) {
        if (!$this->checkOwnershipByPatient($patientId)) {
            $this->jsonResponse(['error' => 'access_denied'], 403);
        }

        $sql = "SELECT pp.*, s.name as service_name 
                FROM patient_packs pp
                JOIN services s ON pp.service_id = s.id
                WHERE pp.patient_id = :pid 
                ORDER BY pp.status ASC, pp.purchase_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pid' => $patientId]);
        $packs = $stmt->fetchAll();

        $this->jsonResponse($packs);
    }

    // Obtener catálogo de bonos disponibles para vender
    public function getAvailablePacks() {
        $stmt = $this->db->query("SELECT * FROM services WHERE type = 'pack' AND active = 1 ORDER BY name ASC");
        $packs = $stmt->fetchAll();
        $this->jsonResponse($packs);
    }

    // Vender/Asignar un bono a un paciente
    public function store() {
        $data = $this->getJsonInput();
        
        if (empty($data['patient_id']) || empty($data['service_id'])) {
            $this->jsonResponse(['error' => 'validation_error'], 422);
        }

        // Obtener detalles del servicio (bono)
        $stmt = $this->db->prepare("SELECT * FROM services WHERE id = :id AND type = 'pack'");
        $stmt->execute(['id' => $data['service_id']]);
        $service = $stmt->fetch();

        if (!$service) {
            $this->jsonResponse(['error' => 'invalid_service', 'message' => 'El servicio no es un bono válido'], 400);
        }

        try {
            $sql = "INSERT INTO patient_packs (patient_id, service_id, sessions_total, sessions_used, price_paid, status) 
                    VALUES (:pid, :sid, :total, 0, :price, 'active')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'pid' => $data['patient_id'],
                'sid' => $service['id'],
                'total' => $service['session_count'],
                'price' => $service['price']
            ]);

            $this->jsonResponse(['message' => 'Bono asignado correctamente'], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }

    private function checkOwnershipByPatient($patientId) {
        if ($this->userRole === 'admin' || $this->userRole === 'manager') return true;
        $stmt = $this->db->prepare("SELECT professional_id FROM patients WHERE id = :id");
        $stmt->execute(['id' => $patientId]);
        $p = $stmt->fetch();
        return $p && $p['professional_id'] == $this->userId;
    }
}
