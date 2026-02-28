-- Link bookings to a customer when booking is created/assigned in future.
ALTER TABLE `bookings` ADD COLUMN `customer_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Link to customers.id when booking is assigned to a customer';
ALTER TABLE `bookings` ADD KEY `customer_id` (`customer_id`);
