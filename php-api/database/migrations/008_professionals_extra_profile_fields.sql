-- Optional extra profile fields for professionals.
-- Adds village, landmark, and aadhaar_no to professionals table.
-- Run once after creating the professionals table.
-- If a column already exists, comment out that specific ALTER.

ALTER TABLE `professionals` ADD COLUMN `village` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `professionals` ADD COLUMN `landmark` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `professionals` ADD COLUMN `aadhaar_no` VARCHAR(20) NULL DEFAULT NULL;

