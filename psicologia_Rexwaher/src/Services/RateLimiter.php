<?php

namespace Src\Services;

class RateLimiter {
    private $db;
    private $forbiddenUsers = ['admin', 'root', 'soporte', 'guest'];

    public function __construct() {
        $this->db = \getDBConnection();
    }

    public function check($ip, $username) {
        // 1. Bloqueo inmediato de usuarios críticos
        if (in_array(strtolower($username), $this->forbiddenUsers)) {
            return ['allowed' => false, 'message' => 'Usuario no permitido por política de seguridad.'];
        }

        // 2. Calcular intentos fallidos recientes (últimas 24h para el backoff)
        $attempts = $this->getRecentAttempts($ip, $username);
        
        // Si no hay intentos recientes, pase
        if ($attempts['total'] === 0) {
            return ['allowed' => true];
        }

        // 3. Lógica de Bloqueo (Backoff Progresivo)
        $blockDuration = 0;
        
        // Umbrales de intentos totales en ventana de 24h
        if ($attempts['total'] >= 20) {
            $blockDuration = 24 * 60; // 24 horas
        } elseif ($attempts['total'] >= 10) {
            $blockDuration = 60; // 1 hora
        } elseif ($attempts['total'] >= 5) {
            $blockDuration = 15; // 15 minutos
        }

        if ($blockDuration > 0) {
            $lastAttempt = new \DateTime($attempts['last_time']);
            $unlockTime = clone $lastAttempt;
            $unlockTime->modify("+{$blockDuration} minutes");
            $now = new \DateTime();

            if ($now < $unlockTime) {
                $wait = $now->diff($unlockTime);
                $minutes = ($wait->days * 24 * 60) + ($wait->h * 60) + $wait->i + 1;
                return [
                    'allowed' => false, 
                    'message' => "Demasiados intentos fallidos. Intente de nuevo en $minutes minutos."
                ];
            }
        }

        // 4. Rate Limit por Minuto (Ráfaga rápida)
        // Max 5 por usuario/min o 15 por IP/min
        if ($attempts['last_minute_user'] >= 5 || $attempts['last_minute_ip'] >= 15) {
            return ['allowed' => false, 'message' => 'Velocidad de intentos excedida. Espere un momento.'];
        }

        return ['allowed' => true];
    }

    public function logFailure($ip, $username) {
        $stmt = $this->db->prepare("INSERT INTO login_attempts (ip_address, username, attempt_at) VALUES (:ip, :user, NOW())");
        $stmt->execute(['ip' => $ip, 'user' => $username]);
    }

    public function clearAttempts($ip, $username) {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE (ip_address = :ip OR username = :user)");
        $stmt->execute(['ip' => $ip, 'user' => $username]);
    }

    private function getRecentAttempts($ip, $username) {
        // Contar intentos en las últimas 24h y en el último minuto
        // CORRECCIÓN: Se eliminan los parámetros con nombre (:ip, :user) repetidos porque PDO no siempre soporta reutilizarlos en la misma query.
        // Usamos parámetros posicionales (?) o nombres únicos.
        
        $sql = "SELECT 
                    COUNT(*) as total_24h,
                    SUM(CASE WHEN attempt_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) AND username = :user1 THEN 1 ELSE 0 END) as minute_user,
                    SUM(CASE WHEN attempt_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) AND ip_address = :ip1 THEN 1 ELSE 0 END) as minute_ip,
                    MAX(attempt_at) as last_attempt
                FROM login_attempts 
                WHERE (ip_address = :ip2 OR username = :user2) 
                AND attempt_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user1' => $username,
            'ip1' => $ip,
            'ip2' => $ip,
            'user2' => $username
        ]);
        $data = $stmt->fetch();

        return [
            'total' => (int)($data['total_24h'] ?? 0),
            'last_minute_user' => (int)($data['minute_user'] ?? 0),
            'last_minute_ip' => (int)($data['minute_ip'] ?? 0),
            'last_time' => $data['last_attempt'] ?? null
        ];
    }
}
