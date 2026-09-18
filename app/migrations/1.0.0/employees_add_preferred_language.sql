-- Employees: add preferred_language column
-- Stores the employee's preferred UI/content language choice so it can be
-- restored on subsequent logins instead of relying on session only.
-- Values match language codes used by the language switcher (e.g. 'th',
-- 'en', or a tenant-installed content language code). NULL means no
-- preference has been set yet, in which case the configured default
-- (Thai) applies. This migration is idempotent and safe to re-run.

SET @pl_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'employees'
    AND column_name = 'preferred_language');

SET @pl_sql = IF(@pl_exists = 0,
    'ALTER TABLE `employees` ADD COLUMN `preferred_language` VARCHAR(10) NULL DEFAULT NULL AFTER `must_change_password`',
    'SELECT "preferred_language column already exists" AS message');
PREPARE stmt FROM @pl_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
