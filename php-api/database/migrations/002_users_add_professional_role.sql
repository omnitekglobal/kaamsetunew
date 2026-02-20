-- Add 'professional' role for service providers (approved from professionals table).
-- End user = customer who books; professional = service provider.
-- Run once. If you get an error, your MySQL may already have the role.

ALTER TABLE `users` MODIFY COLUMN `role` ENUM('super_admin', 'admin', 'staff', 'professional', 'end_user') NOT NULL DEFAULT 'end_user';
