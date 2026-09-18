-- Admin Users: add default_company_id column
-- Stores the user's preferred default company within a tenant.
-- Used by the company selector "Set Default" action and as the
-- fallback company when navigating from tenant-level pages.
-- This migration is idempotent and safe to re-run.

SET @dci_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'admin_users'
    AND column_name = 'default_company_id');

SET @dci_sql = IF(@dci_exists = 0,
    'ALTER TABLE `admin_users` ADD COLUMN `default_company_id` INT NULL DEFAULT NULL AFTER `must_change_password`',
    'SELECT "default_company_id column already exists" AS message');
PREPARE stmt FROM @dci_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
