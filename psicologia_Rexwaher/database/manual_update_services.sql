USE psicologia_crm;

-- ==========================================
-- 1. TABLA DE SERVICIOS (PRODUCTOS)
-- ==========================================
-- Define qué vende la clínica: Sesiones sueltas o Bonos

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    type ENUM('session', 'pack') NOT NULL DEFAULT 'session',
    session_count INT DEFAULT 1, -- 1 para individual, >1 para bonos
    duration_minutes INT DEFAULT 60,
    active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 2. TABLA DE BONOS COMPRADOS (PACKS)
-- ==========================================
-- Registra qué paciente ha comprado qué bono y cuántas sesiones le quedan

CREATE TABLE IF NOT EXISTS patient_packs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    service_id INT NOT NULL, -- El bono original comprado
    sessions_total INT NOT NULL,
    sessions_used INT DEFAULT 0,
    price_paid DECIMAL(10,2) NOT NULL,
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'expired') DEFAULT 'active',
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- ==========================================
-- 3. ACTUALIZACIÓN DE LA TABLA DE SESIONES
-- ==========================================
-- Vincula cada cita a un servicio o a un bono específico

-- Añadir columna service_id si no existe
SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = 'psicologia_crm' AND table_name = 'sessions' AND column_name = 'service_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE sessions ADD COLUMN service_id INT NULL AFTER professional_id, ADD CONSTRAINT fk_session_service FOREIGN KEY (service_id) REFERENCES services(id)', 'SELECT "Columna service_id ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Añadir columna patient_pack_id si no existe
SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = 'psicologia_crm' AND table_name = 'sessions' AND column_name = 'patient_pack_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE sessions ADD COLUMN patient_pack_id INT NULL AFTER service_id, ADD CONSTRAINT fk_session_pack FOREIGN KEY (patient_pack_id) REFERENCES patient_packs(id)', 'SELECT "Columna patient_pack_id ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- ==========================================
-- 4. DATOS DE EJEMPLO (SERVICIOS BÁSICOS)
-- ==========================================

INSERT INTO services (name, price, type, session_count, duration_minutes) VALUES
('Sesión Individual', 60.00, 'session', 1, 60),
('Bono 5 Sesiones', 250.00, 'pack', 5, 60),
('Bono 10 Sesiones', 450.00, 'pack', 10, 60),
('Primera Consulta', 50.00, 'session', 1, 90);
