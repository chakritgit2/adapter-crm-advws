# Help Center for Super Admin — Implementation Procedure

This document describes the procedure for adding a **Help Center** to the Admin
Dashboard, gated to the **Super Admin** role. The Help Center is a single
landing page (plus optional sub-pages) that gives the platform's most privileged
users a consolidated place to find operational documentation, system status,
support contacts, and administrative quick links.

It is intentionally **Super Admin-only**. Standard `Admin` and `Member` users
(see [`Database-Schema.md`](./Database-Schema.md) → `admin_users.role`) do not
see the link or the page. This keeps sensitive operational/system-level
information away from company-scoped staff.

---

## 1. Super Admin System Clues (Existing Conventions)

Before implementing, these are the established patterns the new feature **must**
follow. They were extracted from the codebase and the existing
[`mdSource`](./) documentation.

### 1.1 Role Model

| Source | Reference |
| --- | --- |
| `app/models/AdminUsers.php` | `ROLE_SUPER_ADMIN = 'Super Admin'` (const). Other roles: `Admin`, `Member`. |
| `app/migrations/1.0.0/tenant_system.sql` | Backfills `tenant_user_map` so `Super Admin` users become `tenant_admin` on the Default tenant. |
| `Database-Schema.md` → `admin_users` | `role` is `enum('Super Admin','Admin','Member','')`, default `'Admin'`. `Super Admin` = full platform access. |

The role string `'Super Admin'` is the single source of truth. It is stored on
the `admin_users` row itself (global role), independent of the per-tenant /
per-company grants in `tenant_user_map` and `company_user_map`.

### 1.2 Detecting Super Admin in a Controller

Both `IndexController` and `DashboardController` use the **same** private helper:

```php
private function isSuperAdmin(): bool
{
    $user = $this->session->get('auth');
    return ($user['role'] ?? '') === 'Super Admin';
}
```

References:
- <ref_snippet file="/c:/Users/WD11/Documents/Windsurf/hr.advws.com/app/controllers/IndexController.php" lines="240-244" />
- <ref_snippet file="/c:/Users/WD11/Documents/Windsurf/hr.advws.com/app/controllers/DashboardController.php" lines="1999-2003" />

The Help Center controller action must reuse this exact pattern. Do **not**
invent a new role-check mechanism.

### 1.3 Gating an Action (the Flash + Redirect Pattern)

When a non-Super Admin hits a Super Admin-only route, the established behavior
is: set an error flash and redirect back to the safe list page. Example from
`adminUsersCreateAction`:

```php
public function adminUsersCreateAction()
{
    if (!$this->isSuperAdmin()) {
        $this->flashSession->error($this->locale->t('flash.super_admin_only_create_admin'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
        return;
    }
    // ... action body
}
```

The Help Center must follow the same guard. Because the Help Center is a
read-only landing page (no "list" to return to), redirect to the dashboard root
on failure.

### 1.4 Passing `isSuperAdmin` to the View

Controllers set a view variable so Volt templates can conditionally render
Super Admin-only UI:

```php
$this->view->setVar('isSuperAdmin', $this->isSuperAdmin());
```

Used in `companies.volt` and `admin-users-list.volt` to show/hide Create / Edit
/ Delete buttons.

### 1.5 Routing

All dashboard routes live in <ref_file file="/c:/Users/WD11/Documents/Windsurf/hr.advws.com/app/config/router.php" />,
inside the `$tenantGroup` (`controller => 'dashboard'`). The URL shape is:

```
/{tenant_slug}/{company_slug}/dashboard/<action>
```

Settings sub-pages use `/dashboard/settings/<section>`. The Help Center is a
top-level dashboard concern (not a per-company setting), so it belongs at
`/dashboard/help-center`.

### 1.6 View Layout & Sidebar

- All dashboard views extend `layouts/admin.volt`.
- The sidebar nav is defined inline in <ref_file file="/c:/Users/WD11/Documents/Windsurf/hr.advws.com/app/views/layouts/admin.volt" />.
- Settings cards are rendered as a Tailwind grid in
  <ref_file file="/c:/Users/WD11/Documents/Windsurf/hr.advws.com/app/views/dashboard/settings/index.volt" />.

### 1.7 Translations

- Catalogs: `app/lang/en.php`, `app/lang/th.php`.
- Fragments: `app/lang/_fragments/*.php` (included by `en.php` / `th.php`).
- Usage in PHP: `$this->locale->t('key')`.
- Usage in Volt: `{{ t('key') }}`.
- Super Admin permission flashes live under `flash.super_admin_only_*`
  (see `app/lang/_fragments/controllers_flash.php`).

