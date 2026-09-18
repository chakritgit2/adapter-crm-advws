# Routing and Multi-Tenant Architecture

This document details the routing system, tenant-aware route groups, and the multi-tenant resolution flow that forms the core architectural pattern of the HR system.

---

## Router Configuration

The router is configured in `app/config/router.php` and operates on the `Phalcon\Mvc\Router` instance obtained from the DI container.

### Router Setup

```php
$router = $di->getRouter();
$router->removeExtraSlashes(true);
$router->setDefaults([
    'controller' => 'index',
    'action' => 'index',
]);
```

- **`removeExtraSlashes(true)`** — Normalizes URLs by stripping redundant slashes
- **Default route** — Falls back to `IndexController::indexAction`

---

## Route Categories

### 1. Public Routes (No Tenant Required)

These routes are accessible without a tenant slug prefix:

| Method | Path | Controller | Action |
|--------|------|-----------|--------|
| GET | `/loginhrm` | login | index |
| POST | `/loginhrm/authenticate` | login | authenticate |
| GET | `/set-password/{token}` | login | setPassword |
| POST | `/set-password/{token}/save` | login | setPasswordSave |
| GET | `/loginhrm/logout` | login | logout |

The login path (`/loginhrm`) is configurable via `$config['loginPath']`.

### 2. Global Dashboard Routes (No Tenant Slug)

| Method | Path | Controller | Action |
|--------|------|-----------|--------|
| GET | `/dashboard` | dashboard | index |
| GET | `/dashboard/companies` | index | companies |
| GET | `/dashboard/companies/create` | index | companiesCreate |
| GET | `/dashboard/companies/edit/{id}` | index | companiesEdit |
| POST | `/dashboard/companies/store` | index | companiesStore |
| POST | `/dashboard/companies/update/{id}` | index | companiesUpdate |
| POST | `/dashboard/companies/delete/{id}` | index | companiesDelete |
| GET | `/dashboard/profile` | index | profile |
| POST | `/dashboard/profile/save` | index | profileSave |

### 3. Legacy Redirect Routes

```php
$router->addGet('/dashboard{params:.*}', ['controller' => 'index', 'action' => 'legacyRedirect']);
$router->addGet('/api{params:.*}', ['controller' => 'index', 'action' => 'legacyRedirect']);
$router->addGet('/reporting{params:.*}', ['controller' => 'index', 'action' => 'legacyRedirect']);
```

These catch-all routes redirect legacy URLs (from a prior advertising platform) to the new tenant-aware equivalents via `LegacyRedirectController`.

### 4. Tenant-Aware Routes (Tenant Slug Prefix)

All tenant-scoped routes are grouped under a `RouterGroup` with a dynamic prefix:

```php
$tenantSlugPattern = '([a-zA-Z0-9\-]+)';
$tenantGroup = new RouterGroup(['controller' => 'dashboard']);
$tenantGroup->setPrefix('/{tenant_slug:' . $tenantSlugPattern . '}');
```

Two route groups carry the tenant prefix:

- **Company-scoped group** — `/{tenant_slug}/{company_slug}` prefix, default controller `dashboard`. Mounted first.
- **Tenant-level group** — `/{tenant_slug}` prefix, default controller `tenant`. Mounted after, so its specific routes win over the generic `/{tenant_slug}/{company_slug}` pattern.

#### Tenant-level routes

