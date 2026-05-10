<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/app.php';

use Src\Services\Logger;

class ItAdminController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        if ($this->userRole !== 'it_admin') {
            redirect('/dashboard');
            exit;
        }
    }

    public function index() {
        // Obtener configuración actual
        $stmt = $this->db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'it_alert_email'");
        $alertEmail = $stmt->fetchColumn();

        // Obtener logs
        $logs = Logger::getLogs(50);

        require __DIR__ . '/../Views/it/dashboard.php';
    }

    public function updateSettings() {
        $data = $this->getJsonInput();
        $email = $data['email'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['error' => 'validation_error', 'message' => 'Email inválido'], 400);
        }

        $stmt = $this->db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('it_alert_email', :val) ON DUPLICATE KEY UPDATE setting_value = :val");
        $stmt->execute(['val' => $email]);

        Logger::info("Configuración de alertas actualizada por usuario ID {$this->userId}");
        $this->jsonResponse(['message' => 'Configuración guardada']);
    }

    public function clearLogs() {
        Logger::clearLogs();
        Logger::info("Logs limpiados por usuario ID {$this->userId}");
        $this->jsonResponse(['message' => 'Logs limpiados']);
    }
    
    public function testError() {
        // Simular un error para probar el sistema
        try {
            throw new \Exception("Error de prueba generado manualmente por IT Admin");
        } catch (\Exception $e) {
            Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
        $this->jsonResponse(['message' => 'Error de prueba generado y logueado']);
    }
}