### 1.8 Documentation Style

Existing `mdSource` procedure docs (e.g.
[`Employee-Leave-Management.md`](./Employee-Leave-Management.md),
[`Companies-System.md`](./Companies-System.md)) follow this structure:
overview → schema → controller → view → routes → translations → rollout
checklist. This document mirrors that.

---

## 2. Help Center Scope

The Help Center is a **Super Admin operational hub**. It is **not** a per-company
setting and does **not** store tenant data. v1 content:

| Section | Purpose | Source of truth |
| --- | --- | --- |
| Getting Started | Quick-start pointers for new Super Admins. | Static content in the Volt view. |
| Admin Guide | Links to the operational docs in `resource/mdSource/` (Companies, Leave, Overtime, Positions, etc.). | Links to `mdSource` files. |
| System Status | High-level platform health (tenant count, company count, admin user count, last login). | Live queries against `tenants`, `companies`, `admin_users`. |
| Role & Permissions Reference | What `Super Admin` / `Admin` / `Member` can do. | Static table mirroring `admin_users.role`. |
| Support | Contact / ticket channel for the platform operator. | Static content (configurable via translation keys). |

Future extensions (out of scope for v1): searchable FAQ, embedded markdown
rendering of `mdSource` files, audit log viewer, platform-wide alert banner.

### Design Decisions

| Concern | Decision |
| --- | --- |
| Storage | No new tables. The Help Center is a read-only view + a few aggregate queries. Keeps the feature zero-migration. |
| Access | Super Admin only. Guarded by `isSuperAdmin()` in the controller and by hiding the sidebar link for other roles. |
| Routing | `/dashboard/help-center` (GET). Registered in the existing `$tenantGroup`. |
| Layout | Extends `layouts/admin.volt`. Uses the same Tailwind card grid style as `settings/index.volt`. |
| Multi-language | All visible strings go through `t('help_center.*')` keys in `en.php` / `th.php`. |
| Tenant scope | The page works at the tenant level. `currentTenantSlug` is used for the URL; company scope is irrelevant. |

---

## 3. Routes

Add to <ref_file file="/c:/Users/WD11/Documents/Windsurf/hr.advws.com/app/config/router.php" /> inside the `$tenantGroup` block, near the other top-level dashboard routes (after the `/dashboard/settings` route):

```php
// Help Center (Super Admin only)
$tenantGroup->addGet('/dashboard/help-center', [
    'action' => 'helpCenter'
]);
```

No POST routes are required for v1 (the page is read-only).

---

## 4. Controller Action

Add `helpCenterAction` to <ref_file file="/c:/Users/WD11/Documents/Windsurf/hr.advws.com/app/controllers/DashboardController.php" />, next to the other dashboard actions. The `isSuperAdmin()` helper already exists at line 1999 of that file — reuse it.

```php
/**
 * Help Center — Super Admin operational hub.
 * Route: GET /{tenant_slug}/{company_slug}/dashboard/help-center
 */
public function helpCenterAction()
{
    // 1. Role guard — Super Admin only.
    if (!$this->isSuperAdmin()) {
        $this->flashSession->error($this->locale->t('flash.super_admin_only_help_center'));
        $this->response->redirect($this->tenantUrl('/dashboard'));
        return;
    }

    // 2. Platform-wide status counts (tenant level, not company scoped).
    $tenantCount = (int) $this->db->fetchOne(
        "SELECT COUNT(*) AS c FROM tenants",
        Phalcon\Db\Enum::FETCH_ASSOC
    )['c'];

    $companyCount = (int) $this->db->fetchOne(
        "SELECT COUNT(*) AS c FROM companies",
        Phalcon\Db\Enum::FETCH_ASSOC
    )['c'];

    $adminUserCount = (int) $this->db->fetchOne(
        "SELECT COUNT(*) AS c FROM admin_users",
        Phalcon\Db\Enum::FETCH_ASSOC
    )['c'];

    $superAdminCount = (int) $this->db->fetchOne(
        "SELECT COUNT(*) AS c FROM admin_users WHERE role = 'Super Admin'",
        Phalcon\Db\Enum::FETCH_ASSOC
    )['c'];

    // 3. Curated links to the operational documentation in resource/mdSource.
    //    Keep this list in sync with the files actually present in mdSource.
    $docLinks = [
        ['label' => 'Companies System',            'file' => 'Companies-System.md'],
        ['label' => 'Company Multi-Tenant',        'file' => 'Company-Multi-Tenant.md'],
        ['label' => 'Dashboard',                   'file' => 'Dashboard.md'],
        ['label' => 'Database Schema',             'file' => 'Database-Schema.md'],
        ['label' => 'Employee Leave Management',   'file' => 'Employee-Leave-Management.md'],
        ['label' => 'Employee Attributes',         'file' => 'Employee-Attributes.md'],
        ['label' => 'Employee Milestone',          'file' => 'Employee-Milestone.md'],
        ['label' => 'Employee Over Time',          'file' => 'Employee-Over-Time.md'],
        ['label' => 'Multi-Language Support',      'file' => 'Multi-Language-Support.md'],
        ['label' => 'Position Job Levels',         'file' => 'Position-Job-Levels.md'],
        ['label' => 'Position Super Admin Approval','file' => 'Position-Super-Admin-Approval.md'],
        ['label' => 'UUID Public Id Approach',     'file' => 'UUID-Public-Id-Approach.md'],
        ['label' => 'Auditing Engine Logic',       'file' => 'Auditing Engine Logic and Schema.md'],
        ['label' => 'Email Service',               'file' => 'Email-Service.md'],
    ];

    // 4. View variables.
    $this->view->setVar('title', $this->locale->t('help_center.title'));
    $this->view->setVar('tenantCount', $tenantCount);
    $this->view->setVar('companyCount', $companyCount);
    $this->view->setVar('adminUserCount', $adminUserCount);
    $this->view->setVar('superAdminCount', $superAdminCount);
    $this->view->setVar('docLinks', $docLinks);
    $this->view->setVar('isSuperAdmin', true); // already guaranteed by the guard

    $this->view->pick('dashboard/help-center');
}
```

