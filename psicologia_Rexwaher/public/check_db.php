<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDBConnection();
    echo "<h1>Diagnóstico de Base de Datos</h1>";

    // Verificar tabla patient_members
    $stmt = $pdo->query("SHOW TABLES LIKE 'patient_members'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ La tabla 'patient_members' EXISTE.</p>";
        
        echo "<h3>Columnas:</h3><ul>";
        $cols = $pdo->query("SHOW COLUMNS FROM patient_members")->fetchAll();
        foreach ($cols as $col) {
            echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red'>❌ La tabla 'patient_members' NO EXISTE.</p>";
        echo "<p>Debes ejecutar el script de migración.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Error de conexión: " . $e->getMessage() . "</p>";
}
