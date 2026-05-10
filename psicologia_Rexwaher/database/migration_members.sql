USE psicologia_crm;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Crear tabla de Miembros (Las personas reales)
CREATE TABLE IF NOT EXISTS patient_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100),
    birth_date DATE,
    occupation VARCHAR(100),
    email VARCHAR(150),
    phone VARCHAR(50),
    dni VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- 2. Migrar datos existentes (Asumimos que todos los actuales son Individuales)
INSERT INTO patient_members (patient_id, name, surname, email, phone, dni)
SELECT id, name, surname, email, phone, dni FROM patients;

-- 3. Modificar tabla Pacientes (Ahora es el "Expediente" o "Caso")
ALTER TABLE patients
    ADD COLUMN type ENUM('individual', 'couple', 'family') DEFAULT 'individual' AFTER professional_id,
    ADD COLUMN entry_date DATE AFTER type,
    ADD COLUMN closure_date DATE AFTER entry_date,
    ADD COLUMN referred_by VARCHAR(150) AFTER closure_date,
    -- Actualizamos el ENUM de estado
    MODIFY COLUMN status ENUM('open', 'closed', 'reopened', 'dropout', 'no_show', 'suspension') DEFAULT 'open';

-- 4. Actualizar el "Nombre del Caso" en la tabla patients
-- Para individuales, ya está bien. Pero aseguramos que la columna 'name' ahora represente el "Display Name" del caso.
-- (No borramos name, surname, email, phone de patients todavía para no romper compatibilidad inmediata con otras vistas,
-- pero idealmente deberían quedar obsoletos o usarse como "Contacto Principal").

UPDATE patients SET entry_date = DATE(created_at) WHERE entry_date IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
