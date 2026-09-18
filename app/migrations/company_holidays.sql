-- Migration: Create company_holidays table
-- Per-company calendar of Company Holiday dates, managed by Super Admins
-- from the Settings > Company Holidays page.
-- Run this against the `hr` database before deploying the new code.

CREATE TABLE `company_holidays` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL,
    `holiday_date` DATE NOT NULL,
    `name` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_company_holiday` (`company_id`, `holiday_date`),
    KEY `ch_company_idx` (`company_id`),
    CONSTRAINT `ch_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
