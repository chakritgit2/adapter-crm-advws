-- DESTRUCTIVE DATABASE RETIREMENT - REVIEW AND BACK UP BEFORE RUNNING.
--
-- The requested tables are actively referenced by the current HR/CRM codebase.
-- Running this migration will disable employee, leave, overtime, position,
-- company-holiday, milestone, job-level, translation, and related reporting
-- features until their controllers, routes, views, services, and models are
-- retired or replaced.
--
-- The legacy Users, ClientUsers, and AuthService code paths have already been
-- retired. client_users is included defensively because it is not part of the
-- current schema snapshot but may exist in an older database.
--
-- This file is intentionally not executed by the agent.

SET FOREIGN_KEY_CHECKS = 0;

-- Requested tables with dependent records first.
DROP TABLE IF EXISTS translations;
DROP TABLE IF EXISTS overtime_requests;
DROP TABLE IF EXISTS leave_allowance_rules;
DROP TABLE IF EXISTS leave_balances;
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS employee_attribute_values;
DROP TABLE IF EXISTS employee_milestones;
DROP TABLE IF EXISTS position_approvals;
DROP TABLE IF EXISTS position_assignments;
DROP TABLE IF EXISTS matrix_connections;
DROP TABLE IF EXISTS company_holidays;

-- Requested parent/domain tables.
DROP TABLE IF EXISTS milestone_event_types;
DROP TABLE IF EXISTS overtime_policies;
DROP TABLE IF EXISTS leave_types;
DROP TABLE IF EXISTS job_levels;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS position_attribute_values;
DROP TABLE IF EXISTS client_users;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;
