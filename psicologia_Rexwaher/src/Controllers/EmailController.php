<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/app.php';

class EmailController extends BaseController {
    
    public function index() {
        if ($this->userRole !== 'admin' && $this->userRole !== 'manager') {
            redirect('/dashboard');
            exit;
        }

        $templates = $this->db->query("SELECT * FROM email_templates")->fetchAll();
        $queue = $this->db->query("SELECT * FROM email_queue ORDER BY scheduled_at DESC LIMIT 20")->fetchAll();

        require __DIR__ . '/../Views/email/index.php';
    }

    public function updateTemplate() {
        if ($this->userRole !== 'admin') $this->jsonResponse(['error' => 'access_denied'], 403);

        $data = $this->getJsonInput();
        $id = $data['id'];
        
        $stmt = $this->db->prepare("UPDATE email_templates SET subject = :sub, body = :body WHERE id = :id");
        $stmt->execute([
            'sub' => $data['subject'],
            'body' => $data['body'],
            'id' => $id
        ]);

        $this->jsonResponse(['message' => 'Plantilla actualizada']);
    }

    // Nuevo: Activar/Desactivar Plantilla
    public function toggleTemplate() {
        if ($this->userRole !== 'admin') $this->jsonResponse(['error' => 'access_denied'], 403);

        $data = $this->getJsonInput();
        $id = $data['id'];
        $active = $data['active'] ? 1 : 0;

        $stmt = $this->db->prepare("UPDATE email_templates SET active = :active WHERE id = :id");
        $stmt->execute(['active' => $active, 'id' => $id]);

        $this->jsonResponse(['message' => 'Estado actualizado']);
    }

    public function runAutomation() {
        if ($this->userRole !== 'admin') $this->jsonResponse(['error' => 'access_denied'], 403);

        // Verificar si la plantilla de recordatorio está activa
        $tpl = $this->db->query("SELECT * FROM email_templates WHERE code = 'reminder_24h' AND active = 1")->fetch();

        if (!$tpl) {
            $this->jsonResponse(['message' => "La automatización de recordatorios está desactivada."]);
        }

        $count = 0;
        $tomorrow = new \DateTime('tomorrow');
        $start = $tomorrow->format('Y-m-d 00:00:00');
        $end = $tomorrow->format('Y-m-d 23:59:59');

        $sql = "SELECT s.*, p.name as p_name, p.email as p_email 
                FROM sessions s
                JOIN patients p ON s.patient_id = p.id
                WHERE s.start_time BETWEEN :start AND :end
                AND s.status = 'scheduled'
                AND p.email IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1 FROM email_queue q 
                    WHERE q.patient_id = s.patient_id 
                    AND q.subject LIKE '%Recordatorio%' 
                    AND DATE(q.scheduled_at) = CURDATE()
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['start' => $start, 'end' => $end]);
        $appointments = $stmt->fetchAll();

        foreach ($appointments as $appt) {
            $body = str_replace(
                ['{{patient_name}}', '{{date}}', '{{time}}'],
                [$appt['p_name'], date('d/m/Y', strtotime($appt['start_time'])), date('H:i', strtotime($appt['start_time']))],
                $tpl['body']
            );

            $this->db->prepare("INSERT INTO email_queue (patient_id, recipient_email, subject, body, scheduled_at) VALUES (:pid, :email, :sub, :body, NOW())")
                ->execute([
                    'pid' => $appt['patient_id'],
                    'email' => $appt['p_email'],
                    'sub' => $tpl['subject'],
                    'body' => $body
                ]);
            $count++;
        }

        $this->jsonResponse(['message' => "Automatización ejecutada. $count correos generados."]);
    }
}