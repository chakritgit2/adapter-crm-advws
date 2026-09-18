-- =========================================================
-- Employee Self-Service Portal — DB migration
-- Adds login columns to the employees table so employees can
-- authenticate against /login (separate from admin /loginhrm).
-- =========================================================

-- 1. Add auth columns.
ALTER TABLE employees
    ADD COLUMN username VARCHAR(255) NULL AFTER email,
    ADD COLUMN password_hash VARCHAR(255) NULL AFTER username,
    ADD COLUMN set_password_token VARCHAR(255) NULL AFTER password_hash,
    ADD COLUMN last_login_at DATETIME NULL AFTER set_password_token,
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER last_login_at,
    ADD INDEX idx_employees_email (email);

-- 2. Backfill username = code + '-' + email for existing rows.
--    Falls back to emp-{id} when code or email is NULL.
UPDATE employees
SET username = CONCAT(code, '-', email)
WHERE username IS NULL AND email IS NOT NULL AND code IS NOT NULL;

UPDATE employees
SET username = CONCAT('emp-', id)
WHERE username IS NULL;

-- 3. Apply the global unique constraint on username.
--    Resolve any duplicate username rows BEFORE running this
--    (e.g. append a suffix or the company_id to duplicates).
ALTER TABLE employees
    ADD UNIQUE KEY uq_employees_username (username);

-- =========================================================
-- SEED: Set default password "password" for all employees.
-- The hash matches LoginController::encryptPass('password'),
-- which is SHA256( base64_encode('password') ).
-- In MySQL: SHA2( TO_BASE64('password'), 256 )
-- All seeded accounts are flagged must_change_password = 1 so
-- the employee is forced to choose a new password on first login.
-- =========================================================

UPDATE employees
SET password_hash = SHA2(TO_BASE64('password'), 256),
    must_change_password = 1
WHERE password_hash IS NULL OR password_hash = '';

-- To set a specific employee password, run from PHP:
--   UPDATE employees SET password_hash = '<hash>' WHERE id = <id>;
-- where <hash> = LoginController::encryptPass('yourpassword')
-- (base64 + sha256, matching the admin_users convention).
