-- Positions: add job_level column
-- Stores the organization's job-level code (e.g. D5, M3, O1) for a position.
-- This migration is idempotent and safe to re-run.

SET @job_level_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'positions'
    AND column_name = 'job_level');

SET @job_level_sql = IF(@job_level_exists = 0,
    'ALTER TABLE `positions` ADD COLUMN `job_level` VARCHAR(10) NULL AFTER `department`',
    'SELECT "job_level column already exists" AS message');
PREPARE stmt FROM @job_level_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
