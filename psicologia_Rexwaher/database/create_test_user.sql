USE psicologia_crm;

-- Script para crear un usuario administrador de prueba
-- Usuario: admin@test.com
-- Contraseña: 123456

-- Primero borramos si existe para evitar duplicados o conflictos de hash
DELETE FROM users WHERE email = 'admin@test.com';

INSERT INTO users (
    name,
    email,
    password_hash,
    role,
    active,
    created_at,
    updated_at
) VALUES (
    'Administrador Prueba',
    'admin@test.com',
    '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', -- Hash bcrypt válido para '123456'
    'admin',
    1,
    NOW(),
    NOW()
);