Notes:
- The status queries are intentionally **platform-wide** (no `company_id`
  filter) because the Help Center is a Super Admin page, not a
  company-scoped view.
- `tenantUrl()` is the existing helper used throughout `DashboardController`
  for tenant-scoped redirects.
- The `docLinks` array is a simple curated list. If the documentation set
  grows, update this array (or, in a future iteration, scan the `mdSource`
  directory dynamically).

---

## 5. View

Create <ref_file file="/c:/Users/WD11/Documents/Windsurf/hr.advws.com/app/views/dashboard/help-center.volt" />. It extends `layouts/admin.volt` and reuses the Tailwind card grid idiom from `settings/index.volt`.

```volt
{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('help_center.title') }}</h1>
        <p class="text-sm text-slate-500 mt-1 notosan">{{ t('help_center.subtitle') }}</p>
    </div>

    <!-- System Status -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                    <i class="fas fa-layer-group text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium notosan">{{ t('help_center.tenants') }}</p>
                    <p class="text-xl font-bold text-slate-900">{{ tenantCount }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-building text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium notosan">{{ t('help_center.companies') }}</p>
                    <p class="text-xl font-bold text-slate-900">{{ companyCount }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                    <i class="fas fa-user-shield text-purple-600"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium notosan">{{ t('help_center.admin_users') }}</p>
                    <p class="text-xl font-bold text-slate-900">{{ adminUserCount }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                    <i class="fas fa-crown text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium notosan">{{ t('help_center.super_admins') }}</p>
                    <p class="text-xl font-bold text-slate-900">{{ superAdminCount }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Getting Started -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-3 notosan">{{ t('help_center.getting_started') }}</h2>
        <p class="text-sm text-slate-600 notosan">{{ t('help_center.getting_started_body') }}</p>
    </div>

    <!-- Admin Guide (doc links) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('help_center.admin_guide') }}</h2>
        <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
            {% for doc in docLinks %}
            <li>
                <a href="/resource/mdSource/{{ doc.file }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg border border-slate-200 hover:bg-slate-50 transition notosan"
                    target="_blank" rel="noopener">
                    <i class="fas fa-file-lines text-slate-400"></i>
                    <span class="text-sm font-medium text-slate-700">{{ doc.label }}</span>
                    <i class="fas fa-arrow-up-right-from-square ml-auto text-xs text-slate-400"></i>
                </a>
            </li>
            {% endfor %}
        </ul>
    </div>

    <!-- Role & Permissions Reference -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('help_center.roles_title') }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 notosan">{{ t('help_center.role') }}</th>
                        <th class="px-4 py-3 notosan">{{ t('help_center.capabilities') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900 notosan">{{ t('help_center.role_super_admin') }}</td>
                        <td class="px-4 py-3 text-slate-600 notosan">{{ t('help_center.role_super_admin_desc') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900 notosan">{{ t('help_center.role_admin') }}</td>
                        <td class="px-4 py-3 text-slate-600 notosan">{{ t('help_center.role_admin_desc') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900 notosan">{{ t('help_center.role_member') }}</td>
                        <td class="px-4 py-3 text-slate-600 notosan">{{ t('help_center.role_member_desc') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Support -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-3 notosan">{{ t('help_center.support_title') }}</h2>
        <p class="text-sm text-slate-600 notosan">{{ t('help_center.support_body') }}</p>
    </div>
</div>
{% endblock %}
```

