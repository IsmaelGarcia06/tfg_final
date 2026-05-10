<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/app.php';

class PatientViewController {
    
    public function index() {
        $this->checkAuth();
        require __DIR__ . '/../Views/patients/index.php';
    }

    public function create() {
        $this->checkAuth();
        require __DIR__ . '/../Views/patients/create.php';
    }

    public function edit($id) {
        $this->checkAuth();
        
        // Obtener datos básicos del paciente para renderizar la vista inicial
        // El resto de datos (citas, notas) se cargarán vía API/AJAX para mantener la página ligera
        $db = \getDBConnection();
        $stmt = $db->prepare("SELECT * FROM patients WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $patient = $stmt->fetch();

        if (!$patient) {
            redirect('/patients?error=not_found');
            exit;
        }

        // Verificar permisos (si es profesional, solo sus pacientes)
        if ($_SESSION['user_role'] === 'professional' && $patient['professional_id'] != $_SESSION['user_id']) {
            redirect('/patients?error=access_denied');
            exit;
        }

        require __DIR__ . '/../Views/patients/edit.php';
    }

    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
            exit;
        }
    }
}