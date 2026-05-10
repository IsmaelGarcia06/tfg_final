USE psicologia_crm;

-- ================================================================
-- SCRIPT DE DATOS DE PRUEBA PARA COMISIONES Y GRÁFICOS
-- ================================================================
-- Este script genera datos históricos para que puedas ver
-- cómo funciona el cálculo de comisiones y los gráficos.

-- 1. CREAR UN PROFESIONAL DE PRUEBA
INSERT INTO users (name, email, password_hash, role, active)
VALUES ('Dr. Ejemplo Gráficos', 'graficos@test.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'professional', 1);

SET @prof_id = LAST_INSERT_ID();

-- 2. CREAR UN PACIENTE DE PRUEBA
INSERT INTO patients (professional_id, name, surname, email, status)
VALUES (@prof_id, 'Paciente', 'Ficticio', 'paciente@test.com', 'open');

SET @patient_id = LAST_INSERT_ID();

-- 3. CREAR TIPOS DE COMISIÓN (TARIFAS)
INSERT INTO tariffs (name, percentage, description) VALUES
('Comisión Base 2023', 20.00, 'Tarifa antigua'),
('Comisión 2024', 30.00, 'Tarifa actual subida');

SET @t_20 = (SELECT id FROM tariffs WHERE percentage = 20.00 LIMIT 1);
SET @t_30 = (SELECT id FROM tariffs WHERE percentage = 30.00 LIMIT 1);

-- 4. ASIGNAR HISTORIAL DE TARIFAS AL PROFESIONAL
-- Enero y Febrero 2024: Cobraba el 20%
INSERT INTO professional_tariffs (user_id, tariff_id, start_date, end_date)
VALUES (@prof_id, @t_20, '2024-01-01', '2024-02-29');

-- Marzo 2024 en adelante: Cobra el 30%
INSERT INTO professional_tariffs (user_id, tariff_id, start_date, end_date)
VALUES (@prof_id, @t_30, '2024-03-01', NULL);

-- 5. OBTENER SERVICIOS (Asumiendo que ejecutaste el script anterior)
-- Si no existen, los creamos al vuelo para evitar errores
INSERT IGNORE INTO services (name, price, type) VALUES ('Terapia Test', 60.00, 'session');
SET @service_id = (SELECT id FROM services WHERE name = 'Terapia Test' LIMIT 1);

-- 6. GENERAR SESIONES COMPLETADAS (HISTÓRICO)

-- ENERO: 2 Sesiones (Tarifa 20%) -> 120€ Facturado -> 24€ Prof -> 96€ Clínica
INSERT INTO sessions (patient_id, professional_id, service_id, start_time, end_time, status, fee_amount)
VALUES
(@patient_id, @prof_id, @service_id, '2024-01-10 10:00:00', '2024-01-10 11:00:00', 'completed', 60.00),
(@patient_id, @prof_id, @service_id, '2024-01-20 16:00:00', '2024-01-20 17:00:00', 'completed', 60.00);

-- FEBRERO: 1 Sesión (Tarifa 20%) -> 60€ Facturado -> 12€ Prof -> 48€ Clínica
INSERT INTO sessions (patient_id, professional_id, service_id, start_time, end_time, status, fee_amount)
VALUES
(@patient_id, @prof_id, @service_id, '2024-02-15 10:00:00', '2024-02-15 11:00:00', 'completed', 60.00);

-- MARZO: 3 Sesiones (Tarifa 30%) -> 180€ Facturado -> 54€ Prof -> 126€ Clínica
INSERT INTO sessions (patient_id, professional_id, service_id, start_time, end_time, status, fee_amount)
VALUES
(@patient_id, @prof_id, @service_id, '2024-03-05 10:00:00', '2024-03-05 11:00:00', 'completed', 60.00),
(@patient_id, @prof_id, @service_id, '2024-03-12 11:00:00', '2024-03-12 12:00:00', 'completed', 60.00),
(@patient_id, @prof_id, @service_id, '2024-03-25 16:00:00', '2024-03-25 17:00:00', 'completed', 60.00);

-- ABRIL (Futuro/Presente): 1 Sesión (Tarifa 30%)
INSERT INTO sessions (patient_id, professional_id, service_id, start_time, end_time, status, fee_amount)
VALUES
(@patient_id, @prof_id, @service_id, DATE_ADD(NOW(), INTERVAL -1 DAY), DATE_ADD(NOW(), INTERVAL -1 DAY), 'completed', 60.00);

SELECT "Datos de prueba generados correctamente. Revisa la sección de Economía." as Mensaje;
