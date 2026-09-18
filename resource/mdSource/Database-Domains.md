Here is the officially updated blueprint for your Database Domains.

This revision fully integrates the **Enterprise Normalization** approach (decoupling the "Human" from the "Seat") and incorporates the new **Multi-Approval Workflow** for your Super Admins.

---

### **1. The Tenant Domain (The Core Anchor)**

This domain serves as the top of the pyramid. Every other domain connects back here to ensure Company A never sees Company B's data.

**Table:** `companies`

* **Purpose:** Stores the core legal or display name of the organization and its status.
* **Structure:** Utilizes an auto-incrementing `id` as the primary key.
* **Columns:** Includes `name`, a `status` enum (defaulting to 'active' or 'suspended'), and a `created_at` timestamp.

---

### **2. Identity & Access Management (IAM) Domain**

These tables manage your system administrators and precisely which companies they are allowed to manage.

**Table:** `admin_users`

* **Purpose:** Holds the credentials and roles for the administrators managing the charts.
* **Structure:** Uses an auto-incrementing `id` as the primary key and features a unique key on the `email` column.
* **Columns:** Includes `name`, `email`, `password_hash`, and a `role` enum (e.g., 'Super Admin', 'Admin', 'Member').

**Table:** `users`

* **Purpose:** An additional user credential table, likely for broader platform access.
* **Structure:** Uses an auto-incrementing `id` as the primary key and enforces a unique `email`.
* **Columns:** Includes `name`, `email`, `password_hash`, and a `role` enum (e.g., 'admin', 'agency_marketer', 'client').

**Table:** `company_user_map` (The Pivot Table)

* **Purpose:** This pivot table acts as the multi-tenant link, mapping `admin_users` to the specific `companies` they are authorized to access.
* **Structure:** Uses a composite primary key consisting of `admin_user_id` and `company_id` to ensure administrators are not assigned to the same company twice.
* **Foreign Keys:** Links to the `admin_users` table and the `companies` table, with both relationships set to cascade on deletion (`ON DELETE CASCADE`).
* **Columns:** Includes a `role` column to define specific permissions within that mapping.

---

### **3. The Human Resources Domain (The Enterprise Canvas)**

This domain has been fully normalized to decouple the "Human" from the "Seat," giving you enterprise-level tracking for career histories and vacant positions.

**Table:** `positions`

* **Purpose:** Defines the structural "seats" of the company and maps out the reporting hierarchy on your D3.js chart.
* **Structure:** Uses an auto-incrementing `id` as the primary key.
* **Multi-Tenant Isolation:** Every record carries a `company_id` referencing the `companies` table (`ON DELETE CASCADE`).
* **Hierarchy Mapping:** Uses a `parent_position_id` to designate which role this seat reports to (`ON DELETE SET NULL`).
* **Columns:** Includes `job_title`, `department`, and crucially, the new `is_approved` boolean flag to track if the position is authorized for hiring.

**Table:** `employees`

* **Purpose:** Stores pure identity data for the human beings. It completely ignores job titles or managers.
* **Structure:** Uses an auto-incrementing `id` as the primary key.
* **Columns:** Includes `company_id` for multi-tenant isolation, `first_name`, and `last_name`.

**Table:** `position_assignments` (The History Ledger)

* **Purpose:** Acts as the historical map joining a human to a seat for a specific timeframe.
* **Structure:** Uses an auto-incrementing `id` as the primary key.
* **Foreign Keys:** Connects to `employee_id` and `position_id` (`ON DELETE CASCADE`).
* **Columns:** Includes `start_date` and `end_date`. If `end_date` is NULL, the employee is currently active in that position.

---

### **3b. The Leave Management Domain (Time-Off Subsystem)**

This domain handles the full leave lifecycle: defining leave categories, tracking
per-employee entitlements, and running the request → approval workflow. All three
tables are scoped to `company_id` for multi-tenant isolation and expose a UUID
`public_id` for external references. See
[`Employee-Leave-Management.md`](./Employee-Leave-Management.md) for the complete
implementation blueprint.

**Table:** `leave_types`

* **Purpose:** Company-defined leave categories (e.g. Annual, Sick, Maternity)
  with a default annual allowance and a workday-hours conversion factor.
* **Structure:** Auto-incrementing `id` PK; unique `public_id` (UUID) for
  API/client use.
* **Foreign Keys:** `company_id` → `companies` (`ON DELETE CASCADE`).
* **Columns:** `name`, `default_allowance_minutes` (int, the seed allowance in
  minutes), `workday_hours` (decimal, converts days ↔ minutes), `is_active`
  (whether selectable for new requests), `is_toil` (flags Time-Off-In-Lieu types
  fed by approved overtime).

**Table:** `leave_balances`

* **Purpose:** Per-employee, per-leave-type, per-year entitlement and usage
  ledger, stored entirely in **minutes** for precise partial-day accounting.
* **Structure:** Auto-incrementing `id` PK; unique `public_id` (UUID).
* **Foreign Keys:** `company_id` → `companies`, `employee_id` → `employees`,
  `leave_type_id` → `leave_types` (all `ON DELETE CASCADE`).
* **Unique Key:** `(employee_id, leave_type_id, year)` — one balance per
  employee/type/year.
* **Columns:** `year`, `allowance_minutes` (total entitled), `used_minutes`
  (consumed by approved requests). Remaining is derived:
  `allowance_minutes - used_minutes`.

**Table:** `leave_requests`

* **Purpose:** A submitted leave request for a contiguous date range, flowing
  `pending → approved/rejected`.
* **Structure:** Auto-incrementing `id` PK; unique `public_id` (UUID).
* **Foreign Keys:** `company_id` → `companies`, `employee_id` → `employees`,
  `leave_type_id` → `leave_types` (all `ON DELETE CASCADE`); `approved_by` →
  `admin_users` (`ON DELETE SET NULL` to preserve history).
* **Columns:** `start_date` / `end_date` (datetime, supports partial-day leave),
  `requested_minutes` (total minutes across the range), `status`
  (enum `pending`/`approved`/`rejected`), `reason`, `attachment_url`,
  `approved_by`, `created_at`.
* **Workflow:** On approval, a transaction sets `status = 'approved'` and
  increments the matching `leave_balances.used_minutes` by `requested_minutes`
  for the year of `start_date`. Rejection updates status only.

---

### **4. Audit & Compliance Domain (The Paper Trail)**

Because this is an HR system, a reliable paper trail for structural changes and budget approvals is non-negotiable.

**Table:** `position_approvals` *(NEW)*

* **Purpose:** Acts as the digital signature sheet for the Multi-Approval Workflow. It tracks exactly which Super Admins signed off on a newly created position.
* **Structure:** Uses an auto-incrementing `id` as the primary key. Features a **Unique Key** on `(position_id, admin_user_id)` to physically prevent double-voting.
* **Foreign Keys:** Links to the `positions` and `admin_users` tables (`ON DELETE CASCADE`).
* **Columns:** Includes an `approved_at` timestamp.

**Table:** `activity_logs`

* **Purpose:** Logs system-wide actions (such as 'CREATE', 'UPDATE', or 'DELETE') and stores the before and after states.
* **Structure:** Uses an auto-incrementing `id` as the primary key.
* **Foreign Keys:** Connects back to `companies` and `admin_users` so every action is tied to a specific tenant and a specific administrator, cascading on deletion.
* **Tracking Columns:** Records the `action`, the `target_table` (e.g., 'position_assignments'), and the exact `target_id` that was modified.
* **Data Snapshots:** Captures state changes using `old_values` and `new_values` columns, validated strictly as JSON formats.