# Controller Patterns

This document details the controller hierarchy, action naming conventions, JSON API response patterns, flash messaging, role-based access control, and cross-cutting controller behaviors.

---

## Controller Hierarchy

```
Phalcon\Mvc\Controller
├── LoginController          (extends Controller directly — public, no tenant)
├── IndexController          (extends Controller — global dashboard, companies, profile)
├── LegacyRedirectController (extends Controller — legacy URL redirects)
├── ActionController         (extends Controller — action endpoints)
├── ReportingController      (extends Controller — reporting views)
└── TenantBaseController     (extends Controller — tenant-aware base)
    ├── TenantController     (tenant-level redirects, default-company setter)
    ├── DashboardController  (95KB — largest controller, handles most HR features)
    ├── HrReportsController  (workforce/leave/overtime/turnover reports)
    ├── OvertimeController   (overtime policies settings)
    └── TranslationController (language management, translation store/delete)
```

### Two-Tier Pattern

- **Direct `Controller` extension** — For public/global routes that don't require tenant context (login, companies list, profile, legacy redirects)
- **`TenantBaseController` extension** — For all tenant-scoped routes. Inherits authentication, tenant resolution, and view variable setup from `beforeExecuteRoute()`

---

## Controller Inventory

| Controller | Size | Extends | Purpose |
|-----------|------|---------|---------|
| `DashboardController` | 97,664 bytes | TenantBaseController | Company dashboard, settings CRUD (job levels, custom attributes, admin users, leave policies, milestone event types, company holidays), help center |
| `IndexController` | 28,955 bytes | Controller | Global companies CRUD, profile, landing page, language switcher |
| `HrReportsController` | 25,625 bytes | TenantBaseController | Workforce/leave/overtime/turnover reports |
| `ReportingController` | 12,076 bytes | Controller | Reporting views (campaign performance, alerts) |
| `TenantBaseController` | 10,467 bytes | Controller | Base class for tenant-aware controllers |
| `LoginController` | 9,069 bytes | Controller | Authentication, password setup, logout |
| `OvertimeController` | 7,561 bytes | TenantBaseController | Overtime policies settings |
| `TenantController` | 5,797 bytes | TenantBaseController | Tenant-level redirects, default-company setter |
| `TranslationController` | 5,048 bytes | TenantBaseController | Language management, translation store/delete |
| `ActionController` | 3,052 bytes | Controller | Action endpoints |
| `LegacyRedirectController` | 1,337 bytes | Controller | Legacy URL redirect handler |

---

## Action Naming Conventions

Controllers follow a RESTful-inspired naming convention:

| Action Suffix | HTTP Method | Purpose |
|--------------|-------------|---------|
| `indexAction` | GET | List view / landing page |
| `createAction` | GET | Show create form |
| `storeAction` | POST | Persist new record |
| `editAction` | GET | Show edit form (with ID/UUID param) |
| `updateAction` | POST | Persist update (with ID/UUID param) |
| `deleteAction` | POST | Delete record (with ID/UUID param) |
| `approveAction` | POST | Approve a request (with UUID param) |
| `rejectAction` | POST | Reject a request (with UUID param) |

### Examples

```php
// List view
public function employeeListAction() { ... }

// Create form
public function employeeMilestoneAction() { ... }

// Store new record
public function employeeMilestoneStoreAction() { ... }

// Edit form (UUID param)
public function employeeEditAction() {
    $uuid = $this->dispatcher->getParam('id');
    $employee = Employees::findByUuid($uuid, $this->currentTenantId);
    // ...
}

// Approve (UUID param)
public function leaveRequestsApproveAction() {
    $uuid = $this->dispatcher->getParam('id');
    $id = LeaveRequests::getIdByUuid($uuid, $this->currentTenantId);
    // ...
}
```

---

## JSON API Response Pattern

For API endpoints (routes under `/api/...`), controllers disable the view and return JSON directly:

```php
public function treeAction(): Response
{
    $this->view->disable();

    $auth = $this->session->get('auth');
    $activeLanguage = $this->session->get('active_language');

    $service = new OrgChartService($this->db);
    $positions = $service->getPositions($this->currentTenantId, $activeLanguage, (int)$auth['id']);
    $canViewSalary = in_array($auth['role'] ?? '', ['Super Admin', 'Admin'], true);
    $nodes = $service->buildChartNodes($positions, $canViewSalary);

    $this->response->setJsonContent([
        'nodes'       => $nodes,
        'connections' => $service->getMatrixConnections($this->currentTenantId),
    ]);
    return $this->response->send();
}
```

### Pattern Steps

