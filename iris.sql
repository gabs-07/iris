-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         9.0.1 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para irisfepi
CREATE DATABASE IF NOT EXISTS `irisfepi` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `irisfepi`;

-- Volcando estructura para tabla irisfepi.appointments
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `professional_id` bigint unsigned DEFAULT NULL,
  `folio` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `modality` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `missed_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending_payment','pending','accepted','rescheduled','rejected','cancelled','completed','missed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded','waived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) DEFAULT NULL,
  `room_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zoom_meeting_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zoom_join_url` text COLLATE utf8mb4_unicode_ci,
  `zoom_start_url` text COLLATE utf8mb4_unicode_ci,
  `zoom_password` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zoom_created_at` timestamp NULL DEFAULT NULL,
  `zoom_payload` json DEFAULT NULL,
  `requested_by` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reschedule_proposal` text COLLATE utf8mb4_unicode_ci,
  `reschedule_date` date DEFAULT NULL,
  `reschedule_time` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancel_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointments_folio_unique` (`folio`),
  KEY `appointments_patient_id_foreign` (`patient_id`),
  KEY `appointments_professional_id_foreign` (`professional_id`),
  KEY `appointments_appointment_date_index` (`appointment_date`),
  KEY `appointments_starts_at_index` (`starts_at`),
  KEY `appointments_status_index` (`status`),
  CONSTRAINT `appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_professional_id_foreign` FOREIGN KEY (`professional_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.appointments: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint unsigned DEFAULT NULL,
  `patient_id` bigint unsigned DEFAULT NULL,
  `auditable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auditable_id` bigint unsigned DEFAULT NULL,
  `action` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_actor_id_foreign` (`actor_id`),
  KEY `audit_logs_patient_id_foreign` (`patient_id`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audit_logs_action_index` (`action`),
  CONSTRAINT `audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.audit_logs: ~5 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.cache
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.cache: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.cache_locks
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.cache_locks: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.community_comments
CREATE TABLE IF NOT EXISTS `community_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `community_post_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `community_comments_community_post_id_foreign` (`community_post_id`),
  KEY `community_comments_user_id_foreign` (`user_id`),
  CONSTRAINT `community_comments_community_post_id_foreign` FOREIGN KEY (`community_post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.community_comments: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.community_likes
CREATE TABLE IF NOT EXISTS `community_likes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `community_post_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_likes_community_post_id_user_id_unique` (`community_post_id`,`user_id`),
  KEY `community_likes_user_id_foreign` (`user_id`),
  CONSTRAINT `community_likes_community_post_id_foreign` FOREIGN KEY (`community_post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.community_likes: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.community_posts
CREATE TABLE IF NOT EXISTS `community_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `community_posts_user_id_foreign` (`user_id`),
  CONSTRAINT `community_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.community_posts: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.community_reports
CREATE TABLE IF NOT EXISTS `community_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `community_post_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `reason` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `community_reports_community_post_id_foreign` (`community_post_id`),
  KEY `community_reports_user_id_foreign` (`user_id`),
  KEY `community_reports_reviewed_by_foreign` (`reviewed_by`),
  KEY `community_reports_status_index` (`status`),
  CONSTRAINT `community_reports_community_post_id_foreign` FOREIGN KEY (`community_post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_reports_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `community_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.community_reports: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.diary_entries
CREATE TABLE IF NOT EXISTS `diary_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `mood` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emoji` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entry_date` date NOT NULL,
  `authorized_professional_id` bigint unsigned DEFAULT NULL,
  `authorized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diary_entries_patient_id_foreign` (`patient_id`),
  KEY `diary_entries_entry_date_index` (`entry_date`),
  KEY `diary_entries_authorized_professional_id_foreign` (`authorized_professional_id`),
  CONSTRAINT `diary_entries_authorized_professional_id_foreign` FOREIGN KEY (`authorized_professional_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `diary_entries_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.diary_entries: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.emergency_contacts
CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `nombre` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relacion` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emergency_contacts_user_id_unique` (`user_id`),
  CONSTRAINT `emergency_contacts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.emergency_contacts: ~2 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.jobs
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.jobs: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.legal_consents
CREATE TABLE IF NOT EXISTS `legal_consents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `acepta_terminos` tinyint(1) NOT NULL DEFAULT '0',
  `acepta_privacidad` tinyint(1) NOT NULL DEFAULT '0',
  `acepta_datos_sensibles` tinyint(1) NOT NULL DEFAULT '0',
  `acepta_comunicaciones` tinyint(1) NOT NULL DEFAULT '0',
  `acepta_condiciones_profesionales` tinyint(1) NOT NULL DEFAULT '0',
  `declara_veracidad_profesional` tinyint(1) NOT NULL DEFAULT '0',
  `accepted_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `legal_consents_user_id_unique` (`user_id`),
  CONSTRAINT `legal_consents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.legal_consents: ~2 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.migrations: ~9 rows (aproximadamente)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '0001_01_01_000000_create_core_tables', 1),
	(2, '2026_06_08_000100_create_profiles_and_legal_tables', 1),
	(3, '2026_06_08_000200_create_clinical_tables', 1),
	(4, '2026_06_08_000300_create_payments_and_community_tables', 1),
	(5, '2026_06_08_000400_add_real_backend_features', 1),
	(6, '2026_06_08_000500_add_zoom_fields_to_appointments', 1),
	(7, '2026_06_08_000600_add_missed_status_to_appointments', 1),
	(8, '2026_06_08_000700_add_modo_escucha_to_professional_profiles', 1),
	(9, '2026_06_12_000800_add_guest_diary_and_professional_chat', 1),
	(10, '2026_06_17_000001_add_doctor_interno_role', 1);

-- Volcando estructura para tabla irisfepi.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.notifications: ~3 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.password_reset_tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.password_reset_tokens: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.patient_notes
CREATE TABLE IF NOT EXISTS `patient_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `professional_id` bigint unsigned DEFAULT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note_date` date DEFAULT NULL,
  `type` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_notes_patient_id_foreign` (`patient_id`),
  KEY `patient_notes_professional_id_foreign` (`professional_id`),
  CONSTRAINT `patient_notes_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_notes_professional_id_foreign` FOREIGN KEY (`professional_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.patient_notes: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.patient_profiles
CREATE TABLE IF NOT EXISTS `patient_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `terapia_previa` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medicacion_actual` text COLLATE utf8mb4_unicode_ci,
  `motivo_consulta` text COLLATE utf8mb4_unicode_ci,
  `objetivos` text COLLATE utf8mb4_unicode_ci,
  `ocupacion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `domicilio` text COLLATE utf8mb4_unicode_ci,
  `estado_civil` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `antecedentes` text COLLATE utf8mb4_unicode_ci,
  `alergias` text COLLATE utf8mb4_unicode_ci,
  `clinical_history` longtext COLLATE utf8mb4_unicode_ci,
  `clinical_attachments` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_profiles_user_id_unique` (`user_id`),
  CONSTRAINT `patient_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.patient_profiles: ~1 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.patient_tasks
CREATE TABLE IF NOT EXISTS `patient_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `professional_id` bigint unsigned DEFAULT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `due_date` date DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `repeat` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evidence` text COLLATE utf8mb4_unicode_ci,
  `evidence_file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evidence_file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evidence_file_disk` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT 'local',
  `evidence_file_mime` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evidence_file_size` int unsigned DEFAULT NULL,
  `follow_up` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `review_status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `review_feedback` text COLLATE utf8mb4_unicode_ci,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_tasks_patient_id_foreign` (`patient_id`),
  KEY `patient_tasks_professional_id_foreign` (`professional_id`),
  CONSTRAINT `patient_tasks_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_tasks_professional_id_foreign` FOREIGN KEY (`professional_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.patient_tasks: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `subscription_id` bigint unsigned DEFAULT NULL,
  `concept` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `method` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'card',
  `provider` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_order_id` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_capture_id` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_provider_order_id_unique` (`provider_order_id`),
  KEY `payments_user_id_foreign` (`user_id`),
  KEY `payments_appointment_id_foreign` (`appointment_id`),
  KEY `payments_subscription_id_foreign` (`subscription_id`),
  KEY `payments_provider_capture_id_index` (`provider_capture_id`),
  CONSTRAINT `payments_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.payments: ~16 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.prescriptions
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned DEFAULT NULL,
  `professional_id` bigint unsigned NOT NULL,
  `folio` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_name` text COLLATE utf8mb4_unicode_ci,
  `diagnosis` text COLLATE utf8mb4_unicode_ci,
  `medication` text COLLATE utf8mb4_unicode_ci,
  `dose` text COLLATE utf8mb4_unicode_ci,
  `frequency` text COLLATE utf8mb4_unicode_ci,
  `duration` text COLLATE utf8mb4_unicode_ci,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'emitida',
  `issued_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prescriptions_folio_unique` (`folio`),
  KEY `prescriptions_patient_id_foreign` (`patient_id`),
  KEY `prescriptions_professional_id_foreign` (`professional_id`),
  CONSTRAINT `prescriptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prescriptions_professional_id_foreign` FOREIGN KEY (`professional_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.prescriptions: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.professional_chat_messages
CREATE TABLE IF NOT EXISTS `professional_chat_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professional_chat_messages_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `professional_chat_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.professional_chat_messages: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.professional_profiles
CREATE TABLE IF NOT EXISTS `professional_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `tipo_profesional` enum('psicologo','psiquiatra','doctor_interno') COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo_profesional` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cedula_profesional` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cedula_especialidad` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `institucion` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `posgrado` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `especialidad_principal` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `experiencia_anios` smallint unsigned DEFAULT NULL,
  `asociaciones` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enfoques` json DEFAULT NULL,
  `poblaciones` json DEFAULT NULL,
  `areas` json DEFAULT NULL,
  `modalidad` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubicacion` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idiomas` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `biografia` text COLLATE utf8mb4_unicode_ci,
  `servicios` text COLLATE utf8mb4_unicode_ci,
  `presentacion` text COLLATE utf8mb4_unicode_ci,
  `formacion_academica` json DEFAULT NULL,
  `especialidades` json DEFAULT NULL,
  `dias_atencion` json DEFAULT NULL,
  `proximo_espacio` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `costo_min` decimal(10,2) DEFAULT NULL,
  `costo_max` decimal(10,2) DEFAULT NULL,
  `duracion_sesion` smallint unsigned DEFAULT NULL,
  `disponibilidad` json DEFAULT NULL,
  `modo_escucha_activo` tinyint(1) NOT NULL DEFAULT '0',
  `modo_escucha_activado_at` timestamp NULL DEFAULT NULL,
  `documentos` json DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `professional_profiles_user_id_unique` (`user_id`),
  KEY `professional_profiles_approved_by_foreign` (`approved_by`),
  KEY `professional_profiles_cedula_profesional_index` (`cedula_profesional`),
  CONSTRAINT `professional_profiles_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `professional_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.professional_profiles: ~1 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.sessions: ~2 rows (aproximadamente)
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
	('YQBkz322b1nLE2iICGYWpOdd0U9nwkkutGO6orAh', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.124.2 Chrome/148.0.7778.97 Electron/42.2.0 Safari/537.36', 'ZXlKcGRpSTZJaTlxUzNGU1ozVXdhMll6TDBkelNURkxhamxsZGtFOVBTSXNJblpoYkhWbElqb2laeXRuWXpWUFFXUmxkMlphWlhVNVoxcERlbUZ3T1RReldYVlJjWFpUZWk5Q1kyTm5hM1l2YlhSNWVtSm9NSGs0U0dWMWRVTTNWbGhyYjJreE1VaFhTa1EwWnpSbEsyb3pWVmM0Wmt4RU5USklhRUYwZVd3eWFHdHNVblZGYUZRME5XWkZRa1pXWTNWdGJraFBXRXd4WkRkelNIUnJTRXBvU0d0S2VIaERkaXRDVUN0ek4zRlhaVVJEU2toVlQzZERaRFZEYkVaWk4wdHNSMVo2VGxKdlMzcFRjRU5DUkRoMk1pOU5LMGRHVjJSU2RYVnBhakV6TW01dE1YZEZWMGxyZFZSeFRIbEVTV0Y0YlRKQlEyRndWSEJzT1U1clowOHdXV3BzV1hvNEsyMTZWa1pXY0V4S1VuSXdha2xhUWpOR2FTOTZUQ3QxUnpKUVdFTmxWRTlCYWtreVVYWTBNRVk1UTFWNWMyeERTMmxUV0dkeFZVRTRZbGhWVkdJeVFrVm1XSFp0UVZJNGRqWmhaVEZTY1hnNU5YcFFZbll4YjFWYVlqbHROMVZxUjFFaUxDSnRZV01pT2lKbVpUUXlZV0l3T0RZd05UZGxZMk0yTXpBMllqTm1OalUyTmpjNVl6a3lOelEyWWpjM1lUa3pORGczTW1ZNU1UWmxZekUyTVRRNE5qSXpPR1EwTm1Oaklpd2lkR0ZuSWpvaUluMD0=', 1781706225);

-- Volcando estructura para tabla irisfepi.session_notes
CREATE TABLE IF NOT EXISTS `session_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `professional_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned DEFAULT NULL,
  `note_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'session',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_notes_appointment_id_foreign` (`appointment_id`),
  KEY `session_notes_professional_id_foreign` (`professional_id`),
  KEY `session_notes_patient_id_foreign` (`patient_id`),
  CONSTRAINT `session_notes_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `session_notes_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `session_notes_professional_id_foreign` FOREIGN KEY (`professional_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.session_notes: ~0 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.subscriptions
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cycle` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `features` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_user_id_foreign` (`user_id`),
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.subscriptions: ~1 rows (aproximadamente)

-- Volcando estructura para tabla irisfepi.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('invitado','paciente','psicologo','psiquiatra','doctor_interno','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paciente',
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_completed` tinyint(1) NOT NULL DEFAULT '0',
  `professional_status` enum('none','incomplete','pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `professional_submitted_at` timestamp NULL DEFAULT NULL,
  `professional_approved_at` timestamp NULL DEFAULT NULL,
  `professional_rejected_at` timestamp NULL DEFAULT NULL,
  `professional_rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_by` bigint unsigned DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_approved_by_foreign` (`approved_by`),
  KEY `users_rol_index` (`rol`),
  KEY `users_professional_status_index` (`professional_status`),
  CONSTRAINT `users_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla irisfepi.users: ~2 rows (aproximadamente)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
