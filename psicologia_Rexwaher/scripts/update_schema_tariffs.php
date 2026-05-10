<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDBConnection();
    echo "Conectado a la BD.\n";

    // 1. Tabla de Tipos de Tarifas
    $pdo->exec("CREATE TABLE IF NOT EXISTS tariffs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        percentage DECIMAL(5,2) NOT NULL, -- Ej: 15.00
        description VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Tabla 'tariffs' creada.\n";

    // 2. Tabla Histórica de Tarifas por Profesional
    $pdo->exec("CREATE TABLE IF NOT EXISTS professional_tariffs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tariff_id INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NULL, -- NULL significa 'hasta hoy/siempre'
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (tariff_id) REFERENCES tariffs(id)
    )");
    echo "Tabla 'professional_tariffs' creada.\n";

    // 3. Migración inicial (Opcional): Crear una tarifa base y asignarla a los actuales
    // Si ya existen usuarios con comisión, creamos una tarifa 'Legacy' para ellos
    $stmt = $pdo->query("SELECT id, commission_percentage FROM users WHERE role = 'professional' AND commission_percentage > 0");
    $users = $stmt->fetchAll();

    if (count($users) > 0) {
        echo "Migrando usuarios existentes...\n";
        foreach ($users as $u) {
            // Crear tarifa personalizada para este usuario (o reutilizar si ya existe lógica compleja)
            $stmtT = $pdo->prepare("INSERT INTO tariffs (name, percentage) VALUES (:name, :perc)");
            $stmtT->execute(['name' => 'Tarifa Inicial - Usuario ' . $u['id'], 'perc' => $u['commission_percentage']]);
            $tariffId = $pdo->lastInsertId();

            // Asignar desde el principio de los tiempos
            $stmtPT = $pdo->prepare("INSERT INTO professional_tariffs (user_id, tariff_id, start_date) VALUES (:uid, :tid, '2020-01-01')");
            $stmtPT->execute(['uid' => $u['id'], 'tid' => $tariffId]);
        }
    }

    echo "Actualización de tarifas completada.";

} catch (PDOException $e) {
    die("Error SQL: " . $e->getMessage());
}
