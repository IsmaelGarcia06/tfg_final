<?php

namespace Src\Controllers\Api;

use Src\Controllers\BaseController;
use Src\Services\GoogleCalendarService;
use PDO;

class SessionController extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $sql = "SELECT s.*, p.name as patient_name, sv.name as service_name
                FROM sessions s 
                JOIN patients p ON s.patient_id = p.id
                LEFT JOIN services sv ON s.service_id = sv.id";

        $params = [];
        $conditions = [];

        if ($this->userRole === 'professional') {
            $conditions[] = "s.professional_id = :prof_id";
            $params['prof_id'] = $this->userId;
        }

        if (isset($_GET['start']) && isset($_GET['end'])) {
            $startStr = substr($_GET['start'], 0, 10);
            $endStr   = substr($_GET['end'], 0, 10);

            $start = $startStr . ' 00:00:00';
            $end   = $endStr . ' 23:59:59';

            $conditions[] = "s.start_time >= :start AND s.start_time <= :end";
            $params['start'] = $start;
            $params['end'] = $end;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll();

        if (isset($_GET['format']) && $_GET['format'] === 'calendar') {
            $events = array_map(function($s) {
                $color = '#3788d8';
                if ($s['status'] === 'completed') $color = '#28a745';
                if ($s['status'] === 'cancelled') $color = '#dc3545';

                return [
                    'id' => $s['id'],
                    'title' => $s['patient_name'],
                    'start' => str_replace(' ', 'T', $s['start_time']),
                    'end' => str_replace(' ', 'T', $s['end_time']),
                    'color' => $color,
                    'extendedProps' => [
                        'status' => $s['status'],
                        'notes' => $s['notes'],
                        'patient_id' => $s['patient_id'],
                        'service_name' => $s['service_name']
                    ]
                ];
            }, $sessions);
            $this->jsonResponse($events);
            return;
        }

        $this->jsonResponse($sessions);
    }

    public function store() {
        $data = $this->getJsonInput();

        if (empty($data['patient_id']) || empty($data['start_time']) || empty($data['duration'])) {
            $this->jsonResponse(['error' => 'validation_error', 'message' => 'Faltan datos'], 422);
        }

        $startTime = new \DateTime($data['start_time']);
        $endTime = clone $startTime;
        $endTime->modify("+{$data['duration']} minutes");

        $professionalId = $this->userRole === 'professional' ? $this->userId : ($data['professional_id'] ?? $this->userId);

        // 1. Validar solapamiento
        $sqlCheck = "SELECT COUNT(*) FROM sessions 
                     WHERE professional_id = :pid 
                     AND status != 'cancelled'
                     AND start_time < :end_time 
                     AND end_time > :start_time";

        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([
            'pid' => $professionalId,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s')
        ]);

        if ($stmtCheck->fetchColumn() > 0) {
            $this->jsonResponse(['error' => 'conflict', 'message' => 'Ya existe una cita en ese horario.'], 409);
        }

        // 2. Gestión de Bonos (Packs)
        $packId = $data['patient_pack_id'] ?? null;
        $serviceId = $data['service_id'] ?? null;

        if ($packId) {
            $stmtPack = $this->db->prepare("SELECT * FROM patient_packs WHERE id = :id AND status = 'active'");
            $stmtPack->execute(['id' => $packId]);
            $pack = $stmtPack->fetch();

            if (!$pack || $pack['sessions_used'] >= $pack['sessions_total']) {
                $this->jsonResponse(['error' => 'invalid_pack', 'message' => 'El bono no es válido o está agotado'], 400);
            }

            $updPack = $this->db->prepare("UPDATE patient_packs SET sessions_used = sessions_used + 1 WHERE id = :id");
            $updPack->execute(['id' => $packId]);

            if ($pack['sessions_used'] + 1 >= $pack['sessions_total']) {
                $this->db->prepare("UPDATE patient_packs SET status = 'completed' WHERE id = :id")->execute(['id' => $packId]);
            }
        }

        // 3. Obtener datos del paciente y servicio
        $stmtPatient = $this->db->prepare("SELECT p.name as patient_name, sv.name as service_name 
                                          FROM patients p 
                                          LEFT JOIN services sv ON sv.id = :sid
                                          WHERE p.id = :pid");
        $stmtPatient->execute(['pid' => $data['patient_id'], 'sid' => $serviceId]);
        $patientData = $stmtPatient->fetch();

        // 4. Insertar Sesión
        $sql = "INSERT INTO sessions (patient_id, professional_id, service_id, patient_pack_id, start_time, end_time, status, notes) 
                VALUES (:pid, :prof_id, :sid, :pack_id, :start, :end, 'scheduled', :notes)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'pid' => $data['patient_id'],
                'prof_id' => $professionalId,
                'sid' => $serviceId,
                'pack_id' => $packId,
                'start' => $startTime->format('Y-m-d H:i:s'),
                'end' => $endTime->format('Y-m-d H:i:s'),
                'notes' => $data['notes'] ?? null
            ]);

            $sessionId = $this->db->lastInsertId();

            // 5. Sincronizar con Google Calendar
            $gcalService = new GoogleCalendarService();
            $googleEventId = $gcalService->createEvent($professionalId, [
                'patient_name' => $patientData['patient_name'] ?? 'Sesión',
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
                'notes' => $data['notes'] ?? ''
            ]);

            // Guardar google_event_id
            if ($googleEventId) {
                $this->db->prepare("UPDATE sessions SET google_event_id = :gid WHERE id = :id")
                    ->execute(['gid' => $googleEventId, 'id' => $sessionId]);
            }

            $this->jsonResponse(['id' => $sessionId, 'message' => 'Sesión agendada'], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update($id) {
        $stmt = $this->db->prepare("SELECT * FROM sessions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $session = $stmt->fetch();

        if (!$session) $this->jsonResponse(['error' => 'not_found'], 404);
        if (!$this->checkOwnership($session['professional_id'])) $this->jsonResponse(['error' => 'access_denied'], 403);

        $data = $this->getJsonInput();
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $params['status'] = $data['status'];

            if ($data['status'] === 'cancelled' && $session['status'] !== 'cancelled' && $session['patient_pack_id']) {
                $this->refundPackSession($session['patient_pack_id']);
            }

            if ($data['status'] === 'completed' && $session['status'] !== 'completed') {
                $this->updatePatientLastSession($session['patient_id'], $session['start_time']);
            }
        }
        if (isset($data['notes'])) { $fields[] = "notes = :notes"; $params['notes'] = $data['notes']; }

        if (!empty($fields)) {
            $sql = "UPDATE sessions SET " . implode(", ", $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        }

        // Si se cancela, eliminar de Google Calendar
        if (isset($data['status']) && $data['status'] === 'cancelled' && $session['google_event_id']) {
            $gcalService = new GoogleCalendarService();
            $gcalService->deleteEvent($session['professional_id'], $session['google_event_id']);
        }

        $this->jsonResponse(['message' => 'Sesión actualizada']);
    }

    private function updatePatientLastSession($patientId, $sessionDate) {
        $sql = "UPDATE patients SET last_session_at = :date WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['date' => $sessionDate, 'id' => $patientId]);
    }

    private function refundPackSession($packId) {
        $sql = "UPDATE patient_packs SET sessions_used = sessions_used - 1, status = 'active' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $packId]);
    }
}