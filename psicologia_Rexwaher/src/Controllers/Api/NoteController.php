<?php

namespace Src\Controllers\Api;

use Src\Controllers\BaseController;

class NoteController extends BaseController {

    public function index($patientId) {
        // Verificar acceso al paciente
        if (!$this->canAccessPatient($patientId)) {
            $this->jsonResponse(['error' => 'access_denied'], 403);
        }

        $sql = "SELECT n.*, u.name as professional_name, d.file_path, d.file_name 
                FROM patient_notes n 
                JOIN users u ON n.professional_id = u.id 
                LEFT JOIN patient_documents d ON d.note_id = n.id
                WHERE n.patient_id = :pid 
                ORDER BY n.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pid' => $patientId]);
        $notes = $stmt->fetchAll();

        $this->jsonResponse($notes);
    }

    public function store() {
        // POST multipart/form-data
        $patientId = $_POST['patient_id'] ?? null;
        $content = $_POST['content'] ?? '';

        if (!$patientId || empty($content)) {
            $this->jsonResponse(['error' => 'validation_error'], 422);
        }

        if (!$this->canAccessPatient($patientId)) {
            $this->jsonResponse(['error' => 'access_denied'], 403);
        }

        try {
            $this->db->beginTransaction();

            // 1. Crear Nota
            $stmt = $this->db->prepare("INSERT INTO patient_notes (patient_id, professional_id, content) VALUES (:pid, :uid, :content)");
            $stmt->execute([
                'pid' => $patientId,
                'uid' => $this->userId,
                'content' => $content
            ]);
            $noteId = $this->db->lastInsertId();

            // 2. Manejar Archivo (si existe)
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $this->saveFile($_FILES['file'], $patientId, $noteId);
            }

            $this->db->commit();
            $this->jsonResponse(['message' => 'Nota guardada', 'id' => $noteId], 201);

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->jsonResponse(['error' => 'server_error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getDocuments($patientId) {
        if (!$this->canAccessPatient($patientId)) {
            $this->jsonResponse(['error' => 'access_denied'], 403);
        }

        $stmt = $this->db->prepare("SELECT * FROM patient_documents WHERE patient_id = :pid ORDER BY uploaded_at DESC");
        $stmt->execute(['pid' => $patientId]);
        $docs = $stmt->fetchAll();

        $this->jsonResponse($docs);
    }

    public function uploadDocument() {
        $patientId = $_POST['patient_id'] ?? null;
        
        if (!$patientId || !isset($_FILES['file'])) {
            $this->jsonResponse(['error' => 'validation_error'], 422);
        }

        if (!$this->canAccessPatient($patientId)) {
            $this->jsonResponse(['error' => 'access_denied'], 403);
        }

        try {
            $this->saveFile($_FILES['file'], $patientId, null);
            $this->jsonResponse(['message' => 'Documento subido'], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'upload_error', 'message' => $e->getMessage()], 500);
        }
    }

    private function saveFile($file, $patientId, $noteId) {
        $uploadDir = __DIR__ . '/../../../public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('doc_') . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $this->db->prepare("INSERT INTO patient_documents (patient_id, note_id, file_name, file_path, file_type) VALUES (:pid, :nid, :name, :path, :type)");
            $stmt->execute([
                'pid' => $patientId,
                'nid' => $noteId,
                'name' => $file['name'],
                'path' => $fileName,
                'type' => $file['type']
            ]);
        } else {
            throw new \Exception("Error al mover el archivo subido.");
        }
    }

    private function canAccessPatient($patientId) {
        if ($this->userRole === 'admin' || $this->userRole === 'manager') return true;
        
        $stmt = $this->db->prepare("SELECT professional_id FROM patients WHERE id = :id");
        $stmt->execute(['id' => $patientId]);
        $p = $stmt->fetch();
        
        return $p && $p['professional_id'] == $this->userId;
    }
}
