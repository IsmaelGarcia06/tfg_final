<?php

// Configuración de Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'psicologia_crm');
define('DB_USER', 'root'); // Cambiar en producción
define('DB_PASS', 'admin');     // Cambiar en producción
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            // En producción, loguear error y mostrar mensaje genérico
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    
    return $pdo;
}
