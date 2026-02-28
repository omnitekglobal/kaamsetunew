-- Optional referral code on bookings when customer is added from dashboard (e.g. by staff).
ALTER TABLE `bookings` ADD COLUMN `referral_code` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Referral code used when creating booking from dashboard (e.g. staff code)';
