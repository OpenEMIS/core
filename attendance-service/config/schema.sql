-- =================================================================
-- Schema for the Attendance Service Database
-- =================================================================

-- This table will store the attendance records for each student.
-- It is designed to be in a separate database managed by the
-- Attendance Service microservice.

CREATE TABLE `attendance_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL COMMENT 'Corresponds to the student ID in the core OpenEMIS database.',
  `class_id` INT UNSIGNED NOT NULL COMMENT 'Corresponds to the class ID in the core OpenEMIS database.',
  `attendance_date` DATE NOT NULL,
  `status` ENUM('present', 'absent', 'late', 'excused') NOT NULL,
  `notes` TEXT DEFAULT NULL COMMENT 'Optional notes, e.g., reason for absence.',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  -- A student can only have one attendance status per day for a given class.
  UNIQUE KEY `uq_student_class_date` (`student_id`, `class_id`, `attendance_date`),
  -- Add indexes for common query patterns
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_class_id_date` (`class_id`, `attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example of how to create the database and user:
-- CREATE DATABASE openemis_attendance;
-- CREATE USER 'attendance_user'@'localhost' IDENTIFIED BY 'password';
-- GRANT ALL PRIVILEGES ON openemis_attendance.* TO 'attendance_user'@'localhost';
-- FLUSH PRIVILEGES;
