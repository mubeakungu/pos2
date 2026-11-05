-- ------------------------------------------------------------
-- Upgrade for an EXISTING posystem database.
-- Run this once in phpMyAdmin (SQL tab) on your current database.
-- (New installs: just import posystem.sql, it already includes this.)
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `settings` (
  `name` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tax used to be saved as a whole number, which dropped the cents
ALTER TABLE `sales` MODIFY `tax` float NOT NULL DEFAULT 0;

-- Allow longer payment details such as M-Pesa references
ALTER TABLE `sales` MODIFY `paymentMethod` varchar(100) NOT NULL DEFAULT 'cash';

-- Let new customers be created without a last purchase / birthdate
ALTER TABLE `customers` MODIFY `lastPurchase` datetime NULL DEFAULT NULL;
ALTER TABLE `customers` MODIFY `purchases` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `products` MODIFY `sales` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `users` MODIFY `lastLogin` datetime NULL DEFAULT NULL;
ALTER TABLE `users` MODIFY `status` int(1) NOT NULL DEFAULT 1;
ALTER TABLE `users` MODIFY `photo` text NULL;

-- Editing a sale should not change its date
ALTER TABLE `sales` MODIFY `saledate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
