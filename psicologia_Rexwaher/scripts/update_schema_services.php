<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDBConnection();
    echo "Conectado a la BD.\n";

    // 1. Tabla de Servicios (Antes Tarifas, pero con otro enfoque)
    // Si ya existe tariffs, la renombramos o creamos services aparte.
    // Para evitar conflictos con lo anterior, crearemos 'services' limpia.
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        type ENUM('session', 'pack') NOT NULL DEFAULT 'session',
        session_count INT DEFAULT 1, -- 1 para individual, >1 para bonos
        duration_minutes INT DEFAULT 60,
        active BOOLEAN DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Tabla 'services' creada.\n";

    // 2. Tabla de Bonos Comprados por Pacientes
    $pdo->exec("CREATE TABLE IF NOT EXISTS patient_packs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        service_id INT NOT NULL, -- El bono original comprado
        sessions_total INT NOT NULL,
        sessions_used INT DEFAULT 0,
        price_paid DECIMAL(10,2) NOT NULL,
        purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'completed', 'expired') DEFAULT 'active',
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id)
    )");
    echo "Tabla 'patient_packs' creada.\n";

    // 3. Actualizar Sesiones para vincularlas a un servicio o bono
    // Añadimos columnas si no existen
    $columns = $pdo->query("SHOW COLUMNS FROM sessions")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('service_id', $columns)) {
        $pdo->exec("ALTER TABLE sessions ADD COLUMN service_id INT NULL AFTER professional_id");
        $pdo->exec("ALTER TABLE sessions ADD CONSTRAINT fk_session_service FOREIGN KEY (service_id) REFERENCES services(id)");
        echo "Columna 'service_id' añadida a sessions.\n";
    }
    
    if (!in_array('patient_pack_id', $columns)) {
        $pdo->exec("ALTER TABLE sessions ADD COLUMN patient_pack_id INT NULL AFTER service_id");
        $pdo->exec("ALTER TABLE sessions ADD CONSTRAINT fk_session_pack FOREIGN KEY (patient_pack_id) REFERENCES patient_packs(id)");
        echo "Columna 'patient_pack_id' añadida a sessions.\n";
    }

    // 4. Datos de Ejemplo
    $count = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO services (name, price, type, session_count, duration_minutes) VALUES 
            ('Sesión Individual', 60.00, 'session', 1, 60),
            ('Bono 5 Sesiones', 250.00, 'pack', 5, 60),
            ('Bono 10 Sesiones', 450.00, 'pack', 10, 60),
            ('Primera Consulta', 50.00, 'session', 1, 90)
        ");
        echo "Servicios de ejemplo insertados.\n";
    }

    echo "Actualización de Servicios completada.";

} catch (PDOException $e) {
    die("Error SQL: " . $e->getMessage());
}
