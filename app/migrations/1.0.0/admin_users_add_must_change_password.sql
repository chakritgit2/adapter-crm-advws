-- Admin Users: add must_change_password column
-- When 1, the admin user is forced to change their password on next login
-- and the sidebar is hidden until they do. This mirrors the employees
-- must_change_password column used by the employee portal.
-- This migration is idempotent and safe to re-run.

SET @mcp_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'admin_users'
    AND column_name = 'must_change_password');

SET @mcp_sql = IF(@mcp_exists = 0,
    'ALTER TABLE `admin_users` ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `set_password_token`',
    'SELECT "must_change_password column already exists" AS message');
PREPARE stmt FROM @mcp_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