---

## 6. Sidebar Navigation Link

Add a Super Admin-only entry to the sidebar in
<ref_file file="/c:/Users/WD11/Documents/Windsurf/hr.advws.com/app/views/layouts/admin.volt" />,
inside the `<nav>` `<ul>` block, after the Settings `<li>` (around line 297).

The link must only render for Super Admins. The layout already has access to
the session via `auth`; expose `isSuperAdmin` to the layout from
`IndexController::setSidebarContext()` (or read the role directly in Volt).

```volt
{% set authUser = session.get('auth') %}
{% set isSuperAdminNav = authUser is defined and authUser['role'] is defined and authUser['role'] == 'Super Admin' %}
{% if isSuperAdminNav %}
<li>
    <a href="/{{ currentTenantSlug ? currentTenantSlug ~ '/' : '' }}{{ navCompanySlug ? navCompanySlug ~ '/' : '' }}dashboard/help-center"
        class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100 notosan">
        <i class="fas fa-circle-question w-5 text-center text-slate-500"></i>
        {{ t('nav.help_center') }}
    </a>
</li>
{% endif %}
```

> **Why read the session in Volt instead of a view variable?** The admin layout
> is shared by every dashboard page. Setting `isSuperAdmin` on every controller
> action is error-prone. Reading `session.get('auth')['role']` directly in the
> layout is consistent with how `isSuperAdmin()` works in the controllers
> (same session key, same role string) and guarantees the link is hidden on
> every page regardless of which controller rendered it.

---

## 7. Translations

Add the following keys to **both** `app/lang/en.php` and `app/lang/th.php`.
Group them together at the end of each file (or in a new
`app/lang/_fragments/help_center.php` fragment included by both catalogs,
following the `_fragments/` convention).

### 7.1 English (`app/lang/en.php`)

```php
// --- Help Center (Super Admin) ---
'help_center.title'                => 'Help Center',
'help_center.subtitle'             => 'Operational hub for Super Admins.',
'help_center.tenants'              => 'Tenants',
'help_center.companies'            => 'Companies',
'help_center.admin_users'          => 'Admin Users',
'help_center.super_admins'         => 'Super Admins',
'help_center.getting_started'      => 'Getting Started',
'help_center.getting_started_body' => 'Use the Admin Guide below to navigate the platform documentation. The System Status cards above show the current platform-wide counts.',
'help_center.admin_guide'          => 'Admin Guide',
'help_center.roles_title'          => 'Roles & Permissions',
'help_center.role'                 => 'Role',
'help_center.capabilities'         => 'Capabilities',
'help_center.role_super_admin'     => 'Super Admin',
'help_center.role_super_admin_desc'=> 'Full platform access: manage tenants, companies, admin users, and all settings.',
'help_center.role_admin'           => 'Admin',
'help_center.role_admin_desc'      => 'Standard staff access: manage employees, positions, leave, and overtime within granted companies.',
'help_center.role_member'          => 'Member',
'help_center.role_member_desc'     => 'Limited access: granted only via tenant/company maps; no global platform privileges.',
'help_center.support_title'        => 'Support',
'help_center.support_body'         => 'For platform-level support, contact the platform operator. For company-level issues, contact your company administrator.',
'nav.help_center'                  => 'Help Center',
'flash.super_admin_only_help_center' => 'Only Super Admins can access the Help Center.',
```

### 7.2 Thai (`app/lang/th.php`)

