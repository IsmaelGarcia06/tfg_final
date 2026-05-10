USE psicologia_crm;

-- ==========================================
-- 1. ACTUALIZACIÓN DE PACIENTES
-- ==========================================

-- Añadir nuevos campos a la tabla patients
-- Si ya existen, estas líneas darán error, puedes ignorarlo.
ALTER TABLE patients ADD COLUMN surname VARCHAR(100) AFTER name;
ALTER TABLE patients ADD COLUMN dni VARCHAR(20) AFTER surname;
ALTER TABLE patients ADD COLUMN address TEXT AFTER phone;

-- ==========================================
-- 2. NOTAS Y DOCUMENTOS
-- ==========================================

-- Tabla de Notas Clínicas
CREATE TABLE IF NOT EXISTS patient_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    professional_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES users(id)
);

-- Tabla de Documentos Adjuntos
CREATE TABLE IF NOT EXISTS patient_documents (
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

-- ==========================================
-- 3. SISTEMA DE TARIFAS HISTÓRICAS
-- ==========================================

-- Tabla de Tipos de Tarifas
CREATE TABLE IF NOT EXISTS tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL, -- Ej: 15.00
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla Histórica de Asignación
CREATE TABLE IF NOT EXISTS professional_tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tariff_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL, -- NULL significa 'hasta hoy/siempre'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tariff_id) REFERENCES tariffs(id)
);

-- ==========================================
-- 4. DATOS DE EJEMPLO (OPCIONAL)
-- ==========================================

-- Insertar algunas tarifas base para empezar
INSERT INTO tariffs (name, percentage, description) VALUES
('Estándar', 15.00, 'Comisión base para nuevos profesionales'),
('Socio', 20.00, 'Comisión para socios o senior'),
('Especial', 10.00, 'Tarifa reducida promocional');