1. **`$this->view->disable()`** — Prevent Volt template rendering
2. **Get auth data** from session for role checks
3. **Instantiate service** with DB connection
4. **Execute business logic** via service methods
5. **Set JSON content** on the response object
6. **Return `$this->response->send()`** — Send response immediately

### Error Responses

```php
$this->response->setStatusCode(403);
$this->response->setJsonContent(['error' => 'Only admins can create positions.']);
return $this->response->send();
```

```php
$this->response->setStatusCode(400);
$this->response->setJsonContent(['error' => 'Parent position not found.']);
return $this->response->send();
```

---

## Flash Messaging

Controllers use `flashSession` for user feedback after form submissions:

```php
// Error
$this->flashSession->error('Invalid credentials');

// Success
$this->flashSession->success('Your password has been set. Welcome!');

// Redirect after flash
$this->response->redirect($this->config['loginPath']);
```

Flash messages are rendered by the `partials/flash.volt` partial in the layout, using Bootstrap CSS classes (`alert alert-danger`, `alert alert-success`, etc.).

---

## Role-Based Access Control

### Role Values

The system defines three roles in `admin_users`:
- **`Super Admin`** — Full access including salary visibility and position approvals
- **`Admin`** — Standard administrative access
- **`Member`** — Limited access

### Role Checks in Controllers

```php
// Check if user can view salary
$canViewSalary = in_array($auth['role'] ?? '', ['Super Admin', 'Admin'], true);

// Check if user can create positions
if (!in_array($auth['role'] ?? '', ['Super Admin', 'Admin'], true)) {
    $this->response->setStatusCode(403);
    $this->response->setJsonContent(['error' => 'Only admins can create positions.']);
    return $this->response->send();
}
```

Role checks are performed inline within actions rather than via middleware or ACL. The ACL components (`Phalcon\Acl\Adapter\Memory`) are imported in `services.php` but not actively used.

---

## Tenant URL Helpers

`TenantBaseController` provides two helper methods for generating tenant-aware URLs:

### `redirectToTenant(string $path): Response`

```php
protected function redirectToTenant(string $path): Response
{
    $slug = $this->currentTenantSlug ?? 'unknown';
    $path = ltrim($path, '/');
    return $this->response->redirect("/{$slug}/{$path}");
}
```

Returns a redirect response to a path within the current tenant context.

### `tenantUrl(string $path): string`

```php
protected function tenantUrl(string $path): string
{
    $slug = $this->currentTenantSlug ?? $this->session->get('auth')['default_company_slug'] ?? 'unknown';
    $path = ltrim($path, '/');
    return "/{$slug}/{$path}";
}
```

Generates a tenant-prefixed URL string (used in flash redirect patterns):

```php
$this->flashSession->error('Language code and name are required.');
$this->response->redirect($this->tenantUrl('/dashboard/settings/languages/create'));
return;
```

---

## Translation Saving Pattern

Controllers that handle forms with translatable fields use a shared pattern to save translations:

```php
protected function savePostedTranslations(string $targetTable, int $targetId): void
{
    $translations = $this->request->getPost('translations');
    if (!is_array($translations) || empty($translations)) {
        return;
    }

    $translationService = new TranslationService($this->db);
    foreach ($translations as $langCode => $columns) {
        if (!is_array($columns)) continue;
        foreach ($columns as $column => $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $translationService->upsertTranslation(
                    $this->currentTenantId,
                    $langCode,
                    $targetTable,
                    $column,
                    $targetId,
                    $value
                );
            }
        }
    }
}
```

This method is defined in `OvertimeController` and called after saving the primary record. The `translations` POST field is a nested array: `[language_code => [column => value]]`.

---

## Request Data Access

Controllers access request data via Phalcon's request service:

```php
// POST data with type filtering
$email = $this->request->getPost('email', 'string');
$password = $this->request->getPost('password', 'string', '');
$uuid = $this->request->getPost('employee_public_id', 'string');

// JSON body (for API endpoints)
$data = json_decode($this->request->getRawBody(), true);

// Query parameters
$returnUrl = $this->request->getQuery('returnUrl', 'string');

// Route parameters
$token = $this->dispatcher->getParam('token', 'string', '');
$uuid = $this->dispatcher->getParam('id');
```

### Type Filtering

Phalcon's request service supports type filtering as a second parameter: `'string'`, `'int'`, `'email'`, etc. This provides basic input sanitization at the controller level.

---

## View Picking

Controllers can override the default view resolution (which follows `controller/action` convention) using `$this->view->pick()`:

```php
// Render a view from a different directory
$this->view->pick('dashboard/settings/languages');

// Render a specific login view
$this->view->pick('login/set-password');
```

This is used when an action in one controller needs to render a view from another controller's view directory, or when the view path doesn't match the conventional naming.
