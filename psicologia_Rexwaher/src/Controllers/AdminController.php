<?php

namespace Src\Controllers;

class AdminController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        if ($this->userRole !== 'admin') {
            header('Location: /practicas2026/psicologia_Rexwaher/dashboard');
            exit;
        }
    }

    public function usersIndex() {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        require __DIR__ . '/../Views/admin/users.php';
    }

    public function servicesIndex() {
        $stmt = $this->db->query("SELECT * FROM services WHERE active = 1 ORDER BY name ASC");
        $services = $stmt->fetchAll();
        require __DIR__ . '/../Views/admin/services.php';
    }

    public function userCommissions($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) die("Usuario no encontrado");

        $sql = "SELECT pt.*, t.name as tariff_name, t.percentage 
                FROM professional_tariffs pt 
                JOIN tariffs t ON pt.tariff_id = t.id 
                WHERE pt.user_id = :uid 
                ORDER BY pt.start_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $history = $stmt->fetchAll();

        $allTariffs = $this->db->query("SELECT * FROM tariffs")->fetchAll();

        require __DIR__ . '/../Views/admin/user_commissions.php';
    }
}
