-- Base bookings table (run first if the table does not exist).
-- Required for POST /api/bookings (public booking form).
CREATE TABLE IF NOT EXISTS `bookings` (
  `bookingId` VARCHAR(64) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `service` VARCHAR(255) NOT NULL,
  `pincode` VARCHAR(20) NOT NULL,
  `language` VARCHAR(50) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `created_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bookingId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
