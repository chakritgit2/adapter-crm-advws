# Models and ORM Practices

This document details the Phalcon MVC model patterns, the UUID trait system, relationship definitions, multi-tenant isolation, and the full model inventory.

---

## Model Architecture

All models extend `Phalcon\Mvc\Model` and use `declare(strict_types=1)`. Models follow a consistent pattern:

```php
class Employees extends Model
{
    use TranslateUuidTrait;

    public const TABLE_NAME = 'employees';

    public ?int $id = null;
    public ?string $public_id = null;
    public int $company_id;
    public string $first_name;
    // ...

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo('company_id', Companies::class, 'id', ['alias' => 'Company']);
        $this->hasMany('id', PositionAssignments::class, 'employee_id', ['alias' => 'Assignments']);
    }
}
```

### Key Patterns

- **`TABLE_NAME` constant** — Each model defines its table name as a class constant, passed to `setSource()` in `initialize()`
- **Typed properties** — All model properties use PHP 8.x typed properties with nullable types where appropriate
- **`initialize()` method** — Phalcon's model initialization hook for setting source table and defining relationships
- **Status constants** — Models with status fields define them as class constants (e.g., `STATUS_PENDING`, `STATUS_ACTIVE`, `STATUS_SUSPENDED`)

---

## TranslateUuidTrait — UUID System

The `TranslateUuidTrait` is the cornerstone of the public ID security layer. It provides automatic UUID generation and UUID-to-internal-ID translation for all models that use it.

### Trait Methods

#### `beforeValidationOnCreate()` — Auto UUID Generation
```php
public function beforeValidationOnCreate(): void
{
    if (empty($this->public_id)) {
        $this->public_id = self::generateUuid();
    }
}
```
Automatically generates a UUIDv4 before a new record is created if `public_id` is not already set.

#### `findByUuid(string $uuid, ?int $companyId = null)` — UUID Lookup
```php
public static function findByUuid(string $uuid, ?int $companyId = null): ?\Phalcon\Mvc\ModelInterface
{
    $conditions = 'public_id = :uuid:';
    $bind = ['uuid' => $uuid];

    if ($companyId !== null) {
        $conditions .= ' AND company_id = :company_id:';
        $bind['company_id'] = $companyId;
    }

    $record = self::findFirst(['conditions' => $conditions, 'bind' => $bind]);
    return $record ?: null;
}
```
Finds a record by its public UUID. When `$companyId` is provided, enforces multi-tenant isolation by requiring the record to belong to the specified tenant.

#### `getIdByUuid(string $uuid, ?int $companyId = null)` — UUID to Internal ID Translation
```php
public static function getIdByUuid(string $uuid, ?int $companyId = null): ?int
{
    $record = self::findByUuid($uuid, $companyId);
    return $record ? (int) $record->id : null;
}
```
The primary translation method: converts a public UUID to the internal integer ID used for database operations. Always includes the tenant check when `$companyId` is provided.

#### `generateUuid()` — UUIDv4 Generation
```php
public static function generateUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
```
Generates a RFC 4122 compliant UUIDv4 using native PHP `random_bytes()`. No external dependencies required.

### UUID Processing Flow

1. **Frontend** sends only UUIDs in requests (route params, form data, JSON payloads)
2. **Controller** receives UUID, calls `Model::getIdByUuid($uuid, $companyId)` to translate
3. **Database operations** use the internal integer ID for all JOINs, inserts, and updates
4. **Response** returns only `public_id` to the frontend, never the internal `id`

### Models Using TranslateUuidTrait

- `Employees`
- `LeaveRequests`
- `LeaveBalances`
- `LeaveTypes`
- `OvertimePolicies`
- `OvertimeRequests`
- `EmployeeMilestones`
- `MilestoneEventTypes`

---

## Model Relationships

### Relationship Types

Phalcon supports four relationship types, of which the codebase uses two:

- **`belongsTo`** — Foreign key → primary key (many-to-one)
- **`hasMany`** — Primary key → foreign key (one-to-many)

### Relationship Pattern

```php
$this->belongsTo(
    'company_id',           // Local field
    Companies::class,       // Target model
    'id',                   // Target field
    ['alias' => 'Company']  // Access via $model->Company
);
```

All relationships use string class references (not table names) and define aliases for convenient access.

### Example: LeaveRequests Relationships

```php
$this->belongsTo('company_id', Companies::class, 'id', ['alias' => 'Company']);
$this->belongsTo('employee_id', Employees::class, 'id', ['alias' => 'Employee']);
$this->belongsTo('leave_type_id', LeaveTypes::class, 'id', ['alias' => 'LeaveType']);
$this->belongsTo('approved_by', AdminUsers::class, 'id', ['alias' => 'Approver']);
```

This defines a leave request as belonging to a company, an employee, a leave type, and an approver (admin user).

---

## Multi-Tenant Isolation

Every tenant-scoped model includes a `company_id` property. This is the primary mechanism for data isolation:

- **Model-level:** `findByUuid()` accepts optional `$companyId` for tenant-scoped lookups
- **Query-level:** Controllers always append `company_id = :company_id` to queries
- **Relationship-level:** `belongsTo('company_id', Companies::class, 'id')` links every record to its tenant

### Companies Model (The Tenant Anchor)

