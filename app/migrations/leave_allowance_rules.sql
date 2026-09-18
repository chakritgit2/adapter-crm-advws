-- Migration: Create leave_allowance_rules table
-- Position & tenure-based allowance rules for leave policies.
-- Run this against the `hr` database before deploying the new code.

CREATE TABLE `leave_allowance_rules` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `public_id` CHAR(36) NOT NULL,
    `company_id` INT(11) NOT NULL,
    `leave_type_id` INT(11) NOT NULL,
    `position_id` INT(11) NULL DEFAULT NULL,
    `job_level` VARCHAR(10) NULL DEFAULT NULL,
    `min_tenure_months` INT(11) NULL DEFAULT NULL,
    `max_tenure_months` INT(11) NULL DEFAULT NULL,
    `allowance_minutes` INT(11) NOT NULL DEFAULT 0,
    `priority` INT(11) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_leave_allowance_rules_public_id` (`public_id`),
    KEY `lar_company_idx` (`company_id`),
    KEY `lar_leave_type_idx` (`leave_type_id`),
    KEY `lar_position_idx` (`position_id`),
    CONSTRAINT `lar_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `lar_leave_type_fk` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
    CONSTRAINT `lar_position_fk` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
