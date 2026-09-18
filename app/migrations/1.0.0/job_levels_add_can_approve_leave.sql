-- Job Levels: add can_approve_leave column.
-- Stores a per-level flag indicating whether holders of this job level are
-- permitted to approve Leave Requests for employees they manage. Tenant-global
-- (company_id IS NULL) rows are seeded with sensible defaults so the existing
-- approval hierarchy (Supervisor -> Manager/Director -> Executive) is preserved
-- out of the box; company-specific rows default to 0 (not yet permitted).
-- This migration is idempotent and safe to re-run.

SET @has_col = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'job_levels'
    AND column_name = 'can_approve_leave');

SET @add_col = IF(@has_col = 0,
    'ALTER TABLE `job_levels` ADD COLUMN `can_approve_leave` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`',
    'SELECT "job_levels.can_approve_leave already exists" AS message');
PREPARE stmt FROM @add_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Seed defaults for tenant-global rows: Supervisor (O3) and above can approve.
-- Executive (D1-D5) and Management (M1-M5) tiers, plus the Supervisor officer
-- level, are granted approval rights. Junior officer levels (O1, O2) are not.
UPDATE `job_levels`
SET `can_approve_leave` = 1
WHERE `company_id` IS NULL
  AND `can_approve_leave` = 0
  AND (
      `code` IN ('D5','D4','D3','D2','D1','M5','M4','M3','M2','M1','O3')
      OR `category` IN ('Executive','Management')
  );