```php
class Companies extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    public ?int $id = null;
    public string $name;
    public string $slug;
    public string $status = self::STATUS_ACTIVE;

    public static function findFirstBySlug(string $slug): ?self
    {
        return self::findFirst([
            'conditions' => 'slug = :slug:',
            'bind' => ['slug' => $slug],
        ]) ?: null;
    }
}
```

The `Companies` model does **not** use `TranslateUuidTrait` — it uses integer IDs and slug-based lookups, as it is the tenant anchor itself.

---

## Model Inventory

### HR Domain Models (with TranslateUuidTrait)

| Model | Table | Key Relationships |
|-------|-------|-------------------|
| `Employees` | `employees` | belongsTo Company, hasMany Assignments |
| `LeaveRequests` | `leave_requests` | belongsTo Company, Employee, LeaveType, Approver |
| `LeaveBalances` | `leave_balances` | — |
| `LeaveTypes` | `leave_types` | — |
| `OvertimePolicies` | `overtime_policies` | — |
| `OvertimeRequests` | `overtime_requests` | — |
| `EmployeeMilestones` | `employee_milestones` | — |
| `MilestoneEventTypes` | `milestone_event_types` | — |

### HR Domain Models (without TranslateUuidTrait)

| Model | Table | Purpose |
|-------|-------|---------|
| `Companies` | `companies` | Tenant anchor (slug-based lookup) |
| `AdminUsers` | `admin_users` | System administrators |
| `CompanyUserMap` | `company_user_map` | User-tenant access pivot |
| `Users` | `users` | Additional user credentials |
| `ClientUsers` | `client_users` | Client portal users |
| `Clients` | `clients` | Client entities |
| `PositionAssignments` | `position_assignments` | Employee-position history ledger |
| `PositionApprovals` | `position_approvals` | Multi-approval workflow signatures |
| `TenantLanguages` | `tenant_languages` | Per-tenant language installations |
| `Translations` | `translations` | Polymorphic translation records |
| `EmailOutbox` | `email_outbox` | Email queue table |
| `ApiTokens` | `api_tokens` | API credential tokens |

### Legacy Ad Domain Models

| Model | Table | Purpose |
|-------|-------|---------|
| `AdAccounts` | `ad_accounts` | Advertising accounts |
| `Campaigns` | `campaigns` | Ad campaigns |
| `AdGroups` | `ad_groups` | Ad groups |
| `Keywords` | `keywords` | Keywords |
| `AdsAssets` | `ads_assets` | Ad creative assets |
| `AdTests` | `ad_tests` | A/B test definitions |
| `AdTestVariations` | `ad_test_variations` | A/B test variations |
| `CampaignNegativeKeywords` | `campaign_negative_keywords` | Campaign negative keywords |
| `AdGroupNegativeKeywords` | `ad_group_negative_keywords` | Ad group negative keywords |
| `CampaignPerformanceDaily` | `campaign_performance_daily` | Daily campaign metrics |
| `AdGroupPerformanceDaily` | `ad_group_performance_daily` | Daily ad group metrics |
| `KeywordPerformanceDaily` | `keyword_performance_daily` | Daily keyword metrics |
| `AdPerformanceDaily` | `ad_performance_daily` | Daily ad metrics |
| `AuditRules` | `audit_rules` | Audit rule definitions |
| `AccountAuditAlerts` | `account_audit_alerts` | Account-level audit alerts |
| `AdAuditAlerts` | `ad_audit_alerts` | Ad-level audit alerts |
| `AdGroupAuditAlerts` | `ad_group_audit_alerts` | Ad group audit alerts |
| `CampaignAuditAlerts` | `campaign_audit_alerts` | Campaign audit alerts |
| `KeywordAuditAlerts` | `keyword_audit_alerts` | Keyword audit alerts |
| `UrlCrawlLogs` | `url_crawl_logs` | URL health crawl logs |

---

## Model Query Patterns

### Direct DB Queries (Raw SQL)

Many controllers and services bypass the ORM and use direct SQL via `$this->db->fetchAll()` for complex queries, especially those involving translation joins:

```php
$this->db->fetchAll(
    "SELECT p.id, p.parent_position_id, p.job_title, ...
     FROM positions p
     LEFT JOIN position_assignments pa ON pa.position_id = p.id AND pa.end_date IS NULL
     LEFT JOIN employees e ON e.id = pa.employee_id
     LEFT JOIN translations t_jt ON t_jt.company_id = p.company_id AND ...
     WHERE p.company_id = :company_id",
    \Phalcon\Db\Enum::FETCH_ASSOC,
    ['company_id' => $companyId]
);
```

This pattern is used when:
- Multiple translation joins are needed (COALESCE with fallback values)
- Complex aggregations are required
- Performance is critical for large result sets

### ORM Model Queries

For simpler operations, the standard Phalcon ORM is used:

```php
// Find by conditions
LeaveRequests::find([
    'conditions' => 'company_id = :company_id: AND status = :status:',
    'bind' => ['company_id' => $companyId, 'status' => 'pending']
]);

// Find first by UUID with tenant isolation
$employee = Employees::findByUuid($uuid, $companyId);
```

### Parameterized Queries

All queries use parameterized bindings (`:param` or `?`) to prevent SQL injection. This is enforced consistently across both raw SQL and ORM query patterns.
