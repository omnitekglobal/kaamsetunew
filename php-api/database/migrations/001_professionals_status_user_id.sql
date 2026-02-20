-- Run once if professionals table already exists (e.g. from Next.js).
-- Adds status (pending/approved/rejected) and user_id (link to users when approved).
-- If a column already exists, skip that line or run the single ALTER you need.

ALTER TABLE `professionals` ADD COLUMN `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending';
ALTER TABLE `professionals` ADD COLUMN `user_id` INT UNSIGNED NULL DEFAULT NULL;
-- ALTER TABLE `professionals` ADD COLUMN `created_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;
