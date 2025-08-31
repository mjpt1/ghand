-- Comprehensive Health Management Web System
-- Database Schema
--
-- Target Server Version: MySQL 8
-- Character Set: utf8mb4
-- Collation: utf8mb4_persian_ci

-- This file should be imported into your database.
-- Do NOT include the `CREATE DATABASE` statement, as per requirements.
-- USE `your_database_name`;

SET NAMES utf8mb4;
SET time_zone = '+03:30'; -- Asia/Tehran

--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone_number` VARCHAR(20) DEFAULT NULL,
  `role` ENUM('patient', 'doctor', 'pharmacy', 'admin') NOT NULL,
  `status` ENUM('active', 'inactive', 'pending_verification') NOT NULL DEFAULT 'pending_verification',
  `two_fa_secret` VARCHAR(255) DEFAULT NULL,
  `two_fa_enabled` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `doctors`
--
CREATE TABLE `doctors` (
  `user_id` INT UNSIGNED NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `medical_license_number` VARCHAR(100) NOT NULL,
  `specialty` VARCHAR(100) DEFAULT NULL,
  `clinic_address` TEXT,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `medical_license_number` (`medical_license_number`),
  CONSTRAINT `fk_doctors_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `patients`
--
CREATE TABLE `patients` (
  `user_id` INT UNSIGNED NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `national_id` VARCHAR(11) DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `gender` ENUM('male', 'female', 'other') DEFAULT NULL,
  `address` TEXT,
  `assigned_doctor_id` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `national_id` (`national_id`),
  CONSTRAINT `fk_patients_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_patients_doctors` FOREIGN KEY (`assigned_doctor_id`) REFERENCES `doctors` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `pharmacies`
--
CREATE TABLE `pharmacies` (
  `user_id` INT UNSIGNED NOT NULL,
  `pharmacy_name` VARCHAR(150) NOT NULL,
  `license_number` VARCHAR(100) NOT NULL,
  `address` TEXT,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `license_number` (`license_number`),
  CONSTRAINT `fk_pharmacies_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `health_records`
--
CREATE TABLE `health_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_user_id` INT UNSIGNED NOT NULL,
  `record_type` ENUM('blood_sugar', 'blood_pressure', 'heart_rate', 'spo2', 'bmi') NOT NULL,
  `value1` DECIMAL(10, 2) NOT NULL,
  `value2` DECIMAL(10, 2) DEFAULT NULL,
  `unit` VARCHAR(20) DEFAULT NULL,
  `recorded_at` TIMESTAMP NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_patient_records` (`patient_user_id`, `recorded_at`),
  CONSTRAINT `fk_health_records_patients` FOREIGN KEY (`patient_user_id`) REFERENCES `patients` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `meals`
--
CREATE TABLE `meals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_user_id` INT UNSIGNED NOT NULL,
  `meal_type` ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
  `description` TEXT NOT NULL,
  `meal_time` TIMESTAMP NOT NULL,
  `calories` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_patient_meals` (`patient_user_id`, `meal_time`),
  CONSTRAINT `fk_meals_patients` FOREIGN KEY (`patient_user_id`) REFERENCES `patients` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `prescriptions`
--
CREATE TABLE `prescriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_user_id` INT UNSIGNED NOT NULL,
  `patient_user_id` INT UNSIGNED NOT NULL,
  `prescription_details` TEXT NOT NULL,
  `status` ENUM('issued', 'filled', 'cancelled') NOT NULL DEFAULT 'issued',
  `pharmacy_user_id` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prescriptions_doctor` (`doctor_user_id`),
  KEY `idx_prescriptions_patient` (`patient_user_id`),
  CONSTRAINT `fk_prescriptions_doctors` FOREIGN KEY (`doctor_user_id`) REFERENCES `doctors` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prescriptions_patients` FOREIGN KEY (`patient_user_id`) REFERENCES `patients` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prescriptions_pharmacies` FOREIGN KEY (`pharmacy_user_id`) REFERENCES `pharmacies` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `messages`
--
CREATE TABLE `messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_user_id` INT UNSIGNED NOT NULL,
  `receiver_user_id` INT UNSIGNED NOT NULL,
  `message_content` BLOB NOT NULL, -- Storing encrypted content
  `message_type` ENUM('text', 'audio_url', 'file') NOT NULL DEFAULT 'text',
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_messages_sender_receiver` (`sender_user_id`, `receiver_user_id`),
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `payments`
--
CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(20, 2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'IRR',
  `gateway` ENUM('zarinpal', 'idpay', 'pay.ir', 'wallet') NOT NULL,
  `transaction_id` VARCHAR(191) DEFAULT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `description` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id_gateway` (`transaction_id`, `gateway`),
  KEY `idx_payments_user` (`user_id`),
  CONSTRAINT `fk_payments_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `reminders`
--
CREATE TABLE `reminders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `reminder_type` ENUM('medication', 'appointment') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `reminder_time` TIMESTAMP NOT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reminders_user` (`user_id`),
  CONSTRAINT `fk_reminders_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `ai_analysis`
--
CREATE TABLE `ai_analysis` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_user_id` INT UNSIGNED NOT NULL,
  `analysis_type` VARCHAR(100) NOT NULL,
  `result` JSON,
  `generated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ai_analysis_patient` (`patient_user_id`),
  CONSTRAINT `fk_ai_analysis_patients` FOREIGN KEY (`patient_user_id`) REFERENCES `patients` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `logs`
--
CREATE TABLE `logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `action` VARCHAR(191) NOT NULL,
  `details` TEXT,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Table structure for table `rate_limits`
--
CREATE TABLE `rate_limits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(191) NOT NULL,
  `action_type` VARCHAR(100) NOT NULL,
  `request_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `last_request_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identifier_action` (`identifier`, `action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
