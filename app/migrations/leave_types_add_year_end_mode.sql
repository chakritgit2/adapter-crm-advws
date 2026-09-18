-- Migration: Add year_end_mode column to leave_types table
-- Controls how each leave policy behaves during the year-end rollover process.
--   'carry_forward' = Carry balance forward (unused remaining is added to the new year's quota)
--   'reset'         = Treat like normal types (fresh rule-calculated quota each year, unused forfeited)
-- Defaults to 'reset' so existing policies keep the standard annual-reset behaviour
-- until a Super Admin opts in to carry-forward per policy.

ALTER TABLE `leave_types`
    ADD COLUMN `year_end_mode` ENUM('carry_forward','reset') NOT NULL DEFAULT 'reset'
    AFTER `is_toil`;