- `/{tenant_slug}` — `dashboardRedirect` (redirects to the user's default company)
- `POST /{tenant_slug}/companies/set-default` — `setDefaultCompany`
- `/{tenant_slug}/dashboard{params:.*}`, `/api{params:.*}`, `/reporting{params:.*}` — catch-alls that redirect to the same path under the user's default company

#### Company-scoped route domains (`/{tenant_slug}/{company_slug}`)

**Dashboard:**
- `/{t}/{c}` and `/{t}/{c}/dashboard` — companiesDashboard
- `/{t}/{c}/dashboard/filter-employees`, `filter-employees-by-age` — roster filter JSON

**Leave Management:**
- `/{t}/{c}/dashboard/settings/leave-policies` — leavePolicies (CRUD, year-end mode, allowance rules)

**Overtime Management:**
- `/{t}/{c}/dashboard/settings/overtime-policies` — policies (CRUD)

**Settings (Admin Users, Job Levels, Custom Attributes, Milestone Event Types, Company Holidays):**
- Full CRUD routes under `/{t}/{c}/dashboard/settings/...`

**Language & Translation:**
- `/{t}/{c}/dashboard/settings/languages` — languages (list, create, store, delete)
- `POST /{t}/{c}/dashboard/translations/store`, `delete/{id}` — JSON endpoints used by settings forms

**Reporting:**
- `/{t}/{c}/reporting/campaign-performance`, `active-alerts`, `test-results`, `broken-urls`, `budget-pacing`, `zero-conversion-waste`, `ab-test-losers`, `health-alerts`
- `/{t}/{c}/dashboard/reports`, `/workforce`, `/leave`, `/overtime`, `/turnover` — hr-reports

### UUID Route Parameters

Routes that reference tenant-scoped entities use UUID patterns instead of integer IDs:

```php
$tenantGroup->addGet('/dashboard/settings/leave-policies/edit/{id:[0-9a-fA-F-]{36}}', [
    'action' => 'leavePoliciesEdit'
]);
```

This enforces that only UUID-format strings are accepted for public-facing entity references, aligning with the UUID Public ID approach.

---

## TenantBaseController — Multi-Tenant Guard

`TenantBaseController` is the base class for all tenant-aware controllers. It implements the `beforeExecuteRoute` hook to enforce authentication and tenant resolution before any action executes.

### Authentication Check

```php
$user = $this->session->get('auth');
if (!$user) {
    $this->response->redirect('/')->send();
    return false;
}
```

If no authenticated user session exists, the request is redirected to the root (which triggers the login flow).

### Tenant Resolution Flow

1. **Extract tenant slug** from the dispatcher's `tenant_slug` parameter
2. **If no slug** — return 404 "Tenant not specified"
3. **If controller is `dashboard` and action is `index`** — this is the companies dashboard page, so load all companies for the user (no specific tenant required)
4. **Otherwise** — resolve the tenant by slug via `TenantResolver`:
   - If company not found or suspended → 404
   - If user lacks access → 403
5. **Set tenant context** on the controller and view:
   - `currentTenantId`, `currentTenantSlug`, `currentTenant` (company array)
   - `currentPathSuffix` (URL path after tenant slug, for navigation highlighting)
   - `userCompanies` (all companies the user has access to, for tenant switcher)
   - `tenantLanguages` (active languages for this tenant)

### Helper Methods

- **`redirectToTenant(string $path)`** — Redirects to a path within the current tenant context
- **`tenantUrl(string $path)`** — Generates a tenant-prefixed URL string

---

## TenantResolver Helper

`TenantResolver` is a plain PHP class (not a Phalcon model) that performs database lookups for tenant resolution:

### Methods

| Method | Purpose |
|--------|---------|
| `resolveBySlug(string $slug): ?array` | Finds a company by its slug, returns `id, name, slug, status` |
| `userHasAccess(int $adminUserId, int $companyId): bool` | Checks `company_user_map` pivot table for access |
| `findCompaniesForUser(int $adminUserId, bool $activeOnly = true): array` | Lists all companies a user can access |
| `findDefaultCompanyForUser(int $adminUserId): ?array` | Returns the first active company for a user |

### Access Control Model

The multi-tenant access is managed through a pivot table:

```
admin_users ←── company_user_map ──→ companies
```

Each row in `company_user_map` grants an `admin_user` access to a specific `company`. The `TenantResolver::userHasAccess()` method queries this table to enforce isolation.

---

## Login Flow and Tenant Selection

The `LoginController::authenticateAction()` handles the login flow:

1. Validate email and password against `admin_users` table
2. Set session `auth` data (`id`, `email`, `name`, `role`)
3. Resolve companies for the user via `TenantResolver::findCompaniesForUser()`
4. If user has access to multiple companies → redirect to `/dashboard`
5. If user has access to one company → redirect to `/dashboard/companies`
6. Update `last_login_at` timestamp

The session `auth` array structure:
```php
[
    'id' => $user['id'],
    'email' => $user['email'],
    'name' => $user['name'],
    'role' => $user['role'],
]
```

This session data is checked by `TenantBaseController::beforeExecuteRoute()` on every subsequent request.
