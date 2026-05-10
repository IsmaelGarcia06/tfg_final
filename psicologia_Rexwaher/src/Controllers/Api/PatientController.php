<?php

namespace Src\Controllers\Api;

use Src\Controllers\BaseController;
use PDO;

class PatientController extends BaseController {

    public function index() {
        $sql = "SELECT p.*, u.name as professional_name 
                FROM patients p 
                JOIN users u ON p.professional_id = u.id";
        
        $params = [];
        $conditions = [];

        if ($this->userRole === 'professional') {
            $conditions[] = "p.professional_id = :prof_id";
            $params['prof_id'] = $this->userId;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY p.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $all = $stmt->fetchAll();

        $activeStatuses = ['open', 'reopened'];
        $active = [];
        $inactive = [];

        foreach ($all as $p) {
            if (in_array($p['status'], $activeStatuses)) {
                $active[] = $p;
            } else {
                $inactive[] = $p;
            }
        }

        $this->jsonResponse(['active' => $active, 'inactive' => $inactive]);
    }

    public function show($id) {
        $patient = $this->getPatientIfAllowed($id);
        if (!$patient) return; 

        $stmtM = $this->db->prepare("SELECT * FROM patient_members WHERE patient_id = :pid");
        $stmtM->execute(['pid' => $id]);
        $patient['members'] = $stmtM->fetchAll();

        $this->jsonResponse($patient);
    }

    public function store() {
        $data = $this->getJsonInput();
        
        if (empty($data['name']) || empty($data['members'])) {
            $this->jsonResponse(['error' => 'validation_error', 'message' => 'Nombre del caso y al menos un miembro requeridos'], 422);
        }

        $professionalId = $this->userRole === 'professional' ? $this->userId : ($data['professional_id'] ?? $this->userId);

        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO patients (professional_id, name, type, entry_date, referred_by, drive_folder_url, status) 
                    VALUES (:pid, :name, :type, :entry, :ref, :drive, 'open')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'pid' => $professionalId,
                'name' => $data['name'],
                'type' => $data['type'] ?? 'individual',
                'entry' => $data['entry_date'] ?? date('Y-m-d'),
                'ref' => $data['referred_by'] ?? null,
                'drive' => $data['drive_folder_url'] ?? null
            ]);
            
            $patientId = $this->db->lastInsertId();

            $sqlM = "INSERT INTO patient_members (patient_id, name, surname, birth_date, occupation, email, phone) 
                     VALUES (:pid, :name, :surname, :birth, :occ, :email, :phone)";
            $stmtM = $this->db->prepare($sqlM);

            foreach ($data['members'] as $m) {
                $stmtM->execute([
                    'pid' => $patientId,
                    'name' => $m['name'],
                    'surname' => $m['surname'] ?? null,
                    'birth' => $m['birth_date'] ?? null,
                    'occ' => $m['occupation'] ?? null,
                    'email' => $m['email'] ?? null,
                    'phone' => $m['phone'] ?? null
                ]);
            }

            $this->db->commit();
            $this->jsonResponse(['id' => $patientId, 'message' => 'Expediente creado'], 201);

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update($id) {
        $data = $this->getJsonInput();
        
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['name'])) { $fields[] = "name = :name"; $params['name'] = $data['name']; }
        if (isset($data['status'])) { $fields[] = "status = :status"; $params['status'] = $data['status']; }
        if (isset($data['closure_date'])) { $fields[] = "closure_date = :cdate"; $params['cdate'] = $data['closure_date']; }
        if (isset($data['drive_folder_url'])) { $fields[] = "drive_folder_url = :drive"; $params['drive'] = $data['drive_folder_url']; }

        if (!empty($fields)) {
            $sql = "UPDATE patients SET " . implode(", ", $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        }

        $this->jsonResponse(['message' => 'Expediente actualizado']);
    }

    public function addMember($id) {
        $patient = $this->getPatientIfAllowed($id);
        if (!$patient) return;

        $data = $this->getJsonInput();
        
        if (empty($data['name'])) {
            $this->jsonResponse(['error' => 'validation_error', 'message' => 'Nombre requerido'], 422);
        }

        try {
            $sql = "INSERT INTO patient_members (patient_id, name, surname, birth_date, occupation, email, phone) 
                    VALUES (:pid, :name, :surname, :birth, :occ, :email, :phone)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'pid' => $id,
                'name' => $data['name'],
                'surname' => $data['surname'] ?? null,
                'birth' => $data['birth_date'] ?? null,
                'occ' => $data['occupation'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null
            ]);

            $this->jsonResponse(['message' => 'Miembro añadido correctamente'], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getAppointments($id) {
        $patient = $this->getPatientIfAllowed($id);
        if (!$patient) return;

        $sql = "SELECT * FROM sessions WHERE patient_id = :pid ORDER BY start_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pid' => $id]);
        $this->jsonResponse($stmt->fetchAll());
    }

    private function getPatientIfAllowed($id) {
        $stmt = $this->db->prepare("SELECT * FROM patients WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $patient = $stmt->fetch();

        if (!$patient) {
            $this->jsonResponse(['error' => 'not_found'], 404);
            return null;
        }

        if (!$this->checkOwnership($patient['professional_id'])) {
            $this->jsonResponse(['error' => 'access_denied'], 403);
            return null;
        }

        return $patient;
    }
}
