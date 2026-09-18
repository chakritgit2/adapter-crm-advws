-- Leave Requests: make approved_by polymorphic (admin user OR employee)
--
-- The employee portal allows managers (employees whose job_level has
-- can_approve_leave = 1) to approve/reject their subordinates' leave
-- requests. Those approvers are employees, not admin users, so the
-- teamApproveAction / teamRejectAction store the approver's employee id
-- in leave_requests.approved_by.
--
-- The original schema defined approved_by with a FOREIGN KEY to
-- admin_users.id, which rejected any employee id and caused the
-- employee-portal approval to fail with a constraint violation
-- (surfaced as "Approve Leave Request try again").
--
-- This migration:
--   1. Drops that FOREIGN KEY (whatever it is named) so approved_by can
--      hold either an admin_users.id or an employees.id.
--   2. Adds an approved_by_type ENUM('admin','employee') discriminator
--      (default 'admin' so all existing rows are interpreted correctly).
--
-- Read queries resolve the approver name by joining admin_users when
-- approved_by_type = 'admin' and employees when 'employee'.
--
-- This migration is idempotent and safe to re-run.

-- 1. Drop the approved_by foreign key (name is not fixed across installs).
SET @fk_name = (
    SELECT kcu.constraint_name
    FROM information_schema.key_column_usage kcu
    WHERE kcu.table_schema = DATABASE()
      AND kcu.table_name = 'leave_requests'
      AND kcu.column_name = 'approved_by'
      AND kcu.referenced_table_name IS NOT NULL
    LIMIT 1
);
SET @drop_fk_sql = IF(@fk_name IS NOT NULL,
    CONCAT('ALTER TABLE `leave_requests` DROP FOREIGN KEY `', @fk_name, '`'),
    'SELECT "approved_by foreign key already absent" AS message'
);
PREPARE stmt FROM @drop_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add the approved_by_type discriminator column.
SET @abt_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'leave_requests'
    AND column_name = 'approved_by_type');
SET @abt_sql = IF(@abt_exists = 0,
    'ALTER TABLE `leave_requests` ADD COLUMN `approved_by_type` ENUM(''admin'',''employee'') NOT NULL DEFAULT ''admin'' AFTER `approved_by`',
    'SELECT "approved_by_type column already exists" AS message');
PREPARE stmt FROM @abt_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
