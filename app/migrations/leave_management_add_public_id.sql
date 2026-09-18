-- Migration: Add public_id UUID columns to leave management tables
-- Run this against the `hr` database before deploying the new code.

-- leave_types
ALTER TABLE `leave_types`
    ADD COLUMN `public_id` CHAR(36) NULL AFTER `id`,
    ADD UNIQUE KEY `unique_leave_types_public_id` (`public_id`);

-- leave_balances
ALTER TABLE `leave_balances`
    ADD COLUMN `public_id` CHAR(36) NULL AFTER `id`,
    ADD UNIQUE KEY `unique_leave_balances_public_id` (`public_id`);

-- leave_requests
ALTER TABLE `leave_requests`
    ADD COLUMN `public_id` CHAR(36) NULL AFTER `id`,
    ADD UNIQUE KEY `unique_leave_requests_public_id` (`public_id`);

-- Backfill existing rows with UUIDv4 values
UPDATE `leave_types` SET `public_id` = UUID() WHERE `public_id` IS NULL;
UPDATE `leave_balances` SET `public_id` = UUID() WHERE `public_id` IS NULL;
UPDATE `leave_requests` SET `public_id` = UUID() WHERE `public_id` IS NULL;

-- Make public_id NOT NULL after backfill
ALTER TABLE `leave_types` MODIFY COLUMN `public_id` CHAR(36) NOT NULL;
ALTER TABLE `leave_balances` MODIFY COLUMN `public_id` CHAR(36) NOT NULL;
ALTER TABLE `leave_requests` MODIFY COLUMN `public_id` CHAR(36) NOT NULL;
