# Database Schema — `hr`

Multi-tenant HR system schema. The top-level tenant is `tenants`; each tenant owns
`companies`, and most business data is scoped to a `company_id`. Admin/staff users
live in `admin_users` and are mapped to tenants/companies via `tenant_user_map` and
`company_user_map`. Employees, positions, leave, overtime, milestones, custom
attributes, and translations all hang off `companies`.

Conventions used in this document:

- **PK** — Primary Key
- **UQ** — Unique constraint
- **FK** — Foreign Key; the target table is noted in the column description
- Enum values are listed inline in the description
- Timestamps default to `current_timestamp()` unless noted otherwise

---

## Table of Contents

1. [tenants](#tenants)
2. [companies](#companies)
3. [admin_users](#admin_users)
4. [tenant_user_map](#tenant_user_map)
5. [company_user_map](#company_user_map)
6. [users](#users)
7. [employees](#employees)
8. [positions](#positions)
9. [position_assignments](#position_assignments)
10. [position_approvals](#position_approvals)
11. [matrix_connections](#matrix_connections)
12. [job_levels](#job_levels)
13. [custom_attributes](#custom_attributes)
14. [company_attribute_values](#company_attribute_values)
15. [employee_attribute_values](#employee_attribute_values)
16. [position_attribute_values](#position_attribute_values)
17. [leave_types](#leave_types)
18. [leave_balances](#leave_balances)
19. [leave_requests](#leave_requests)
20. [leave_allowance_rules](#leave_allowance_rules)
21. [overtime_policies](#overtime_policies)
22. [overtime_requests](#overtime_requests)
23. [milestone_event_types](#milestone_event_types)
24. [employee_milestones](#employee_milestones)
25. [tenant_languages](#tenant_languages)
26. [translations](#translations)
26. [email_outbox](#email_outbox)
27. [activity_logs](#activity_logs)

---

## tenants

Top-level multi-tenant boundary. A tenant represents the customer organization that
owns one or more `companies`. Tenants carry billing/plan metadata and a lifecycle
status. All `companies` belong to a tenant, and `job_levels` are scoped at the tenant
level (optionally overridden per company). Deleting a tenant cascades to its companies.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique tenant identifier. |
| `name` | varchar(255) | Display name of the tenant organization. |
| `slug` | varchar(255), UQ | URL-friendly unique identifier for the tenant. |
| `status` | enum('active','suspended'), default 'active' | Tenant lifecycle state. `active` = usable; `suspended` = access disabled (e.g. billing issues). |
| `plan` | varchar(50), nullable | Subscription/plan tier (e.g. free, pro, enterprise). Free-text. |
| `billing_email` | varchar(255), nullable | Contact email used for billing correspondence. |
| `created_at` | datetime, default current_timestamp() | When the tenant record was created. |

---

## companies

A company is a real business entity owned by a tenant. A tenant may run multiple
companies. Almost all business data (employees, positions, leave, overtime, custom
attributes, translations, activity logs) is scoped to a `company_id`. Each company has
a unique slug within its tenant. Deleting a company cascades to all child records.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique company identifier. |
| `tenant_id` | int(11), FK → `tenants.id` | Parent tenant. Cascades on delete. |
| `name` | varchar(255) | Company display name. |
| `slug` | varchar(255), UQ with `tenant_id` | URL-friendly identifier, unique per tenant. |
| `status` | enum('active','suspended'), default 'active' | Company lifecycle state. `active` = operational; `suspended` = disabled. |
| `created_at` | datetime, default current_timestamp() | When the company was created. |

**Unique key:** `(tenant_id, slug)` — slug uniqueness is enforced within a tenant.

---

## admin_users

Internal staff/admin accounts that log into the HR platform to manage tenants and
companies. Distinct from `users` (a legacy/external table). Roles are global on the
record itself; per-tenant and per-company roles are granted via `tenant_user_map` and
`company_user_map`. `set_password_token` supports invite/set-password flows.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique admin user identifier. |
| `name` | varchar(255) | Full display name of the admin user. |
| `email` | varchar(255), UQ | Login email; unique across all admin users. |
| `password_hash` | varchar(255) | Hashed password (e.g. bcrypt/argon2). Never stored in plaintext. |
| `role` | enum('Super Admin','Admin','Member',''), default 'Admin' | Global platform role. `Super Admin` = full platform access; `Admin` = standard staff; `Member` = limited; empty string = no global role (access granted only via maps). |
| `created_at` | datetime, default current_timestamp() | When the account was created. |
| `last_login_at` | datetime, nullable | Timestamp of the most recent successful login. |
| `set_password_token` | varchar(255), nullable | Single-use token for setting/resetting a password (invite or reset flow). |

---

## tenant_user_map

Maps `admin_users` to `tenants` with a tenant-scoped role. This grants an admin user
access to a specific tenant and defines what they can do within it. Composite primary
key `(admin_user_id, tenant_id)` ensures one role per user per tenant.

| Column | Type | Description |
| --- | --- | --- |
| `admin_user_id` | int(11), PK, FK → `admin_users.id` | The admin user being granted access. Cascades on delete. |
| `tenant_id` | int(11), PK | The tenant the user is granted access to. |
| `role` | varchar(50), default 'tenant_member' | Role within this tenant (e.g. `tenant_admin`, `tenant_member`). Free-text. |
| `created_at` | datetime, default current_timestamp() | When the mapping was created. |

---

## company_user_map

Maps `admin_users` to `companies` with a company-scoped role. This is the primary
grant for day-to-day HR work: an admin user gets access to a specific company and a
role within it. Composite primary key `(admin_user_id, company_id)` ensures one role
per user per company.

| Column | Type | Description |
| --- | --- | --- |
| `admin_user_id` | int(11), PK, FK → `admin_users.id` | The admin user being granted access. Cascades on delete. |
| `company_id` | int(11), PK, FK → `companies.id` | The company the user is granted access to. Cascades on delete. |
| `role` | varchar(50) | Role within this company (e.g. `company_admin`, `hr_manager`, `viewer`). Free-text. |

---

## users

Legacy/external user table, appears to originate from a separate application (uses
`latin1` charset, `agency_marketer`/`client` roles). Retained for compatibility but
not part of the core HR admin model — prefer `admin_users` for HR platform accounts.

| Column | Type | Description |
| --- | --- | --- |
| `id` | bigint(20) AUTO_INCREMENT, PK | Unique user identifier. |
| `name` | varchar(255), latin1 | Display name. |
| `email` | varchar(255), latin1, UQ | Login email; unique. |
| `password_hash` | varchar(255), latin1 | Hashed password. |
| `role` | enum('admin','agency_marketer','client'), default 'agency_marketer' | Legacy role from the originating app. `admin` = platform admin; `agency_marketer` = agency user; `client` = client user. |
| `created_at` | timestamp, default current_timestamp() | When the record was created. |

---

## employees

Core employee record for a company. Each employee has a stable `public_id` (UUID) for
external/API use and an internal integer `id`. `code` is the company-assigned employee
code (unique within a company by convention). `salary` is stored in cents to avoid
floating-point issues. Deleting a company cascades to its employees.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Internal employee identifier. |
| `public_id` | char(36), UQ | UUID exposed to clients/APIs instead of the internal id. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `code` | varchar(64) | Company-assigned employee code (e.g. payroll/staff number). |
| `first_name` | varchar(100) | Given name. |
| `last_name` | varchar(100) | Family name. |
| `gender` | varchar(20), nullable | Gender (free-text; may be 'male'/'female'/'other' etc.). |
| `employment_type` | varchar(50), nullable | Employment category (e.g. 'full_time', 'part_time', 'contract'). Free-text. |
| `email` | varchar(255), nullable | Work or personal email. |
| `phone` | varchar(50), nullable | Contact phone number. |
| `date_of_birth` | date, nullable | Date of birth. |
| `salary` | int(10) UNSIGNED | Salary in **cents** (e.g. 5000000 = $50,000.00). Always integer to avoid rounding errors. |

---

## positions

A position is a role/seat in a company's org chart. Positions self-reference via
`parent_position_id` to build the reporting hierarchy (solid-line manager). Dotted-line
(matrix) reporting is modeled separately in `matrix_connections`. Positions can require
approval before being active (`is_approved`). Deleting a company cascades to positions;
deleting a parent position sets the child's `parent_position_id` to NULL.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique position identifier. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `parent_position_id` | int(11), nullable, FK → `positions.id` | The manager/parent position in the org chart (solid-line reporting). Set to NULL on parent delete. |
| `job_title` | varchar(100) | Title shown for this position (e.g. "Engineering Manager"). |
| `department` | varchar(100), nullable | Department/functional grouping. |
| `job_level` | varchar(10), nullable | Job level code (see `job_levels.code`). Free-text reference. |
| `is_approved` | tinyint(1), default 0 | Whether the position has been approved (1) or is pending approval (0). See `position_approvals`. |

---

## position_assignments

Tracks which employee currently (or historically) holds a position. An assignment has
a `start_date` and an optional `end_date`; an open `end_date` means the employee
currently holds the position. This allows full assignment history per position and per
employee. Deleting an employee or position cascades to its assignments.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique assignment identifier. |
| `employee_id` | int(11), FK → `employees.id` | The employee assigned to the position. Cascades on delete. |
| `position_id` | int(11), FK → `positions.id` | The position being filled. Cascades on delete. |
| `start_date` | date | When the employee started in this position. |
| `end_date` | date, nullable | When the employee left this position. NULL = currently holds it. |

---

## position_approvals

Records approval votes cast by admin users for positions (used when a position requires
approval before becoming active — see `positions.is_approved`). Each admin user can
vote at most once per position (enforced by the unique key). Deleting a position or
admin user cascades to their approval records.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique approval record identifier. |
| `position_id` | int(11), FK → `positions.id` | The position being approved. Cascades on delete. |
| `admin_user_id` | int(11), FK → `admin_users.id` | The admin user who cast the approval. Cascades on delete. |
| `approved_at` | datetime, default current_timestamp() | When the approval was recorded. |

**Unique key:** `(position_id, admin_user_id)` — one vote per admin per position.

---

## matrix_connections

Models dotted-line (matrix) reporting relationships between positions, in addition to
the solid-line hierarchy in `positions.parent_position_id`. A connection goes from one
position to another with an optional label (default "Dotted Line"). Deleting a company
or either endpoint position cascades to the connection.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique connection identifier. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `from_position_id` | int(11), FK → `positions.id` | Source position of the dotted-line relationship. Cascades on delete. |
| `to_position_id` | int(11), FK → `positions.id` | Target position of the dotted-line relationship. Cascades on delete. |
| `connection_label` | varchar(100), default 'Dotted Line' | Human-readable label for the relationship type. |

---

## job_levels

Tenant-wide (optionally company-specific) catalog of job levels/grades. Each level has
a `code` (short, e.g. "L1", "M3"), a `category`, a display `name`, a `sort_order`, and
a `can_approve_leave` flag that marks which levels are permitted to approve leave
requests for employees they manage. Levels can be tenant-global (`company_id` NULL) or
scoped to a single company. The generated `company_scope_key` column lets the unique
key treat NULL `company_id` as 0 so a tenant-global level and a company-specific level
can coexist without collision.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(10) UNSIGNED AUTO_INCREMENT, PK | Unique job level identifier. |
| `tenant_id` | int(11), FK → `tenants.id` | Owning tenant. Cascades on delete. |
| `company_id` | int(10) UNSIGNED, nullable | If set, this level is scoped to that company; if NULL, it is tenant-global. |
| `code` | varchar(10) | Short stable code (e.g. "L1", "M3"). Unique within `(tenant, company_scope, code)`. |
| `category` | varchar(50) | Grouping category (e.g. "Individual Contributor", "Manager"). |
| `name` | varchar(100) | Display name (e.g. "Senior Engineer"). |
| `sort_order` | int(11), default 0 | Ordering for display/ranking (lower = earlier). |
| `is_active` | tinyint(1), default 1 | Whether the level is active and selectable. |
| `can_approve_leave` | tinyint(1), default 0 | Whether holders of this job level may approve `leave_requests` for employees they manage. Set per level in the Job Levels settings UI; tenant-global rows are seeded with approval rights for the Supervisor (O3) and all Executive/Management tiers. |
| `created_at` | datetime, default current_timestamp() | When the level was created. |
| `company_scope_key` | int(10) UNSIGNED, GENERATED VIRTUAL | `coalesce(company_id, 0)` — used by the unique key to treat NULL company as 0. |

**Unique key:** `(tenant_id, company_scope_key, code)` — code uniqueness within a tenant
and company scope (tenant-global vs. a specific company).

---

## custom_attributes

Defines custom, company-specific fields that can be attached to `companies`,
`employees`, or `positions`. Each attribute declares a `field_type` and, for dropdown
fields, a JSON array of choices in `dropdown_choice`. Flags control whether the
attribute is required, shown in list views, and shown on dashboards. The actual values
are stored in the per-entity `*_attribute_values` tables.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique attribute definition identifier. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `name` | varchar(100) | Display name of the custom field (e.g. "Shirt Size", "Bank Account"). |
| `attribute_entity` | enum('companies','employees','positions','') | Which entity this attribute applies to. Empty string is a legacy/invalid value. |
| `field_type` | enum('text','number','date','boolean','dropdown','email'), default 'text' | Data type of the field. `dropdown` uses `dropdown_choice` for allowed values. |
| `dropdown_choice` | longtext (JSON), default '[]' | JSON array of allowed choices when `field_type` = 'dropdown'. Validated by `json_valid`. |
| `is_required` | tinyint(1), default 0 | Whether a value is mandatory for this attribute. |
| `show_in_list` | tinyint(1), default 0 | Whether the attribute appears as a column in list views. |
| `show_in_dashboard` | tinyint(1), default 0 | Whether the attribute appears on dashboard widgets/cards. |

---

## company_attribute_values

Stores the actual value of a `custom_attributes` field for a specific `company`. One
row per (company, attribute) pair — enforced by the unique key. Deleting a company or
attribute cascades to its values.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique value record identifier. |
| `company_id` | int(11), FK → `companies.id` | The company this value belongs to. Cascades on delete. |
| `attribute_id` | int(11), FK → `custom_attributes.id` | The attribute definition this value is for. Cascades on delete. |
| `value` | text, nullable | The stored value (serialized as text regardless of `field_type`). |

**Unique key:** `(company_id, attribute_id)` — one value per company per attribute.

---

## employee_attribute_values

Stores the actual value of a `custom_attributes` field for a specific `employee`. One
row per (employee, attribute) pair. Deleting an employee or attribute cascades to its
values.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique value record identifier. |
| `employee_id` | int(11), FK → `employees.id` | The employee this value belongs to. Cascades on delete. |
| `attribute_id` | int(11), FK → `custom_attributes.id` | The attribute definition this value is for. Cascades on delete. |
| `value` | text, nullable | The stored value (serialized as text regardless of `field_type`). |

**Unique key:** `(employee_id, attribute_id)` — one value per employee per attribute.

---

## position_attribute_values

Stores the actual value of a `custom_attributes` field for a specific `position`. One
row per (position, attribute) pair. Deleting a position or attribute cascades to its
values.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique value record identifier. |
| `position_id` | int(11), FK → `positions.id` | The position this value belongs to. Cascades on delete. |
| `attribute_id` | int(11), FK → `custom_attributes.id` | The attribute definition this value is for. Cascades on delete. |
| `value` | text, nullable | The stored value (serialized as text regardless of `field_type`). |

**Unique key:** `(position_id, attribute_id)` — one value per position per attribute.

---

## leave_types

Company-defined leave categories (e.g. Annual, Sick, Maternity). Each type has a
default annual allowance in minutes and a `workday_hours` value used to convert
between days and minutes. TOIL (Time Off In Lieu) leave types are flagged with
`is_toil`. Deleting a company cascades to its leave types (and onward to balances and
requests).

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique leave type identifier. |
| `public_id` | char(36), UQ | UUID exposed to clients/APIs. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `name` | varchar(100) | Display name (e.g. "Annual Leave", "Sick Leave"). |
| `default_allowance_minutes` | int(11), default 0 | Default annual entitlement in minutes, used when seeding `leave_balances`. |
| `workday_hours` | decimal(4,2), default 8.00 | Standard workday length in hours; used to convert minutes ↔ days. |
| `is_active` | tinyint(1), default 1 | Whether the leave type is active and selectable for new requests. |
| `is_toil` | tinyint(1), default 0 | Whether this is a Time-Off-In-Lieu type (earned via overtime instead of granted annually). |

---

## leave_balances

Tracks an employee's leave entitlement and usage for a specific `leave_type` and
`year`. Allowance and used amounts are stored in **minutes** for precise partial-day
accounting. One row per (employee, leave_type, year). Deleting a company, employee, or
leave type cascades to balances.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique balance record identifier. |
| `public_id` | char(36), UQ | UUID exposed to clients/APIs. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `employee_id` | int(11), FK → `employees.id` | The employee the balance belongs to. Cascades on delete. |
| `leave_type_id` | int(11), FK → `leave_types.id` | The leave type. Cascades on delete. |
| `year` | year(4) | The leave year this balance applies to. |
| `allowance_minutes` | int(11), default 0 | Total entitled minutes for the year. |
| `used_minutes` | int(11), default 0 | Minutes already consumed by approved leave requests. |

**Unique key:** `(employee_id, leave_type_id, year)` — one balance per employee/type/year.

---

## leave_requests

A leave request submitted by an employee for a contiguous date range against a
`leave_type`. The requested duration is stored in minutes (`requested_minutes`).
Status flows `pending` → `approved`/`rejected`. `approved_by` records the admin user
who actioned the request. Deleting a company, employee, or leave type cascades;
deleting the approver sets `approved_by` to NULL (preserving the request history).

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique request identifier. |
| `public_id` | char(36), UQ | UUID exposed to clients/APIs. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `employee_id` | int(11), FK → `employees.id` | The employee requesting leave. Cascades on delete. |
| `leave_type_id` | int(11), FK → `leave_types.id` | The leave type being requested. Cascades on delete. |
| `start_date` | datetime | Start of the leave period (datetime to support partial-day/half-day leave). |
| `end_date` | datetime | End of the leave period (inclusive). |
| `requested_minutes` | int(11) | Total minutes of leave requested across the range. |
| `status` | enum('pending','approved','rejected'), default 'pending' | Workflow state. `pending` = awaiting action; `approved` = granted; `rejected` = denied. |
| `reason` | text, nullable | Employee-provided reason for the leave. |
| `attachment_url` | varchar(255), nullable | URL to an uploaded supporting document (e.g. medical certificate). |
| `approved_by` | int(11), nullable, FK → `admin_users.id` | Admin user who approved/rejected. Set to NULL on approver delete. |
| `created_at` | datetime, default current_timestamp() | When the request was submitted. |

---

## leave_allowance_rules

Conditional allowance overrides for a `leave_types` policy, based on employee
position, job level, and/or tenure. Rules are evaluated per employee in
`priority DESC` order; the first matching rule wins. If no rule matches, the
`leave_types.default_allowance_minutes` is used. Deleting a company or leave
type cascades to its rules; deleting a position sets `position_id` to NULL
(preserving the rule as a position-agnostic rule).

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique rule identifier. |
| `public_id` | char(36), UQ | UUID exposed to clients/APIs. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `leave_type_id` | int(11), FK → `leave_types.id` | The policy this rule applies to. Cascades on delete. |
| `position_id` | int(11), nullable, FK → `positions.id` | Specific position match (NULL = any position). Set NULL on position delete. |
| `job_level` | varchar(10), nullable | Match by job level code (NULL = any level). Free-text, references `job_levels.code`. |
| `min_tenure_months` | int(11), nullable | Minimum tenure in months from join date (NULL = no minimum). |
| `max_tenure_months` | int(11), nullable | Maximum tenure in months (NULL = no maximum). |
| `allowance_minutes` | int(11) | The quota in minutes for employees matching this rule. |
| `priority` | int(11), default 0 | Evaluation order — higher values evaluated first. |
| `is_active` | tinyint(1), default 1 | Whether the rule is active. |
| `created_at` | datetime, default current_timestamp() | When the rule was created. |

**Migration:** `app/migrations/leave_allowance_rules.sql`

---

## overtime_policies

Company-defined overtime compensation policies. Each policy has a `multiplier` applied
to overtime hours (e.g. 1.50 = time-and-a-half). Policies are referenced by
`overtime_requests`. Deleting a company cascades to its policies.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique policy identifier. |
| `public_id` | char(36), UQ | UUID exposed to clients/APIs. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `name` | varchar(100) | Display name (e.g. "Weekday OT 1.5x", "Holiday OT 2x"). |
| `multiplier` | decimal(4,2), default 1.00 | Pay/credit multiplier applied to overtime minutes. |
| `is_active` | tinyint(1), default 1 | Whether the policy is active and selectable for new requests. |
| `created_at` | datetime, default current_timestamp() | When the policy was created. |

---

## overtime_requests

A request from an employee to record overtime work against an `overtime_policies`
policy. The worked duration is stored in minutes. The employee chooses how to be
compensated: `payout` (paid) or `toil` (Time Off In Lieu — credited as leave).
Status flows `pending` → `approved`/`rejected`. Deleting a company or employee
cascades; deleting the approver sets `approved_by` to NULL.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique request identifier. |
| `public_id` | char(36), UQ | UUID exposed to clients/APIs. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `employee_id` | int(11), FK → `employees.id` | The employee who worked the overtime. Cascades on delete. |
| `start_time` | datetime | When the overtime period started. |
| `end_time` | datetime | When the overtime period ended. |
| `overtime_policy_id` | int(11), FK → `overtime_policies.id` | The policy applied (determines the multiplier). |
| `worked_minutes` | int(11) | Total minutes of overtime worked. |
| `compensation_type` | enum('payout','toil'), default 'payout' | How the employee is compensated. `payout` = paid out; `toil` = credited as Time Off In Lieu leave. |
| `status` | enum('pending','approved','rejected'), default 'pending' | Workflow state. `pending` = awaiting action; `approved` = granted; `rejected` = denied. |
| `reason` | text, nullable | Employee-provided reason for the overtime. |
| `approved_by` | int(11), nullable, FK → `admin_users.id` | Admin user who approved/rejected. Set to NULL on approver delete. |
| `created_at` | datetime, default current_timestamp() | When the request was submitted. |

---

## milestone_event_types

Company-scoped catalog of milestone event categories (e.g. "Hired", "Promotion",
"Work Anniversary"). Supports a localized Thai name (`name_th`) and a color tag for UI.
A NULL `company_id` could represent a system-provided default type. Deleting a company
cascades to its event types.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique event type identifier. |
| `company_id` | int(11), nullable, FK → `companies.id` | Owning company, or NULL for a system-default type. Cascades on delete. |
| `name` | varchar(100) | Display name (English). |
| `name_th` | varchar(100), nullable | Thai-language display name. |
| `color_tag` | varchar(50), nullable | UI color label/identifier for the event type. |
| `is_active` | tinyint(1), default 1 | Whether the event type is active and selectable. |
| `created_at` | datetime, default current_timestamp() | When the type was created. |

---

## employee_milestones

Records a dated milestone event for an employee (e.g. hire date, promotion,
anniversary). The `event_type_id` references `milestone_event_types`. Optional
`description` and a JSON `metadata` field carry extra context. Deleting a company or
employee cascades; deleting an event type is restricted (no cascade) to preserve
historical records.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique milestone record identifier. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `employee_id` | int(11), FK → `employees.id` | The employee the milestone belongs to. Cascades on delete. |
| `event_type_id` | int(11), FK → `milestone_event_types.id` | The type of milestone. Not cascaded (preserves history). |
| `event_date` | date | When the milestone occurred. |
| `description` | varchar(255), nullable | Optional human-readable note about the milestone. |
| `metadata` | longtext (JSON), nullable | Optional structured extra data. Validated by `json_valid`. |
| `created_at` | datetime, default current_timestamp() | When the milestone record was created. |

---

## tenant_languages

Languages enabled for a company (used to drive available translations/UI locales).
One row per (company, language_code). Deleting a company cascades to its language
settings.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique record identifier. |
| `company_id` | int(11), FK → `companies.id` | The company this language is enabled for. Cascades on delete. |
| `language_code` | varchar(10) | BCP-47 / locale code (e.g. "en", "th", "en-US"). |
| `language_name` | varchar(50) | Human-readable language name (e.g. "English", "Thai"). |
| `is_active` | tinyint(1), default 1 | Whether the language is currently enabled for the company. |
| `created_at` | datetime, default current_timestamp() | When the language was enabled. |

**Unique key:** `(company_id, language_code)` — one record per language per company.

---

## translations

Stores translated overrides for a specific field value on a specific row. The
`target_table` + `target_column` + `target_id` triple identifies the source value, and
`translation_value` holds the translated text for a given `language_code`. This is a
generic, row-level translation mechanism (e.g. translating a `positions.job_title` or
`milestone_event_types.name` into Thai). Deleting a company cascades to its
translations.

| Column | Type | Description |
| --- | --- | --- |
| `id` | bigint(20) AUTO_INCREMENT, PK | Unique translation record identifier. |
| `company_id` | int(11), FK → `companies.id` | Owning company. Cascades on delete. |
| `language_code` | varchar(10) | The language/locale this translation is for (e.g. "th"). |
| `target_table` | varchar(64) | The table containing the row being translated (e.g. "positions"). |
| `target_column` | varchar(64) | The column whose value is being translated (e.g. "job_title"). |
| `target_id` | int(11) | The primary key value of the target row. |
| `translation_value` | text | The translated text. |
| `created_at` | datetime, default current_timestamp() | When the translation was created. |
| `updated_at` | datetime, default current_timestamp() ON UPDATE | When the translation was last updated. |

**Index:** `(company_id, target_table, target_column, target_id, language_code)` —
optimized lookup for resolving a translation at render time.

---

## email_outbox

Asynchronous email queue. Application code writes rows here instead of sending SMTP
synchronously; a background worker picks up `new` rows, sends them, and updates
status. Supports retries via `retry_count` and `next_retry_at`, with `error_message`
capturing failures. Indexes are tuned for the worker queue and for purging old
successful/failed rows.

| Column | Type | Description |
| --- | --- | --- |
| `id` | bigint(20) AUTO_INCREMENT, PK | Unique outbox record identifier. |
| `recipient` | varchar(255) | Recipient email address. |
| `subject` | varchar(255) | Email subject line. |
| `body` | text | Email body (HTML or plain text). |
| `status` | enum('new','processing','success','error','failed'), default 'new' | Worker state. `new` = queued; `processing` = being sent; `success` = sent; `error` = transient failure (will retry); `failed` = permanent failure (no more retries). |
| `retry_count` | tinyint(4), default 0 | Number of send attempts so far. |
| `next_retry_at` | datetime, nullable | When the worker should next attempt a retry (for backoff). |
| `error_message` | text, nullable | Last error message from a failed send attempt. |
| `created_at` | timestamp, default current_timestamp() | When the email was queued. |
| `updated_at` | timestamp, default current_timestamp() ON UPDATE | When the row was last updated (status change, retry, etc.). |

**Indexes:**

- `idx_worker_queue (status, next_retry_at)` — used by the worker to find the next
  eligible row to send.
- `idx_purge_cleanup (status, updated_at)` — used to purge old `success`/`failed`
  rows.

---

## activity_logs

Append-only audit trail of admin actions. Each row captures who
(`admin_user_id`) did what (`action`) to which row (`target_table`, `target_id`),
along with before/after snapshots in `old_values`/`new_values` (JSON). Deleting a
company or admin user cascades to their log entries.

`company_id` is **nullable** to support platform-level actions
that are not company-scoped. Company-scoped actions
written by `DashboardController::logActivity()` always set a real `company_id`. The migration
`app/migrations/1.0.0/activity_logs_nullable_company_id.sql` makes the column
nullable and re-adds the cascading FK.

| Column | Type | Description |
| --- | --- | --- |
| `id` | int(11) AUTO_INCREMENT, PK | Unique log entry identifier. |
| `company_id` | int(11), nullable, FK → `companies.id` | The company the action was performed in, or `NULL` for platform-level actions. Cascades on delete. |
| `admin_user_id` | int(11), FK → `admin_users.id` | The admin user who performed the action. Cascades on delete. |
| `action` | varchar(50) | The action verb (e.g. "create", "update", "delete", "approve"). |
| `target_table` | varchar(50) | The table that was affected. |
| `target_id` | int(11) | The primary key of the affected row. |
| `old_values` | longtext (JSON), nullable | Snapshot of the row before the change. Validated by `json_valid`. |
| `new_values` | longtext (JSON), nullable | Snapshot of the row after the change. Validated by `json_valid`. |
| `created_at` | datetime, default current_timestamp() | When the action occurred. |
