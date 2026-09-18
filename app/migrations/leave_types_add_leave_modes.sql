-- Migration: Add per-mode enable flags to leave_types table
-- Controls which leave request modes an employee may use for each policy:
--   allow_hourly     = Hourly Leave (start datetime + number of hours)
--   allow_whole_day  = Whole Day Leave (single date)
--   allow_multi_day  = Multi-day Leave (start date + end date)
-- All default to 1 (enabled) so existing policies keep their current behaviour
-- (all three modes available) until an admin turns a mode off per policy.

ALTER TABLE `leave_types`
    ADD COLUMN `allow_hourly` TINYINT(1) NOT NULL DEFAULT 1 AFTER `year_end_mode`,
    ADD COLUMN `allow_whole_day` TINYINT(1) NOT NULL DEFAULT 1 AFTER `allow_hourly`,
    ADD COLUMN `allow_multi_day` TINYINT(1) NOT NULL DEFAULT 1 AFTER `allow_whole_day`;
