Based on the sources, your multi-tenant administrative system is broken down into four core data domains to ensure secure and logical boundaries:
1. The Tenant / Organization Domain This is the foundational layer of your system
. Every piece of data ties back to this domain to ensure strict data isolation so that one company cannot see another company's data
.
Core Entities: The core Company itself (e.g., "Acme Corp"), and optionally Locations or Branches if a company requires separate sub-charts
.
2. The Identity & Access Management (IAM) Domain Since this is an admin system, this domain handles robust access control for the people logging into the backend, who are not necessarily the employees displayed on the charts
.
Core Entities: AdminUsers (the administrators building the charts), Roles & Permissions (defining what an admin can view or edit), and a CompanyUserMap pivot table that links which admin is allowed to manage which specific company
.
3. The Human Resources Domain (The Org Chart Core) This domain holds the actual structure and data of the organizational canvas
.
Core Entities: Initially, this included Employees, Departments, and Positions/Roles
. However, under your fully normalized "Enterprise Approach," this domain is decoupled into three specific structures: Positions (holding the chart structure and reporting lines), Employees (holding strictly human identity data), and Position Assignments (a history ledger linking the human to the position for a specific time period). This domain also includes the Leave Management subsystem: Leave Types (company-defined leave categories with default allowances), Leave Balances (per-employee, per-year entitlement and usage ledger stored in minutes), and Leave Requests (the pending → approved/rejected approval workflow that debits balances on approval). See Employee-Leave-Management.md for the full blueprint
.
4. The Audit & Compliance Domain (The Paper Trail) HR departments require strict compliance, so this domain tracks an exact paper trail of any changes made to the charts
.
Core Entities: An ActivityLog (or AuditTrail) that records every Create, Update, and Delete action
. This logs exactly who made the change (AdminUser ID), when it happened (timestamp), and the specific old and new values of the data
.