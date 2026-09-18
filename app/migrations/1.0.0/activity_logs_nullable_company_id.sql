-- Make activity_logs.company_id nullable so platform-level (System Admin)
-- actions can be logged with NULL instead of a real company_id.
-- This migration is idempotent and safe to re-run.

-- 1. Drop the existing FK so we can modify the column.
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
    AND table_name = 'activity_logs'
    AND constraint_type = 'FOREIGN KEY'
    AND constraint_name = 'fk_activity_logs_company');

SET @drop_fk = IF(@fk_exists > 0,
    'ALTER TABLE `activity_logs` DROP FOREIGN KEY `fk_activity_logs_company`',
    'SELECT "fk_activity_logs_company already dropped" AS message');
PREPARE stmt FROM @drop_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Make company_id nullable.
SET @col_nullable = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'activity_logs'
    AND column_name = 'company_id'
    AND is_nullable = 'NO');

SET @make_nullable = IF(@col_nullable > 0,
    'ALTER TABLE `activity_logs` MODIFY COLUMN `company_id` INT NULL',
    'SELECT "activity_logs.company_id already nullable" AS message');
PREPARE stmt FROM @make_nullable;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Re-add the FK (still cascades on delete; NULL company_id is allowed).
SET @fk_exists2 = (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
    AND table_name = 'activity_logs'
    AND constraint_type = 'FOREIGN KEY'
    AND constraint_name = 'fk_activity_logs_company');

SET @add_fk = IF(@fk_exists2 = 0,
    'ALTER TABLE `activity_logs` ADD CONSTRAINT `fk_activity_logs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT "fk_activity_logs_company already exists" AS message');
PREPARE stmt FROM @add_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
