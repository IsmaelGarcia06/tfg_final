SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar tablas existentes
DROP TABLE IF EXISTS email_queue;
DROP TABLE IF EXISTS email_templates;
DROP TABLE IF EXISTS patient_documents;
DROP TABLE IF EXISTS patient_notes;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS patient_packs;
DROP TABLE IF EXISTS professional_tariffs;
DROP TABLE IF EXISTS tariffs;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'professional') NOT NULL DEFAULT 'professional',
    calendar_color VARCHAR(7) DEFAULT '#3788d8',
    google_calendar_id VARCHAR(255) DEFAULT 'primary',
    google_refresh_token VARCHAR(255) NULL,
    google_access_token TEXT NULL,
    google_token_expires_at DATETIME NULL,
    active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Tarifas (Tipos de Comisión)
CREATE TABLE tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Historial de Comisiones Profesionales
CREATE TABLE professional_tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tariff_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tariff_id) REFERENCES tariffs(id)
);

-- 4. Servicios (Productos)
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    type ENUM('session', 'pack') NOT NULL DEFAULT 'session',
    session_count INT DEFAULT 1,
    duration_minutes INT DEFAULT 60,
    active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 5. Pacientes
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professional_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100),
    dni VARCHAR(20),
    email VARCHAR(150),
    phone VARCHAR(50),
    address TEXT,
    status ENUM('open', 'closed') DEFAULT 'open',
    last_session_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- 6. Bonos de Pacientes
CREATE TABLE patient_packs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    service_id INT NOT NULL,
    sessions_total INT NOT NULL,
    sessions_used INT DEFAULT 0,
    price_paid DECIMAL(10,2) NOT NULL,
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'expired') DEFAULT 'active',
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- 7. Sesiones
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    professional_id INT NOT NULL,
    service_id INT NULL,
    patient_pack_id INT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    notes TEXT NULL,
    google_event_id VARCHAR(255) NULL,
    fee_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (patient_pack_id) REFERENCES patient_packs(id)
);

-- 8. Notas de Pacientes
CREATE TABLE patient_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    professional_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES users(id)
);

-- 9. Documentos de Pacientes
CREATE TABLE patient_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    note_id INT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (note_id) REFERENCES patient_notes(id) ON DELETE SET NULL
);

-- 10. Plantillas de Email
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    variables_help VARCHAR(255) NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 11. Cola de Emails
CREATE TABLE email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NULL,
    recipient_email VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    scheduled_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

-- 12. Logs de Auditoría
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- DATOS INICIALES

-- Usuario Admin (admin@test.com / 123456)
INSERT INTO users (name, email, password_hash, role, active) VALUES
('Administrador', 'admin@test.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'admin', 1);

-- Servicios Base
INSERT INTO services (name, price, type, session_count, duration_minutes) VALUES
('Sesión Individual', 60.00, 'session', 1, 60),
('Bono 5 Sesiones', 250.00, 'pack', 5, 60),
('Bono 10 Sesiones', 450.00, 'pack', 10, 60),
('Primera Consulta', 50.00, 'session', 1, 90);

-- Tipos de Comisión Base
INSERT INTO tariffs (name, percentage, description) VALUES
('Estándar', 20.00, 'Comisión base para la clínica'),
('Socio', 15.00, 'Comisión reducida');

-- Plantillas de Email Base
INSERT INTO email_templates (code, name, subject, body, variables_help) VALUES
('reminder_24h', 'Recordatorio Cita (24h)', 'Recordatorio de su cita mañana', 'Hola {{patient_name}},\n\nLe recordamos que tiene una cita programada para mañana {{date}} a las {{time}}.\n\nPor favor, si no puede asistir, avísenos con antelación.\n\nSaludos,\nClínica Rexwaher', '{{patient_name}}, {{date}}, {{time}}'),
('welcome', 'Bienvenida Paciente', 'Bienvenido a nuestra clínica', 'Hola {{patient_name}},\n\nGracias por confiar en nosotros. Hemos creado su ficha de paciente correctamente.\n\nAtentamente,\nEl equipo.', '{{patient_name}}');
