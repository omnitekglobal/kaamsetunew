-- Optional extra profile fields for all users.
-- Adds language, village, state, landmark, and aadhaar_no to users table.
-- Run once after creating the users table.
-- If a column already exists, comment out that specific ALTER.

ALTER TABLE `users` ADD COLUMN `language` VARCHAR(50) NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `village` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `state` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `landmark` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `aadhaar_no` VARCHAR(20) NULL DEFAULT NULL;

