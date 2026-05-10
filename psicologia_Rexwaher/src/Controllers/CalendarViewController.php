<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/app.php';

class CalendarViewController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
            exit;
        }

        // Obtener los servicios activos directamente desde la base de datos
        // para inyectarlos en el HTML sin depender de JS
        $stmt = $this->db->query("SELECT * FROM services WHERE active = 1 ORDER BY name ASC");
        $services = $stmt->fetchAll();

        require __DIR__ . '/../Views/calendar/index.php';
    }
}