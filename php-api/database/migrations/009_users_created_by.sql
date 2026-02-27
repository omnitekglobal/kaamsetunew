-- Track who created each dashboard user.
-- Adds created_by to users table, pointing to users.id.
-- Run once after creating the users table.
-- If the column or foreign key already exists, comment out that specific line.

ALTER TABLE `users` ADD COLUMN `created_by` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_created_by`
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
  ON DELETE SET NULL;

