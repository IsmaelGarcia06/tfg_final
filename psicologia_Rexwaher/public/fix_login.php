<?php
require_once __DIR__ . '/../config/db.php';

echo "<h1>Diagnóstico y Reparación de Login</h1>";

try {
    $pdo = getDBConnection();
    echo "<p style='color:green'>Conexión a base de datos exitosa.</p>";
    
    $email = 'admin@test.com';
    $password = '123456';
    
    // 1. Buscar usuario
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p style='color:red'>El usuario $email NO existe en la base de datos.</p>";
        echo "<p>Creando usuario...</p>";
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, active) VALUES ('Admin Test', :email, :hash, 'admin', 1)");
        $stmt->execute(['email' => $email, 'hash' => $hash]);
        
        echo "<p style='color:green'>Usuario creado correctamente.</p>";
    } else {
        echo "<p>El usuario existe. ID: " . $user['id'] . "</p>";
        echo "<p>Hash actual en BD: " . $user['password_hash'] . "</p>";
        
        // 2. Verificar contraseña actual
        if (password_verify($password, $user['password_hash'])) {
            echo "<p style='color:green'>La contraseña actual ES VÁLIDA.</p>";
        } else {
            echo "<p style='color:orange'>La contraseña actual NO coincide con el hash.</p>";
            echo "<p>Actualizando contraseña a: $password ...</p>";
            
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $update->execute(['hash' => $newHash, 'id' => $user['id']]);
            
            echo "<p style='color:green'>Contraseña actualizada correctamente.</p>";
            echo "<p>Nuevo Hash: $newHash</p>";
        }
    }
    
    echo "<h3><a href='/psicologia_Rexwaher/login'>Intentar Login Ahora</a></h3>";

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
