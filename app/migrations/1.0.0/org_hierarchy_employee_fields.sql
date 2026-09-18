-- Org Hierarchy Import: Employee schema adjustments
-- Run this against the `hr` database before deploying the new import endpoints.
-- This migration is idempotent and safe to re-run.

-- Add gender column if it does not exist
SET @gender_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'employees'
    AND column_name = 'gender');

SET @gender_sql = IF(@gender_exists = 0,
    'ALTER TABLE `employees` ADD COLUMN `gender` VARCHAR(20) NULL AFTER `last_name`',
    'SELECT "gender column already exists" AS message');
PREPARE stmt FROM @gender_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add employment_type column if it does not exist
SET @employment_type_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'employees'
    AND column_name = 'employment_type');

SET @employment_type_sql = IF(@employment_type_exists = 0,
    'ALTER TABLE `employees` ADD COLUMN `employment_type` VARCHAR(50) NULL AFTER `gender`',
    'SELECT "employment_type column already exists" AS message');
PREPARE stmt FROM @employment_type_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Make email nullable to allow CSV rows without an email address
SET @email_nullable = (SELECT is_nullable FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'employees'
    AND column_name = 'email');

SET @email_sql = IF(@email_nullable = 'NO',
    'ALTER TABLE `employees` MODIFY COLUMN `email` VARCHAR(255) NULL',
    'SELECT "email is already nullable" AS message');
PREPARE stmt FROM @email_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Make phone nullable to allow CSV rows without a phone number
SET @phone_nullable = (SELECT is_nullable FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'employees'
    AND column_name = 'phone');

SET @phone_sql = IF(@phone_nullable = 'NO',
    'ALTER TABLE `employees` MODIFY COLUMN `phone` VARCHAR(50) NULL',
    'SELECT "phone is already nullable" AS message');
PREPARE stmt FROM @phone_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Make date_of_birth nullable to allow CSV rows without a date of birth
SET @dob_nullable = (SELECT is_nullable FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'employees'
    AND column_name = 'date_of_birth');

SET @dob_sql = IF(@dob_nullable = 'NO',
    'ALTER TABLE `employees` MODIFY COLUMN `date_of_birth` DATE NULL',
    'SELECT "date_of_birth is already nullable" AS message');
PREPARE stmt FROM @dob_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure positions can be self-referencing with a nullable parent
SET @parent_fk_exists = (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
    AND table_name = 'positions'
    AND constraint_name = 'positions_parent_position_id_fk');

SET @parent_fk_sql = IF(@parent_fk_exists = 0,
    'ALTER TABLE `positions` ADD CONSTRAINT `positions_parent_position_id_fk` FOREIGN KEY (`parent_position_id`) REFERENCES `positions` (`id`) ON DELETE RESTRICT',
    'SELECT "positions parent_position_id FK already exists" AS message');
PREPARE stmt FROM @parent_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
