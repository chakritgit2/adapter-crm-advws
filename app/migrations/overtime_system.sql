-- Overtime System Migration
-- Run this against the `hr` database before deploying the new code.
-- This migration is idempotent and safe to re-run.

-- Add is_toil flag to leave_types if it does not exist
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'leave_types'
    AND column_name = 'is_toil');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `leave_types` ADD COLUMN `is_toil` BOOLEAN NOT NULL DEFAULT FALSE AFTER `is_active`',
    'SELECT "is_toil column already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Table structure for table overtime_policies
CREATE TABLE IF NOT EXISTS overtime_policies (
    id INT(11) NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    company_id INT(11) NOT NULL,
    name VARCHAR(100) NOT NULL,
    multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_public_id (public_id),
    KEY company_id (company_id),
    CONSTRAINT ovt_policy_ibfk_1 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Table structure for table overtime_requests
CREATE TABLE IF NOT EXISTS overtime_requests (
    id INT(11) NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    company_id INT(11) NOT NULL,
    employee_id INT(11) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    overtime_policy_id INT(11) NOT NULL,
    worked_minutes INT(11) NOT NULL,
    compensation_type ENUM('payout', 'toil') NOT NULL DEFAULT 'payout',
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reason TEXT DEFAULT NULL,
    approved_by INT(11) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_public_id (public_id),
    KEY company_id (company_id),
    KEY employee_id (employee_id),
    KEY overtime_policy_id (overtime_policy_id),
    KEY approved_by (approved_by),
    CONSTRAINT overtime_req_ibfk_1 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT overtime_req_ibfk_2 FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE CASCADE,
    CONSTRAINT overtime_req_ibfk_3 FOREIGN KEY (approved_by) REFERENCES admin_users (id) ON DELETE SET NULL,
    CONSTRAINT overtime_req_policy_fk FOREIGN KEY (overtime_policy_id) REFERENCES overtime_policies (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
