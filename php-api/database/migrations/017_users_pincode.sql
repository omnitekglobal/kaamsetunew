-- Add pincode to users for location-wise reporting.
-- Run once after creating the users table.
-- If the column already exists, comment out this ALTER.

ALTER TABLE `users` ADD COLUMN `pincode` VARCHAR(20) NULL DEFAULT NULL;
