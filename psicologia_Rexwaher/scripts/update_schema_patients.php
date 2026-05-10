<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDBConnection();
    echo "Conectado a la BD.\n";

    // 1. Añadir columnas a patients si no existen
    $columns = $pdo->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('surname', $columns)) {
        $pdo->exec("ALTER TABLE patients ADD COLUMN surname VARCHAR(100) AFTER name");
        echo "Columna 'surname' añadida.\n";
    }
    if (!in_array('dni', $columns)) {
        $pdo->exec("ALTER TABLE patients ADD COLUMN dni VARCHAR(20) AFTER surname");
        echo "Columna 'dni' añadida.\n";
    }
    if (!in_array('address', $columns)) {
        $pdo->exec("ALTER TABLE patients ADD COLUMN address TEXT AFTER phone");
        echo "Columna 'address' añadida.\n";
    }

    // 2. Tabla de Notas del Paciente
    $pdo->exec("CREATE TABLE IF NOT EXISTS patient_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        professional_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (professional_id) REFERENCES users(id)
    )");
    echo "Tabla 'patient_notes' verificada.\n";

    // 3. Tabla de Documentos
    // Puede estar asociado a una nota (note_id) o ser general (note_id NULL)
    $pdo->exec("CREATE TABLE IF NOT EXISTS patient_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        note_id INT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_type VARCHAR(50),
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (note_id) REFERENCES patient_notes(id) ON DELETE SET NULL
    )");
    echo "Tabla 'patient_documents' verificada.\n";

    echo "Actualización completada con éxito.";

} catch (PDOException $e) {
    die("Error SQL: " . $e->getMessage());
}
