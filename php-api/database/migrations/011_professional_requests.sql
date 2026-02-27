-- Professional requests / inquiries: capture mobile number and optional referral code
-- when someone expresses interest in joining as a professional (e.g. "Request a call" form).

CREATE TABLE IF NOT EXISTS `professional_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(20) NOT NULL COMMENT 'Mobile number',
  `referral_code` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Optional referral code used',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `referral_code` (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
