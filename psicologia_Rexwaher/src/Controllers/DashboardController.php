<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/app.php';

class DashboardController {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
            exit;
        }

        $role = $_SESSION['user_role'];
        $name = $_SESSION['user_name'];
        $userId = $_SESSION['user_id'];

        $db = \getDBConnection();

        // 1. Citas Próximas (Hoy + Futuras, max 10)
        // Se muestran las de hoy en adelante, ordenadas por fecha
        $sqlUpcoming = "SELECT s.id, s.start_time, s.end_time, s.status, p.name as patient_name 
                        FROM sessions s 
                        JOIN patients p ON s.patient_id = p.id 
                        WHERE s.start_time >= CURDATE() 
                        AND s.status != 'cancelled'";
        
        $paramsUpcoming = [];
        if ($role === 'professional') {
            $sqlUpcoming .= " AND s.professional_id = :uid";
            $paramsUpcoming['uid'] = $userId;
        }
        
        $sqlUpcoming .= " ORDER BY s.start_time ASC LIMIT 10";
        
        $stmt = $db->prepare($sqlUpcoming);
        $stmt->execute($paramsUpcoming);
        $upcomingAppointments = $stmt->fetchAll();

        // 2. Últimos pacientes atendidos
        // Basado en last_session_at (que se actualiza al completar sesión)
        $sqlRecent = "SELECT id, name, email, phone, last_session_at 
                      FROM patients 
                      WHERE last_session_at IS NOT NULL";
        
        $paramsRecent = [];
        if ($role === 'professional') {
            $sqlRecent .= " AND professional_id = :uid";
            $paramsRecent['uid'] = $userId;
        }
        
        $sqlRecent .= " ORDER BY last_session_at DESC LIMIT 5";
        
        $stmt = $db->prepare($sqlRecent);
        $stmt->execute($paramsRecent);
        $recentPatients = $stmt->fetchAll();

        require __DIR__ . '/../Views/dashboard.php';
    }
}
