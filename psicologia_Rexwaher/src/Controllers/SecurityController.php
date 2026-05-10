<?php

namespace Src\Controllers;

class SecurityController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        if ($this->userRole !== 'admin') {
            header('Location: /psicologia_Rexwaher/dashboard');
            exit;
        }
    }

    public function index() {
        // Obtener IPs bloqueadas o con muchos intentos recientes
        $sql = "SELECT ip_address, username, COUNT(*) as attempts, MAX(attempt_at) as last_attempt 
                FROM login_attempts 
                GROUP BY ip_address, username 
                ORDER BY last_attempt DESC LIMIT 50";
        
        $blocked = $this->db->query($sql)->fetchAll();
        
        require __DIR__ . '/../Views/admin/security.php';
    }

    public function unblock() {
        $data = $this->getJsonInput();
        $ip = $data['ip'] ?? null;
        
        if ($ip) {
            $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = :ip");
            $stmt->execute(['ip' => $ip]);
            $this->jsonResponse(['message' => 'IP desbloqueada correctamente']);
        }
        
        $this->jsonResponse(['error' => 'Falta IP'], 400);
    }
}
