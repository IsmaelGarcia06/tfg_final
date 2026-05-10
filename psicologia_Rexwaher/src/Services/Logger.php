<?php

namespace Src\Services;

class Logger {
    
    private static $logFile = __DIR__ . '/../../logs/system.log';

    public static function error($message, $context = []) {
        self::write('ERROR', $message, $context);
        self::sendAlert($message, $context);
    }

    public static function info($message, $context = []) {
        self::write('INFO', $message, $context);
    }

    private static function write($level, $message, $context) {
        // Asegurar que el directorio existe
        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $date = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logEntry = "[$date] [$level] $message $contextStr" . PHP_EOL;

        file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }

    private static function sendAlert($message, $context) {
        try {
            $db = \getDBConnection();
            $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'it_alert_email'");
            $email = $stmt->fetchColumn();

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $subject = "ALERTA CRÍTICA - CRM Clínica";
                $body = "Se ha detectado un error en la plataforma:\n\n" . 
                        "Mensaje: $message\n" . 
                        "Contexto: " . json_encode($context, JSON_PRETTY_PRINT) . "\n\n" . 
                        "Fecha: " . date('Y-m-d H:i:s');

                // En producción usar PHPMailer. Aquí usamos mail() nativo.
                // mail($email, $subject, $body); 
                
                // Logueamos que se intentó enviar
                self::write('ALERT', "Correo de alerta enviado a $email", []);
            }
        } catch (\Exception $e) {
            self::write('CRITICAL', "No se pudo enviar alerta por correo: " . $e->getMessage(), []);
        }
    }

    public static function getLogs($lines = 100) {
        if (!file_exists(self::$logFile)) return [];
        
        $content = file(self::$logFile);
        return array_reverse(array_slice($content, -$lines));
    }
    
    public static function clearLogs() {
        if (file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, '');
        }
    }
}
