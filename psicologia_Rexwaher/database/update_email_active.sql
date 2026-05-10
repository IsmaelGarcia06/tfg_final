USE psicologia_crm;

-- Añadir estado activo/inactivo a las plantillas
ALTER TABLE email_templates ADD COLUMN active BOOLEAN DEFAULT 1 AFTER name;