```php
// --- Help Center (Super Admin) ---
'help_center.title'                => 'ศูนย์ช่วยเหลือ',
'help_center.subtitle'             => 'ศูนย์กลางการดำเนินงานสำหรับผู้ดูแลระบบสูงสุด',
'help_center.tenants'              => 'ผู้เช่า',
'help_center.companies'            => 'บริษัท',
'help_center.admin_users'          => 'ผู้ใช้ผู้ดูแล',
'help_center.super_admins'         => 'ผู้ดูแลระบบสูงสุด',
'help_center.getting_started'      => 'เริ่มต้นใช้งาน',
'help_center.getting_started_body' => 'ใช้คู่มือผู้ดูแลด้านล่างเพื่อดูเอกสารของระบบ การ์ดสถานะระบบด้านบนแสดงจำนวนทั่วทั้งแพลตฟอร์ม',
'help_center.admin_guide'          => 'คู่มือผู้ดูแล',
'help_center.roles_title'          => 'บทบาทและสิทธิ์',
'help_center.role'                 => 'บทบาท',
'help_center.capabilities'         => 'ความสามารถ',
'help_center.role_super_admin'     => 'ผู้ดูแลระบบสูงสุด',
'help_center.role_super_admin_desc'=> 'สิทธิ์เต็มระบบ: จัดการผู้เช่า บริษัท ผู้ใช้ผู้ดูแล และการตั้งค่าทั้งหมด',
'help_center.role_admin'           => 'ผู้ดูแล',
'help_center.role_admin_desc'      => 'สิทธิ์เจ้าหน้าที่มาตรฐาน: จัดการพนักงาน ตำแหน่ง การลา และการทำงานล่วงเวลาในบริษัทที่ได้รับสิทธิ์',
'help_center.role_member'          => 'สมาชิก',
'help_center.role_member_desc'     => 'สิทธิ์จำกัด: ได้รับสิทธิ์เฉพาะผ่านการแมปผู้เช่า/บริษัท ไม่มีสิทธิ์ระดับแพลตฟอร์ม',
'help_center.support_title'        => 'การสนับสนุน',
'help_center.support_body'         => 'สำหรับการสนับสนุนระดับแพลตฟอร์ม กรุณาติดต่อผู้ดำเนินการแพลตฟอร์ม สำหรับปัญหาระดับบริษัท กรุณาติดต่อผู้ดูแลบริษัทของคุณ',
'nav.help_center'                  => 'ศูนย์ช่วยเหลือ',
'flash.super_admin_only_help_center' => 'ผู้ดูแลระบบสูงสุดเท่านั้นที่เข้าถึงศูนย์ช่วยเหลือได้',
```

> The `flash.super_admin_only_help_center` key follows the existing
> `flash.super_admin_only_*` naming convention used for the company and admin
> user permission flashes (see
> <ref_file file="/c:/Users/WD11/Documents/Windsurf/hr.advws.com/app/lang/_fragments/controllers_flash.php" />).

---

## 8. Rollout Checklist

Ordered so each step is independently testable.

1. **Routes**
   - [ ] Add `GET /dashboard/help-center` → `helpCenter` to `$tenantGroup` in
         `app/config/router.php`.

2. **Controller**
   - [ ] Add `helpCenterAction()` to `DashboardController.php`.
   - [ ] Reuse the existing `isSuperAdmin()` helper (line 1999) — do not
         duplicate the role check logic.
   - [ ] Add the `flash.super_admin_only_help_center` guard + redirect.

3. **View**
   - [ ] Create `app/views/dashboard/help-center.volt` extending
         `layouts/admin.volt`.
   - [ ] Render System Status, Getting Started, Admin Guide, Roles table, and
         Support sections.

4. **Sidebar**
   - [ ] Add the Super Admin-only Help Center link in `layouts/admin.volt`,
         gated by `session.get('auth')['role'] == 'Super Admin'`.

5. **Translations**
   - [ ] Add all `help_center.*`, `nav.help_center`, and
         `flash.super_admin_only_help_center` keys to `en.php` and `th.php`.
   - [ ] (Optional) Extract into `app/lang/_fragments/help_center.php` and
         include from both catalogs.

6. **Verification**
   - [ ] Log in as a Super Admin → sidebar shows the Help Center link; page
         loads with correct counts and doc links.
   - [ ] Log in as an Admin → sidebar link is hidden; hitting
         `/dashboard/help-center` directly redirects to the dashboard with the
         `flash.super_admin_only_help_center` error.
   - [ ] Log in as a Member → same as Admin (hidden + redirect).
   - [ ] Switch the UI language to Thai → all Help Center strings render in
         Thai.

---

## 9. Future Extensions (Out of Scope for v1)

- **Dynamic doc discovery:** scan `resource/mdSource/` at runtime instead of
  hardcoding `$docLinks`, so new docs appear automatically.
- **Embedded markdown rendering:** render the selected `mdSource` file inline
  (parsed markdown) instead of linking to the raw file. Requires a PHP markdown
  parser dependency and a route like `/dashboard/help-center/doc/{file}`.
- **Searchable FAQ:** a `help_articles` table (CRUD managed by Super Admins)
  with full-text search. This would introduce a new table and migration,
  departing from the zero-migration v1 design.
- **Audit log viewer:** surface recent `activity_logs` entries for Super Admin
  review (see `Auditing Engine Logic and Schema.md`). A future iteration could
  embed a recent-logs widget directly on the Help Center page.
- **Platform alert banner:** a Super Admin-controlled banner shown across all
  dashboard pages (stored in a `platform_settings` key/value table).
