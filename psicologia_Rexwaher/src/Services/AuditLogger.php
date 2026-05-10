<?php

namespace Src\Services;

class AuditLogger {
    
    public static function log($action, $userId = null, $details = []) {
        $db = \getDBConnection();
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        
        $details['user_agent'] = $userAgent;
        
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (:uid, :action, :details, :ip)");
        $stmt->execute([
            'uid' => $userId,
            'action' => $action,
            'details' => json_encode($details),
            'ip' => $ip
        ]);

        // Alerta básica: Si hay muchos fallos desde esta IP
        if ($action === 'login_failed') {
            self::checkAlerts($ip);
        }
    }

    private static function checkAlerts($ip) {
        $db = \getDBConnection();
        // Contar fallos en los últimos 10 minutos
        $stmt = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'login_failed' AND ip_address = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        $stmt->execute(['ip' => $ip]);
        $count = $stmt->fetchColumn();

        if ($count >= 10) {
            // Aquí se podría enviar un email al admin
            // mail('admin@clinica.com', 'Alerta de Seguridad', "IP $ip ha fallado 10 veces en 10 minutos.");
        }
    }
}
