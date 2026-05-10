USE psicologia_crm;

-- 1. Añadir rol 'it_admin'
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'professional', 'it_admin') NOT NULL DEFAULT 'professional';

-- 2. Tabla de Configuración del Sistema (Global)
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Insertar configuración por defecto
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('it_alert_email', 'soporte@tuempresa.com');

-- 4. Crear un usuario IT Admin por defecto (Password: 123456)
INSERT INTO users (name, email, password_hash, role, active)
VALUES ('Soporte IT', 'it@admin.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'it_admin', 1);
