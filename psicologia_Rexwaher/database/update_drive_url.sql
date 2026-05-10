USE psicologia_crm;

-- Añadir campo para URL de Google Drive
ALTER TABLE patients ADD COLUMN drive_folder_url VARCHAR(255) NULL AFTER referred_by;
