USE psicologia_crm;

-- 1. Tabla de Plantillas de Email
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE, -- Ej: 'reminder_24h', 'welcome'
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL, -- Contenido HTML/Texto
    variables_help VARCHAR(255) NULL, -- Ayuda sobre variables disponibles
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Tabla de Cola de Correos (Log de envíos)
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NULL,
    recipient_email VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    scheduled_at DATETIME NOT NULL, -- Cuándo se debe enviar
    sent_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

-- 3. Insertar Plantillas por Defecto
INSERT IGNORE INTO email_templates (code, name, subject, body, variables_help) VALUES
('reminder_24h', 'Recordatorio Cita (24h)', 'Recordatorio de su cita mañana', 'Hola {{patient_name}},\n\nLe recordamos que tiene una cita programada para mañana {{date}} a las {{time}}.\n\nPor favor, si no puede asistir, avísenos con antelación.\n\nSaludos,\nClínica Rexwaher', '{{patient_name}}, {{date}}, {{time}}'),
('welcome', 'Bienvenida Paciente', 'Bienvenido a nuestra clínica', 'Hola {{patient_name}},\n\nGracias por confiar en nosotros. Hemos creado su ficha de paciente correctamente.\n\nAtentamente,\nEl equipo.', '{{patient_name}}');
