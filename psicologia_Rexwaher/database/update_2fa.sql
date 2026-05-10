USE psicologia_crm;

ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL AFTER password_hash;
ALTER TABLE users ADD COLUMN two_factor_enabled BOOLEAN DEFAULT 0 AFTER two_factor_secret;
