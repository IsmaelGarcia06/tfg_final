USE psicologia_crm;

-- Tabla para registrar intentos de login fallidos
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL, -- Soporta IPv6
    username VARCHAR(100) NOT NULL,
    attempt_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Índices para búsquedas rápidas por tiempo e IP/Usuario
    INDEX idx_ip_time (ip_address, attempt_at),
    INDEX idx_user_time (username, attempt_at)
);
