-- Fix bookings.status so it accepts both 'pending' and 'assigned' (e.g. if it was ENUM or too narrow).
-- Run once. Safe to run even if column is already VARCHAR(30).

ALTER TABLE `bookings` MODIFY COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'pending';
