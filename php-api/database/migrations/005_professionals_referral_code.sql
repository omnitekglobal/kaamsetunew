-- Adds referral tracking fields for professionals created from the dashboard.
-- Run once after creating the professionals table.
-- If a column already exists, comment out that specific ALTER before running.

ALTER TABLE `professionals` ADD COLUMN `referral_code` VARCHAR(64) NULL DEFAULT NULL;
ALTER TABLE `professionals` ADD COLUMN `referred_by_user_id` INT UNSIGNED NULL DEFAULT NULL;

