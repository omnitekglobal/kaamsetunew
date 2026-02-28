-- Customers are created first; bookings can be assigned to them later (via customer_id on bookings).
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(255) NULL DEFAULT NULL,
  `city` VARCHAR(100) NULL DEFAULT NULL,
  `state` VARCHAR(100) NULL DEFAULT NULL,
  `pincode` VARCHAR(20) NULL DEFAULT NULL,
  `language` VARCHAR(50) NULL DEFAULT NULL,
  `referral_code` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Staff/pro referrer code when customer was added',
  `created_by` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Staff/TL user_id who added this customer',
  `created_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `phone` (`phone`),
  KEY `referral_code` (`referral_code`)
);
