-- Migrate roles: add team_leader, replace admin with team_leader.
-- Run if your users table still has ENUM('super_admin', 'admin', 'staff', ...).

-- Step 1: Add team_leader to ENUM
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('super_admin', 'admin', 'team_leader', 'staff', 'professional', 'end_user') NOT NULL DEFAULT 'end_user';

-- Step 2: Move existing admin users to team_leader
UPDATE `users` SET `role` = 'team_leader' WHERE `role` = 'admin';

-- Step 3: Remove admin from ENUM
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('super_admin', 'team_leader', 'staff', 'professional', 'end_user') NOT NULL DEFAULT 'end_user';
