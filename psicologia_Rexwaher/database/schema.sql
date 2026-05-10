-- Base de datos para CRM Clínica Psicología
-- Versión mejorada con soporte completo para OAuth, Templates y Optimización

CREATE DATABASE IF NOT EXISTS psicologia_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE psicologia_crm;

-- 1. Tabla de Usuarios (Profesionales, Gerentes, Admin)
-- Modelado de Profesional: Se usa la misma tabla con rol 'professional'.
-- Se añaden campos específicos para integración con Google y UI.
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'professional') NOT NULL DEFAULT 'professional',

    -- Configuración UI
    calendar_color VARCHAR(7) DEFAULT '#3788d8', -- Color hexadecimal para el calendario

    -- Configuración Económica
    commission_percentage DECIMAL(5,2) DEFAULT 0.00, -- Porcentaje de comisión del profesional (0-100)

    -- Integración Google Calendar (OAuth2)
    google_calendar_id VARCHAR(255) DEFAULT 'primary', -- ID del calendario donde sincronizar
    google_refresh_token VARCHAR(255) NULL,            -- Token de larga duración
    google_access_token TEXT NULL,                     -- Token de corta duración
    google_token_expires_at DATETIME NULL,             -- Expiración del access token

    active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Tabla de Pacientes
-- Estado: 'open' (activo), 'closed' (alta/abandono).
-- Optimización "Sin sesión 15 días": Se desnormaliza 'last_session_at'.
-- Cada vez que se completa una sesión, se actualiza este campo.
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professional_id INT NOT NULL, -- El profesional responsable
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(50),
    status ENUM('open', 'closed') DEFAULT 'open',

    -- Campo clave para consulta eficiente de inactividad
    last_session_at DATETIME NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- 3. Tabla de Sesiones (Citas)
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    professional_id INT NOT NULL,

    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,

    -- Estados:
    -- scheduled: Programada
    -- completed: Realizada (cuenta para facturación y actualiza last_session_at)
    -- cancelled: Cancelada con aviso
    -- no_show: Paciente no apareció (puede cobrarse o no)
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',

    notes TEXT NULL, -- Notas clínicas privadas (encriptar en app si es necesario)

    -- Integración Google
    google_event_id VARCHAR(255) NULL, -- Mapeo bidireccional

    -- Economía
    fee_amount DECIMAL(10,2) DEFAULT 0.00, -- Precio base de la sesión
    manager_fee_percentage DECIMAL(5,2) DEFAULT 0.00, -- % para la clínica (snapshot)

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- 4. Plantillas de Email
-- Permite al Admin/Manager editar los textos de los correos.
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE, -- Ej: 'reminder_24h', 'welcome_patient'
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL, -- Soporta HTML básico
    variables_help VARCHAR(255) NULL, -- Ej: "{{patient_name}}, {{date}}"
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. Cola de Emails (Logs y Programación)
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,

    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    error_message TEXT NULL,

    send_after DATETIME NOT NULL, -- Programación (ej. 24h antes de la cita)
    sent_at DATETIME NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 6. Logs de Auditoría
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(50) NOT NULL, -- Ej: 'create', 'update', 'delete', 'login'
    entity_type VARCHAR(50) NOT NULL, -- Ej: 'patient', 'session'
    entity_id INT NOT NULL,
    details JSON NULL, -- Cambios específicos (old_value, new_value)
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Índices para optimización de consultas frecuentes

-- Buscar pacientes de un profesional
CREATE INDEX idx_patients_prof ON patients(professional_id, status);

-- Query "Sin sesión en 15 días":
-- SELECT * FROM patients WHERE professional_id = X AND status = 'open' AND last_session_at < DATE_SUB(NOW(), INTERVAL 15 DAY);
CREATE INDEX idx_patients_inactivity ON patients(professional_id, status, last_session_at);

-- Calendario: Buscar sesiones por rango de fechas y profesional
CREATE INDEX idx_sessions_calendar ON sessions(professional_id, start_time, end_time);

-- Cola de correo: Buscar pendientes
CREATE INDEX idx_email_queue_process ON email_queue(status, send_after);
