-- Add city and state to bookings so Add Customer can match professional details.
ALTER TABLE `bookings` ADD COLUMN `city` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `bookings` ADD COLUMN `state` VARCHAR(100) NULL DEFAULT NULL;
