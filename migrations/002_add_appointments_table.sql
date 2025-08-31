-- Migration to add the appointments table

CREATE TABLE `appointments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_user_id` INT UNSIGNED NOT NULL,
  `patient_user_id` INT UNSIGNED NOT NULL,
  `appointment_time` DATETIME NOT NULL,
  `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 30,
  `status` ENUM('pending', 'confirmed', 'completed', 'cancelled_by_doctor', 'cancelled_by_patient') NOT NULL DEFAULT 'pending',
  `notes` TEXT,
  `payment_id` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appointments_doctor` (`doctor_user_id`),
  KEY `idx_appointments_patient` (`patient_user_id`),
  CONSTRAINT `fk_appointments_doctors` FOREIGN KEY (`doctor_user_id`) REFERENCES `doctors` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_patients` FOREIGN KEY (`patient_user_id`) REFERENCES `patients` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_payments` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
