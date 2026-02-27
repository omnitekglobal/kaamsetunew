-- Adds referral_code to users so staff (and optionally other roles) can
-- have a unique, stable referral code.
-- Run once after creating the users table.
-- If the column or index already exists, comment out the relevant lines.

ALTER TABLE `users` ADD COLUMN `referral_code` VARCHAR(64) NULL DEFAULT NULL;
CREATE UNIQUE INDEX `idx_users_referral_code` ON `users` (`referral_code`);

