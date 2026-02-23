-- Add icon column to services (run if table already exists without it).
ALTER TABLE `services` ADD COLUMN `icon` VARCHAR(255) DEFAULT NULL AFTER `description`;
