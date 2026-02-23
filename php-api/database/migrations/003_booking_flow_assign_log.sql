-- Booking flow: status (pending/assigned), assign to professional, and audit log.
-- Run once. Bookings table must exist. If a column already exists, skip that statement or run one at a time.

ALTER TABLE `bookings` ADD COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'pending';
ALTER TABLE `bookings` ADD COLUMN `assigned_to` INT UNSIGNED NULL DEFAULT NULL COMMENT 'user_id of assigned professional';
ALTER TABLE `bookings` ADD COLUMN `assigned_by` INT UNSIGNED NULL DEFAULT NULL COMMENT 'user_id of staff who assigned';
ALTER TABLE `bookings` ADD COLUMN `assigned_at` DATETIME NULL DEFAULT NULL;
ALTER TABLE `bookings` ADD COLUMN `created_by` INT UNSIGNED NULL DEFAULT NULL COMMENT 'user_id if created from dashboard';
ALTER TABLE `bookings` ADD COLUMN `created_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

UPDATE `bookings` SET `created_at` = NOW() WHERE `created_at` IS NULL;

CREATE TABLE IF NOT EXISTS `booking_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` VARCHAR(64) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `by_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `details` JSON DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `by_user_id` (`by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
