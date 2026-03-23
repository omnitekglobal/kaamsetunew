-- Migration 018: Add WhatsApp account verification columns to users table
-- Run this on your MySQL database.

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `is_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
    ADD COLUMN IF NOT EXISTS `verification_token` VARCHAR(64) DEFAULT NULL AFTER `is_verified`,
    ADD COLUMN IF NOT EXISTS `verification_token_expires_at` DATETIME DEFAULT NULL AFTER `verification_token`;

-- Mark all existing users as already verified (so they are not locked out)
UPDATE `users` SET `is_verified` = 1 WHERE `is_verified` = 0;

-- Index for fast token lookup
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_verification_token` (`verification_token`);
