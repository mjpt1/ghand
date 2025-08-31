-- Migration to add the lab_results table
-- This should be applied to the database after the initial DB.sql import.

CREATE TABLE `lab_results` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(100) NOT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lab_results_patient` (`patient_user_id`),
  CONSTRAINT `fk_lab_results_patients` FOREIGN KEY (`patient_user_id`) REFERENCES `patients` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
