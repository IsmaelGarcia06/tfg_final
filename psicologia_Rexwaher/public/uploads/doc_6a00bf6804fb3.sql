-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         5.5.50 - MySQL Community Server (GPL)
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.13.0.7147
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para psicologia_crm
CREATE DATABASE IF NOT EXISTS `psicologia_crm` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `psicologia_crm`;

-- Volcando estructura para tabla psicologia_crm.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int(10) unsigned NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_audit_user_final` (`user_id`),
  CONSTRAINT `fk_audit_user_final` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla psicologia_crm.audit_logs: ~0 rows (aproximadamente)

-- Volcando estructura para tabla psicologia_crm.audit_logs_v2
CREATE TABLE IF NOT EXISTS `audit_logs_v2` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_action` (`action`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_audit_user_v2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla psicologia_crm.audit_logs_v2: ~0 rows (aproximadamente)

-- Volcando estructura para tabla psicologia_crm.email_queue
CREATE TABLE IF NOT EXISTS `email_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned DEFAULT NULL,
  `recipient_email` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `error_message` text,
  `scheduled_at` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_email_patient_final` (`patient_id`),
  CONSTRAINT `fk_email_patient_final` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Volcando datos para la tabla psicologia_crm.email_queue: ~0 rows (aproximadamente)

-- Volcando estructura para tabla psicologia_crm.email_templates
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `variables_help` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- Volcando datos para la tabla psicologia_crm.email_templates: ~2 rows (aproximadamente)
REPLACE INTO `email_templates` (`id`, `code`, `name`, `active`, `subject`, `body`, `variables_help`, `updated_at`) VALUES
	(1, 'reminder_24h', 'Recordatorio Cita (24h)', 1, 'Recordatorio de su cita mañana', 'Hola {{patient_name}},\n\nLe recordamos que tiene una cita programada para mañana {{date}} a las {{time}}.', '{{patient_name}}, {{date}}, {{time}}', '2026-02-12 10:15:49'),
	(2, 'welcome', 'Bienvenida Paciente', 1, 'Bienvenido a nuestra clínica', 'Hola {{patient_name}},\n\nGracias por confiar en nosotros.', '{{patient_name}}', '2026-02-12 10:15:49');

-- Volcando estructura para tabla psicologia_crm.login_attempts
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempt_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempt_at`),
  KEY `idx_user_time` (`username`,`attempt_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla psicologia_crm.login_attempts: ~0 rows (aproximadamente)

-- Volcando estructura para tabla psicologia_crm.password_resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla psicologia_crm.password_resets: ~0 rows (aproximadamente)

-- Volcando estructura para tabla psicologia_crm.patient_documents
CREATE TABLE IF NOT EXISTS `patient_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `note_id` int(10) unsigned DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_docs_pat_final` (`patient_id`),
  KEY `fk_docs_note_final` (`note_id`),
  CONSTRAINT `fk_docs_note_final` FOREIGN KEY (`note_id`) REFERENCES `patient_notes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_docs_pat_final` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- Volcando datos para la tabla psicologia_crm.patient_documents: ~1 rows (aproximadamente)
REPLACE INTO `patient_documents` (`id`, `patient_id`, `note_id`, `file_name`, `file_path`, `file_type`, `uploaded_at`) VALUES
	(1, 2, NULL, 'imagen-de-prueba-320x240-1.jpg', 'doc_69a68a7b938bc.jpg', 'image/jpeg', NULL);

-- Volcando estructura para tabla psicologia_crm.patient_members
CREATE TABLE IF NOT EXISTS `patient_members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pm_patient_final` (`patient_id`),
  CONSTRAINT `fk_pm_patient_final` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

-- Volcando datos para la tabla psicologia_crm.patient_members: ~5 rows (aproximadamente)
REPLACE INTO `patient_members` (`id`, `patient_id`, `name`, `surname`, `birth_date`, `occupation`, `email`, `phone`, `dni`, `created_at`) VALUES
	(1, 1, 'PRUEBA', NULL, NULL, NULL, 'prueba@prueba.com', '123456', NULL, '2026-03-04 09:42:07'),
	(2, 2, 'Paciente', 'Ficticio', NULL, NULL, 'paciente@test.com', NULL, NULL, '2026-03-04 09:42:07'),
	(4, 2, 'prueba', 'prueba', '2002-04-23', 'estudiante', 'prueba@gmail.com', '456456456', NULL, NULL),
	(5, 3, 'prueba', 'prueba prueba', '2008-12-30', '', NULL, NULL, NULL, NULL),
	(6, 4, 'prueba', 'prueba prueba', '2008-12-30', '', NULL, NULL, NULL, NULL);

-- Volcando estructura para tabla psicologia_crm.patient_notes
CREATE TABLE IF NOT EXISTS `patient_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `professional_id` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notes_pat_final` (`patient_id`),
  KEY `fk_notes_prof_final` (`professional_id`),
  CONSTRAINT `fk_notes_pat_final` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notes_prof_final` FOREIGN KEY (`professional_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- Volcando datos para la tabla psicologia_crm.patient_notes: ~2 rows (aproximadamente)
REPLACE INTO `patient_notes` (`id`, `patient_id`, `professional_id`, `content`, `created_at`) VALUES
	(1, 2, 1, 'prueba', NULL),
	(2, 2, 1, 'prueba2', NULL);

-- Volcando estructura para tabla psicologia_crm.patient_packs
CREATE TABLE IF NOT EXISTS `patient_packs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `service_id` int(10) unsigned NOT NULL,
  `sessions_total` int(11) NOT NULL,
  `sessions_used` int(11) DEFAULT '0',
  `price_paid` decimal(10,2) NOT NULL,
  `purchase_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','completed','expired') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `fk_pp_patient_final` (`patient_id`),
  KEY `fk_pp_service_final` (`service_id`),
  CONSTRAINT `fk_pp_patient_final` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pp_service_final` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- Volcando datos para la tabla psicologia_crm.patient_packs: ~1 rows (aproximadamente)
REPLACE INTO `patient_packs` (`id`, `patient_id`, `service_id`, `sessions_total`, `sessions_used`, `price_paid`, `purchase_date`, `status`) VALUES
	(1, 2, 3, 10, 1, 450.00, '2026-02-11 13:09:27', 'active');

-- Volcando estructura para tabla psicologia_crm.patients
CREATE TABLE IF NOT EXISTS `patients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `professional_id` int(10) unsigned NOT NULL,
  `type` enum('individual','couple','family') COLLATE utf8mb4_unicode_ci DEFAULT 'individual',
  `entry_date` date DEFAULT NULL,
  `closure_date` date DEFAULT NULL,
  `referred_by` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `drive_folder_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dni` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('open','closed','reopened','dropout','no_show','suspension') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `last_session_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pat_prof_final` (`professional_id`),
  CONSTRAINT `fk_pat_prof_final` FOREIGN KEY (`professional_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla psicologia_crm.patients: ~4 rows (aproximadamente)
REPLACE INTO `patients` (`id`, `professional_id`, `type`, `entry_date`, `closure_date`, `referred_by`, `drive_folder_url`, `name`, `surname`, `dni`, `email`, `phone`, `address`, `status`, `last_session_at`, `created_at`, `updated_at`) VALUES
	(1, 1, 'individual', NULL, NULL, NULL, NULL, 'Jose manuel', NULL, NULL, 'prueba@prueba.com', '123456', NULL, 'open', '2026-02-04 14:10:00', NULL, '2026-05-06 18:17:07'),
	(2, 2, 'individual', '2026-02-11', NULL, NULL, NULL, 'Lorena ramos', 'Ficticio', NULL, 'paciente@test.com', NULL, NULL, 'open', NULL, '2026-02-11 13:33:36', '2026-05-06 18:17:13'),
	(3, 1, 'individual', '2026-03-24', NULL, '', NULL, 'Javi ', NULL, NULL, NULL, NULL, NULL, 'open', NULL, NULL, '2026-05-06 18:19:26'),
	(4, 1, 'individual', '2026-03-24', NULL, '', NULL, 'Manuel molina', NULL, NULL, NULL, NULL, NULL, 'open', NULL, NULL, '2026-05-06 18:19:35');

-- Volcando estructura para tabla psicologia_crm.professional_tariffs
CREATE TABLE IF NOT EXISTS `professional_tariffs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `tariff_id` int(10) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_protar_user_final` (`user_id`),
  KEY `fk_protar_tariff_final` (`tariff_id`),
  CONSTRAINT `fk_protar_tariff_final` FOREIGN KEY (`tariff_id`) REFERENCES `tariffs` (`id`),
  CONSTRAINT `fk_protar_user_final` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- Volcando datos para la tabla psicologia_crm.professional_tariffs: ~2 rows (aproximadamente)
REPLACE INTO `professional_tariffs` (`id`, `user_id`, `tariff_id`, `start_date`, `end_date`, `created_at`) VALUES
	(1, 2, 1, '2024-01-01', '2024-02-29', '2026-02-11 13:33:36'),
	(2, 2, 2, '2024-03-01', NULL, '2026-02-11 13:33:36');

-- Volcando estructura para tabla psicologia_crm.services
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `type` enum('session','pack') NOT NULL DEFAULT 'session',
  `session_count` int(11) DEFAULT '1',
  `duration_minutes` int(11) DEFAULT '60',
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

-- Volcando datos para la tabla psicologia_crm.services: ~5 rows (aproximadamente)
REPLACE INTO `services` (`id`, `name`, `price`, `type`, `session_count`, `duration_minutes`, `active`, `created_at`) VALUES
	(1, 'Sesión Individual', 60.00, 'session', 1, 60, 1, '2026-02-11 12:20:25'),
	(2, 'Bono 5 Sesiones', 250.00, 'pack', 5, 60, 1, '2026-02-11 12:20:25'),
	(3, 'Bono 10 Sesiones', 450.00, 'pack', 10, 60, 1, '2026-02-11 12:20:25'),
	(4, 'Primera Consulta', 50.00, 'session', 1, 90, 1, '2026-02-11 12:20:25'),
	(5, 'Terapia Test', 60.00, 'session', 1, 60, 1, '2026-02-11 12:33:36');

-- Volcando estructura para tabla psicologia_crm.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `professional_id` int(10) unsigned NOT NULL,
  `service_id` int(10) unsigned DEFAULT NULL,
  `patient_pack_id` int(10) unsigned DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `google_event_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fee_amount` decimal(10,2) DEFAULT '0.00',
  `manager_fee_percentage` decimal(5,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sess_pat_final` (`patient_id`),
  KEY `fk_sess_prof_final` (`professional_id`),
  KEY `fk_sess_serv_final` (`service_id`),
  KEY `fk_sess_pack_final` (`patient_pack_id`),
  CONSTRAINT `fk_sess_pack_final` FOREIGN KEY (`patient_pack_id`) REFERENCES `patient_packs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sess_pat_final` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sess_prof_final` FOREIGN KEY (`professional_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_sess_serv_final` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla psicologia_crm.sessions: ~11 rows (aproximadamente)
REPLACE INTO `sessions` (`id`, `patient_id`, `professional_id`, `service_id`, `patient_pack_id`, `start_time`, `end_time`, `status`, `notes`, `google_event_id`, `fee_amount`, `manager_fee_percentage`, `created_at`, `updated_at`) VALUES
	(1, 2, 2, 5, NULL, '2024-01-10 10:00:00', '2024-01-10 11:00:00', 'completed', NULL, NULL, 60.00, 0.00, NULL, '2026-02-11 12:33:36'),
	(2, 2, 2, 5, NULL, '2024-01-20 16:00:00', '2024-01-20 17:00:00', 'completed', NULL, NULL, 60.00, 0.00, NULL, '2026-02-11 12:33:36'),
	(3, 2, 2, 5, NULL, '2024-02-15 10:00:00', '2024-02-15 11:00:00', 'completed', NULL, NULL, 60.00, 0.00, NULL, '2026-02-11 12:33:36'),
	(4, 2, 2, 5, NULL, '2024-03-05 10:00:00', '2024-03-05 11:00:00', 'completed', NULL, NULL, 60.00, 0.00, NULL, '2026-02-11 12:33:36'),
	(5, 2, 2, 5, NULL, '2024-03-12 11:00:00', '2024-03-12 12:00:00', 'completed', NULL, NULL, 60.00, 0.00, NULL, '2026-02-11 12:33:36'),
	(6, 2, 2, 5, NULL, '2024-03-25 16:00:00', '2024-03-25 17:00:00', 'completed', NULL, NULL, 60.00, 0.00, NULL, '2026-02-11 12:33:36'),
	(7, 2, 2, 5, NULL, '2026-02-10 13:33:36', '2026-02-10 13:33:36', 'completed', NULL, NULL, 60.00, 0.00, NULL, '2026-02-11 12:33:36'),
	(8, 2, 1, NULL, NULL, '2026-02-13 14:03:00', '2026-02-13 15:03:00', 'scheduled', '', NULL, 0.00, 0.00, NULL, '2026-02-11 13:03:54'),
	(9, 2, 1, NULL, 1, '2026-02-14 14:09:00', '2026-02-14 15:09:00', 'scheduled', '', NULL, 0.00, 0.00, NULL, '2026-02-11 13:09:56'),
	(10, 1, 1, NULL, NULL, '2026-02-04 14:10:00', '2026-02-04 15:10:00', 'completed', '', NULL, 0.00, 0.00, NULL, '2026-02-11 13:10:56'),
	(11, 3, 1, NULL, NULL, '2026-03-25 10:36:00', '2026-03-25 11:36:00', 'scheduled', '', 'jenqrh383m9ctahs384qc52n7g', 0.00, 0.00, NULL, '2026-03-24 09:36:15');

-- Volcando estructura para tabla psicologia_crm.system_settings
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla psicologia_crm.system_settings: ~1 rows (aproximadamente)
REPLACE INTO `system_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
	('it_alert_email', 'soporte@tuempresa.com', '2026-02-23 10:03:20');

-- Volcando estructura para tabla psicologia_crm.tariffs
CREATE TABLE IF NOT EXISTS `tariffs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- Volcando datos para la tabla psicologia_crm.tariffs: ~2 rows (aproximadamente)
REPLACE INTO `tariffs` (`id`, `name`, `percentage`, `description`, `created_at`) VALUES
	(1, 'Comisión Base 2023', 20.00, 'Tarifa antigua', '2026-02-11 13:33:36'),
	(2, 'Comisión 2024', 30.00, 'Tarifa actual subida', '2026-02-11 13:33:36');

-- Volcando estructura para tabla psicologia_crm.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `role` enum('admin','manager','professional','it_admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'professional',
  `calendar_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#3788d8',
  `commission_percentage` decimal(5,2) DEFAULT '0.00',
  `google_calendar_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'primary',
  `google_refresh_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_access_token` text COLLATE utf8mb4_unicode_ci,
  `google_token_expires_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla psicologia_crm.users: ~3 rows (aproximadamente)
REPLACE INTO `users` (`id`, `name`, `email`, `password_hash`, `two_factor_secret`, `two_factor_enabled`, `role`, `calendar_color`, `commission_percentage`, `google_calendar_id`, `google_refresh_token`, `google_access_token`, `google_token_expires_at`, `active`, `created_at`, `updated_at`) VALUES
	(1, 'Administrador Prueba', 'admin@test.com', '$argon2id$v=19$m=65536,t=4,p=1$eEpiQkcydW95UGU4NW9Baw$LlYdY9WYYpZx8oZ3WMOP2rVrXjPz8+BNxyAyj+k/dkY', '2T2LHQMXHERF2XAH', 1, 'admin', '#3788d8', 0.00, 'primary', '1//03EgnXXtLoGZnCgYIARAAGAMSNwF-L9Ir_l9R7YX1bMUsWl_ybe_16xHl3B-voxT9vIEy9uH2jZLuTFYboUU2mfwK3KwTSt4I6jk', '"{\\"access_token\\":\\"ya29.a0Aa7MYiqvm90Lgrj8TQiyIPXa_ZnbHNed0PV9J5__zGC_aVIqE_Hvg4BFHK57ElwBMLmAaY-5Eq0h6MCoOe7uYEhopZ1xBW0R7DFBVvKvdK6Spwkv-s0BcLwGKa66LgoZQnDFv1M7jjf2KYmsvDvi0ruM5uIwsXR_dHk9qVFsOXfx-TldoQuDK1Uy0Mtr0x17iBmLtj8aCgYKASgSARQSFQHGX2Mi8hNcLkUlWdknOP6MtoHmRw0206\\",\\"expires_in\\":3599,\\"refresh_token\\":\\"1\\\\\\/\\\\\\/03EgnXXtLoGZnCgYIARAAGAMSNwF-L9Ir_l9R7YX1bMUsWl_ybe_16xHl3B-voxT9vIEy9uH2jZLuTFYboUU2mfwK3KwTSt4I6jk\\",\\"scope\\":\\"https:\\\\\\/\\\\\\/www.googleapis.com\\\\\\/auth\\\\\\/calendar\\",\\"token_type\\":\\"Bearer\\",\\"refresh_token_expires_in\\":604799,\\"created\\":1774342772}"', '2026-03-24 10:59:31', 1, '2026-02-10 10:12:17', '2026-03-24 08:59:32'),
	(2, 'Dr. Ejemplo Gráficos', 'graficos@test.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', NULL, 0, 'professional', '#3788d8', 0.00, 'primary', NULL, NULL, NULL, 1, '2026-02-11 13:33:36', '2026-02-11 12:33:36'),
	(3, 'Soporte IT', 'it@admin.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', NULL, 0, 'it_admin', '#3788d8', 0.00, 'primary', NULL, NULL, NULL, 1, '2026-02-23 10:03:20', '2026-02-23 09:03:20');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
