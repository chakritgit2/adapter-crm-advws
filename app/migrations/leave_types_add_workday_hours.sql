-- Add workday_hours column to leave_types table
-- Used to calculate minutes for whole-day leave requests

ALTER TABLE leave_types ADD COLUMN workday_hours DECIMAL(4,2) NOT NULL DEFAULT 8.00 AFTER default_allowance_minutes;
