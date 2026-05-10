<?php

// Script para ejecutar vía CRON
require_once __DIR__ . '/../config/db.php';

class EmailWorker {
    private $db;

    public function __construct() {
        $this->db = \getDBConnection();
    }

    public function run() {
        echo "[" . date('Y-m-d H:i:s') . "] Iniciando Cron...\n";
        
        $this->generateReminders();
        $this->processQueue();
        
        echo "[" . date('Y-m-d H:i:s') . "] Fin Cron.\n";
    }

    /**
     * Genera recordatorios para sesiones de mañana (24h antes)
     */
    private function generateReminders() {
        // Buscar sesiones programadas para mañana entre 00:00 y 23:59
        // que NO tengan ya un recordatorio generado
        
        // Nota: Para simplificar, asumimos que si no está en email_queue, no se ha enviado.
        // En producción, mejor tener una tabla 'session_reminders' para evitar duplicados.
        
        $sql = "
            SELECT s.id, s.start_time, p.email, p.name as patient_name, u.name as prof_name
            FROM sessions s
            JOIN patients p ON s.patient_id = p.id
            JOIN users u ON s.professional_id = u.id
            WHERE s.status = 'scheduled'
            AND s.start_time BETWEEN DATE_ADD(NOW(), INTERVAL 23 HOUR) AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
            AND p.email IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM email_queue q 
                WHERE q.recipient_email = p.email 
                AND q.body LIKE CONCAT('%', DATE_FORMAT(s.start_time, '%Y-%m-%d'), '%')
            )
        ";

        $stmt = $this->db->query($sql);
        $sessions = $stmt->fetchAll();

        foreach ($sessions as $session) {
            $this->enqueueEmail(
                $session['email'],
                "Recordatorio de Cita: " . $session['start_time'],
                "Hola {$session['patient_name']},\n\nTe recordamos tu cita con {$session['prof_name']} para mañana a las {$session['start_time']}.\n\nSaludos.",
                date('Y-m-d H:i:s') // Enviar ya
            );
            echo "Recordatorio generado para sesión #{$session['id']}\n";
        }
    }

    /**
     * Procesa la cola de envíos
     */
    private function processQueue() {
        // Buscar emails pendientes cuya fecha de envío ya pasó
        $stmt = $this->db->prepare("SELECT * FROM email_queue WHERE status = 'pending' AND send_after <= NOW() LIMIT 10");
        $stmt->execute();
        $emails = $stmt->fetchAll();

        foreach ($emails as $email) {
            if ($this->sendEmail($email['recipient_email'], $email['subject'], $email['body'])) {
                $upd = $this->db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = :id");
                $upd->execute(['id' => $email['id']]);
                echo "Email enviado a {$email['recipient_email']}\n";
            } else {
                $upd = $this->db->prepare("UPDATE email_queue SET status = 'failed', attempts = attempts + 1 WHERE id = :id");
                $upd->execute(['id' => $email['id']]);
                echo "Fallo envío a {$email['recipient_email']}\n";
            }
        }
    }

    private function enqueueEmail($to, $subject, $body, $sendAfter) {
        $stmt = $this->db->prepare("INSERT INTO email_queue (recipient_email, subject, body, send_after) VALUES (:to, :sub, :body, :after)");
        $stmt->execute([
            'to' => $to,
            'sub' => $subject,
            'body' => $body,
            'after' => $sendAfter
        ]);
    }

    private function sendEmail($to, $subject, $body) {
        // Aquí iría la integración real con PHPMailer o Mailgun
        // Simulamos éxito el 90% de las veces
        // mail($to, $subject, $body);
        return rand(1, 10) > 1;
    }
}

// Ejecutar
$worker = new EmailWorker();
$worker->run();
