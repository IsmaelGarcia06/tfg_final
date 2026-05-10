<?php

namespace Src\Services;

class PasswordPolicy {
    
    // Lista básica de contraseñas comunes a bloquear
    private static $commonPasswords = [
        '123456', '12345678', '123456789', 'password', 'contraseña', 'qwerty', 
        '12345', '111111', '123123', 'admin', 'admin123', 'clinica', 'psicologia'
    ];

    public static function validate($password) {
        // 1. Longitud mínima
        if (strlen($password) < 12) {
            return ['valid' => false, 'message' => 'La contraseña debe tener al menos 12 caracteres.'];
        }

        // 2. Bloqueo de contraseñas comunes
        if (in_array(strtolower($password), self::$commonPasswords)) {
            return ['valid' => false, 'message' => 'Esa contraseña es demasiado común y fácil de adivinar.'];
        }

        return ['valid' => true];
    }
}
