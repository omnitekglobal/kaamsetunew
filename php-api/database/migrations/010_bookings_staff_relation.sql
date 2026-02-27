-- Ensure bookings table has staff relation columns (created_by, assigned_by).
-- Idempotent: safe to run even if migration 003 already added these.
-- Links bookings to staff/TL: who created the booking, who assigned it to a professional.

DROP PROCEDURE IF EXISTS add_bookings_staff_columns;

DELIMITER //
CREATE PROCEDURE add_bookings_staff_columns()
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings') THEN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'created_by') THEN
      ALTER TABLE `bookings` ADD COLUMN `created_by` INT UNSIGNED NULL DEFAULT NULL COMMENT 'user_id if created from dashboard (staff/TL)';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'assigned_by') THEN
      ALTER TABLE `bookings` ADD COLUMN `assigned_by` INT UNSIGNED NULL DEFAULT NULL COMMENT 'user_id of staff who assigned to professional';
    END IF;
  END IF;
END//
DELIMITER ;

CALL add_bookings_staff_columns();
DROP PROCEDURE IF EXISTS add_bookings_staff_columns;
