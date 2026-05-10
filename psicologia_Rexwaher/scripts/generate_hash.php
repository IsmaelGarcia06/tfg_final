<?php
// Script auxiliar para generar un hash válido
$password = '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";
