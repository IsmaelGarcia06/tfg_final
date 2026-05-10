<?php

namespace Src\Controllers;

require_once __DIR__ . '/../../config/app.php';

class FinancialViewController extends BaseController {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
            exit;
        }

        $db = \getDBConnection();
        $userId = $this->userId;

        // 1. Obtener todas las sesiones completadas del usuario (o de todos si es admin y quiere ver global, pero priorizamos vista personal)
        // Si es admin, podríamos añadir un selector, pero por defecto mostramos SU facturación si atiende pacientes, o global si no.
        // Asumiremos vista personal para todos por ahora.
        
        $sql = "SELECT 
                    s.start_time, 
                    s.fee_amount, 
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
                WHERE s.professional_id = :uid 
                AND s.status = 'completed'
                ORDER BY s.start_time ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $sessions = $stmt->fetchAll();

        // 2. Procesar Datos
        $currentYear = date('Y');
        $lastYear = date('Y') - 1;
        $currentMonth = date('Y-m');
        $sameMonthLastYear = $lastYear . '-' . date('m');

        $stats = [
            'month_current' => 0,
            'month_last_year' => 0,
            'year_current' => 0,
            'year_last' => 0,
            'monthly_data_current' => array_fill(1, 12, 0),
            'monthly_data_last' => array_fill(1, 12, 0)
        ];

        foreach ($sessions as $s) {
            $date = $s['start_time'];
            $year = date('Y', strtotime($date));
            $month = date('n', strtotime($date)); // 1-12
            $ym = date('Y-m', strtotime($date));

            $gross = (float)$s['fee_amount'];
            $adminShare = $gross * ((float)($s['commission_rate'] ?? 0) / 100);
            $netIncome = $gross - $adminShare; // Lo que gana el profesional

            // KPIs
            if ($ym === $currentMonth) $stats['month_current'] += $netIncome;
            if ($ym === $sameMonthLastYear) $stats['month_last_year'] += $netIncome;
            
            if ($year == $currentYear) {
                $stats['year_current'] += $netIncome;
                $stats['monthly_data_current'][$month] += $netIncome;
            }
            if ($year == $lastYear) {
                $stats['year_last'] += $netIncome;
                $stats['monthly_data_last'][$month] += $netIncome;
            }
        }

        // Calcular variaciones
        $monthGrowth = 0;
        if ($stats['month_last_year'] > 0) {
            $monthGrowth = (($stats['month_current'] - $stats['month_last_year']) / $stats['month_last_year']) * 100;
        } elseif ($stats['month_current'] > 0) {
            $monthGrowth = 100;
        }

        $yearGrowth = 0;
        if ($stats['year_last'] > 0) {
            $yearGrowth = (($stats['year_current'] - $stats['year_last']) / $stats['year_last']) * 100;
        } elseif ($stats['year_current'] > 0) {
            $yearGrowth = 100;
        }

        require __DIR__ . '/../Views/financial/index.php';
    }
}
