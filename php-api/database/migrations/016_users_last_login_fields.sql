-- Track each user's latest login date and time.
-- Run once after creating the users table.
-- If a column already exists, comment out that specific ALTER.

ALTER TABLE `users` ADD COLUMN `last_login_date` DATE NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `last_login_time` TIME NULL DEFAULT NULL;
