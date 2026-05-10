<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/app.php';

class ProfessionalsViewController {
    
    public function index() {
        if ($_SESSION['user_role'] !== 'manager' && $_SESSION['user_role'] !== 'admin') {
            redirect('/dashboard');
            exit;
        }

        $db = \getDBConnection();
        $stmt = $db->query("SELECT id, name, email, role, commission_percentage FROM users WHERE role = 'professional'");
        $professionals = $stmt->fetchAll();

        require __DIR__ . '/../Views/professionals/index.php';
    }
}
