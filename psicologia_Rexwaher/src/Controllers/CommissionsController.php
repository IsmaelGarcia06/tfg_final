<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/app.php';

class CommissionsController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        if ($this->userRole !== 'admin') {
            redirect('/dashboard');
            exit;
        }
    }

    public function index() {
        $db = \getDBConnection();

        $professionals = $db->query("SELECT id, name FROM users WHERE role = 'professional' ORDER BY name ASC")->fetchAll();

        $sql = "SELECT 
                    s.id, 
                    s.start_time, 
                    s.fee_amount, 
                    svc.name as service_name,
                    u.name as professional_name,
                    (
                        SELECT t.percentage 
                        FROM professional_tariffs pt 
                        JOIN tariffs t ON pt.tariff_id = t.id
                        WHERE pt.user_id = s.professional_id 
                        AND pt.start_date <= DATE(s.start_time) 
                        AND (pt.end_date IS NULL OR pt.end_date >= DATE(s.start_time))
                        ORDER BY pt.start_date DESC 
                        LIMIT 1
                    ) as commission_rate
                FROM sessions s
                LEFT JOIN services svc ON s.service_id = svc.id
                JOIN users u ON s.professional_id = u.id
                WHERE s.status = 'completed'";

        $params = [];
        if (!empty($_GET['professional_id'])) {
            $sql .= " AND s.professional_id = :pid";
            $params['pid'] = $_GET['professional_id'];
        }
        if (!empty($_GET['start_date'])) {
            $sql .= " AND s.start_time >= :start";
            $params['start'] = $_GET['start_date'];
        }
        if (!empty($_GET['end_date'])) {
            $sql .= " AND s.start_time <= :end";
            $params['end'] = $_GET['end_date'] . ' 23:59:59';
        }

        $sql .= " ORDER BY s.start_time ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll();

        $monthlyStats = [];
        $serviceStats = [];
        $profStats = [];

        foreach ($sessions as $session) {
            $month = date('Y-m', strtotime($session['start_time'])); 
            $price = (float)$session['fee_amount'];
            $adminPercent = (float)($session['commission_rate'] ?? 0); 
            $adminProfit = $price * ($adminPercent / 100);
            $profEarnings = $price - $adminProfit;

            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = ['revenue' => 0, 'admin_profit' => 0, 'prof_cost' => 0];
            }
            $monthlyStats[$month]['revenue'] += $price;
            $monthlyStats[$month]['admin_profit'] += $adminProfit;
            $monthlyStats[$month]['prof_cost'] += $profEarnings;

            $svcName = $session['service_name'] ?? 'Otros';
            if (!isset($serviceStats[$svcName])) {
                $serviceStats[$svcName] = ['count' => 0, 'revenue' => 0, 'admin_profit' => 0];
            }
            $serviceStats[$svcName]['count']++;
            $serviceStats[$svcName]['revenue'] += $price;
            $serviceStats[$svcName]['admin_profit'] += $adminProfit;

            $profName = $session['professional_name'];
            if (!isset($profStats[$profName])) {
                $profStats[$profName] = ['revenue' => 0, 'admin_profit' => 0, 'prof_cost' => 0];
            }
            $profStats[$profName]['revenue'] += $price;
            $profStats[$profName]['admin_profit'] += $adminProfit;
            $profStats[$profName]['prof_cost'] += $profEarnings;
        }

        $chartLabels = array_keys($monthlyStats);
        $chartAdminData = array_column($monthlyStats, 'admin_profit');
        $chartProfData = array_column($monthlyStats, 'prof_cost');

        require __DIR__ . '/../Views/admin/commissions.php';
    }
}