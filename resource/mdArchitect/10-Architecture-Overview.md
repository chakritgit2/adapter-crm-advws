# Architecture Overview

This document provides a high-level architectural summary of the HR system, including the request lifecycle, layer separation, cross-cutting concerns, key architectural decisions, and a file-to-domain reference map.

---

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         Client (Browser)                         │
│              Tailwind CSS 4 + Font Awesome + Vanilla JS          │
└──────────────────────────┬──────────────────────────────────────┘
                           │ HTTP
┌──────────────────────────▼──────────────────────────────────────┐
│                    Apache (mod_rewrite)                          │
│              public/.htaccess — URL rewriting + caching          │
└──────────────────────────┬──────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│                   public/index.php (Bootstrap)                   │
│  FactoryDefault DI → services.php → router.php → loader.php      │
│              Phalcon\Mvc\Application->handle()                   │
└──────────────────────────┬──────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│                     Router (router.php)                          │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────────────┐  │
│  │ Public Routes│  │ Global Routes│  │ Tenant-Aware Routes    │  │
│  │ (/loginhrm)  │  │ (/dashboard) │  │ (/{slug}/dashboard/..) │  │
│  └─────────────┘  └──────────────┘  └────────────────────────┘  │
└──────────────────────────┬──────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│                    Controller Layer                              │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  TenantBaseController (beforeExecuteRoute)                │   │
│  │  ├── Auth check (session)                                 │   │
│  │  ├── Tenant resolution (TenantResolver)                   │   │
│  │  └── View variable setup (tenant, companies, languages)   │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ├── DashboardController    ├── TenantController                 │
│  ├── OvertimeController     ├── TranslationController            │
│  ├── LoginController        ├── IndexController                  │
│  ├── HrReportsController    ├── ReportingController              │
│  └── ActionController       └── LegacyRedirectController         │
└──────────┬───────────────────────────────┬──────────────────────┘
           │                               │
┌──────────▼──────────┐     ┌─────────────▼──────────────────────┐
│   Service Layer      │     │         Model Layer (ORM)           │
│  ├── AuthService     │     │  ├── TranslateUuidTrait             │
│  ├── EmailService    │     │  ├── Employees, LeaveRequests       │
│  ├── TranslationSvc  │     │  ├── OvertimeRequests, Companies    │
│  ├── OrgChartService │     │  ├── PositionAssignments            │
│  ├── ErrorService    │     │  ├── Translations, TenantLanguages  │
│  ├── CurlService     │     │  └── 40 models total                │
│  └── ApiCallerSvc    │     └─────────────┬──────────────────────┘
└──────────┬──────────┘                    │
           │                               │
┌──────────▼───────────────────────────────▼──────────────────────┐
│              Database (MariaDB / MySQL via PDO)                  │
│                    host: mariadb.cdi-advws                        │
│                    database: hr (UTF-8)                          │
└──────────────────────────────────────────────────────────────────┘
```

---

## Request Lifecycle

1. **Browser** sends HTTP request
2. **Apache `.htaccess`** — Rewrites non-static URLs to `index.php?_url=/path`
3. **`public/index.php`** — Creates `FactoryDefault` DI, includes `services.php`, `router.php`, `loader.php`
4. **Router** — Matches URL against defined routes, extracts `tenant_slug` parameter
5. **Dispatcher** — Instantiates the matched controller
6. **`TenantBaseController::beforeExecuteRoute()`** (for tenant-aware routes):
   - Checks session for authenticated user
   - Resolves tenant slug to company via `TenantResolver`
   - Validates user access to the company
   - Sets view variables (tenant, companies, languages)
7. **Controller Action** — Executes business logic:
   - Calls services for complex operations
   - Calls models for data access
   - Uses `TranslateUuidTrait` to convert UUIDs to internal IDs
   - Sets view variables or returns JSON response
8. **View** (for HTML responses) — Volt engine renders the template:
   - Extends a layout (`admin.volt`, `main.volt`, etc.)
   - Uses custom Volt functions for formatting
   - Includes partials (nav, footer, flash messages)
9. **Response** — HTML content or JSON returned to browser

---

## Layer Separation

### Controller Layer
- **Responsibility:** Request handling, input filtering, orchestration
- **Pattern:** Thin controllers that delegate to services and models
- **Exception:** `DashboardController` (97KB) is a "fat controller" that contains significant business logic inline — a candidate for refactoring into services

### Service Layer
- **Responsibility:** Business logic, external API integration, cross-cutting operations
- **Pattern:** Services receive DI or DB connection in constructor, return arrays or error arrays
- **Key services:** Auth, Email, Translation, OrgChart, Error, Curl

### Model Layer
- **Responsibility:** Data representation, relationships, UUID generation
- **Pattern:** Phalcon MVC Models with `TranslateUuidTrait`, typed properties, `setSource()` table mapping
- **Note:** Many complex queries bypass ORM and use raw SQL for translation joins and aggregations

### View Layer
- **Responsibility:** Presentation logic only
- **Pattern:** Volt templates extending layouts, using custom compiler functions for formatting
- **Styling:** Tailwind CSS 4 with pre-main fallback styles for dynamic classes

---

## Cross-Cutting Concerns

### Multi-Tenancy
- **Enforcement point:** `TenantBaseController::beforeExecuteRoute()`
- **Data isolation:** `company_id` column on all tenant-scoped tables
- **Access control:** `company_user_map` pivot table
- **URL pattern:** `/{tenant_slug}/...` prefix on all tenant routes

### Translations
- **System:** Polymorphic translation ledger (`translations` table)
- **Config-driven:** `allowed_targets` whitelist in `config.php`
- **Query pattern:** `COALESCE(translation_value, original_value)` with LEFT JOINs
- **Service:** `TranslationService` for CRUD operations
- **Controller integration:** `savePostedTranslations()` method for form handling

### Security
- **Authentication:** Session-based with `auth` key
- **UUID protection:** Internal IDs never exposed to frontend
- **Encryption:** AES-256-GCM for API credentials
- **Cookie signing:** Signed cookies with configured key
- **See:** [08-Security-Practices.md](08-Security-Practices.md)

### Error Handling
- **Pattern:** Services return error arrays instead of throwing exceptions
- **Checking:** `ErrorService::isError()` to detect error responses
- **Chaining:** Prior errors are preserved in `error_prior` array
- **See:** [07-Service-Layer.md](07-Service-Layer.md)

---

## Key Architectural Decisions

### 1. UUID Public ID Approach
- **Decision:** All public-facing entity references use UUIDv4, never internal integer IDs
- **Implementation:** `TranslateUuidTrait` provides `findByUuid()`, `getIdByUuid()`, and `generateUuid()`
- **Benefit:** Prevents enumeration attacks, hides database size, decouples public API from internal schema
- **Trade-off:** Extra lookup query per request (UUID → integer ID translation)

### 2. Tenant Slug URL Routing
- **Decision:** All tenant-scoped routes are prefixed with `/{tenant_slug}/`
- **Implementation:** `RouterGroup` with dynamic prefix pattern
- **Benefit:** Clean URLs, explicit tenant context, easy tenant switching
- **Trade-off:** All tenant routes require slug resolution on every request

### 3. Polymorphic Translation System
- **Decision:** Single `translations` table with `target_table` + `target_column` + `target_id` polymorphic references
- **Implementation:** `TranslationService` with config-driven `allowed_targets` whitelist
- **Benefit:** No schema changes needed to add translatable fields, tenant-scoped languages
- **Trade-off:** Multiple LEFT JOINs for translated queries, potential performance impact on large datasets

### 4. Email Queue with Retry
- **Decision:** Emails are queued in `email_outbox` table and processed by a worker
- **Implementation:** `EmailService::sendInternal()` with `FOR UPDATE SKIP LOCKED` pattern
- **Benefit:** Non-blocking email sends, automatic retry with exponential backoff
- **Trade-off:** Requires CLI worker to process the queue

### 5. Raw SQL Over ORM for Complex Queries
- **Decision:** Use `$this->db->fetchAll()` with raw SQL for queries involving translation joins or complex aggregations
- **Implementation:** Parameterized queries with `Phalcon\Db\Enum::FETCH_ASSOC`
- **Benefit:** Full SQL control, optimized JOINs, COALESCE for translation fallbacks
- **Trade-off:** Bypasses ORM features (events, behaviors), SQL is embedded in PHP code

### 6. Volt `always => true` (Development Mode)
- **Decision:** Volt templates recompile on every request
- **Implementation:** Set in `services.php` view service configuration
- **Benefit:** Instant template changes during development, no cache clearing needed
- **Trade-off:** Performance impact in production — should be set to `false` for production deployments

---

## File-to-Domain Reference Map

| Domain | Key Files | Documentation |
|--------|-----------|---------------|
| **Framework & Stack** | `composer.json`, `package.json`, `Dockerfile` | [01-Framework-and-Stack-Overview.md](01-Framework-and-Stack-Overview.md) |
| **Config & DI** | `app/config/config.php`, `services.php`, `loader.php`, `console.php` | [02-Config-and-DI-Services.md](02-Config-and-DI-Services.md) |
| **Routing & Multi-Tenant** | `app/config/router.php`, `TenantBaseController.php`, `TenantResolver.php` | [03-Routing-and-Multi-Tenant.md](03-Routing-and-Multi-Tenant.md) |
| **Volt Templates** | `app/views/`, `services.php` (Volt config section) | [04-Volt-Templates-and-Views.md](04-Volt-Templates-and-Views.md) |
| **Models & ORM** | `app/models/`, `app/traits/TranslateUuidTrait.php` | [05-Models-and-ORM-Practices.md](05-Models-and-ORM-Practices.md) |
| **Controllers** | `app/controllers/` (11 controllers) | [06-Controller-Patterns.md](06-Controller-Patterns.md) |
| **Service Layer** | `app/services/` (8 services) | [07-Service-Layer.md](07-Service-Layer.md) |
| **Security** | `CredentialEncryption.php`, `LoginController.php`, `services.php` (security, session, cookies) | [08-Security-Practices.md](08-Security-Practices.md) |
| **Deployment** | `Dockerfile`, `start.sh`, `public/.htaccess`, `public/index.php` | [09-Deployment-and-Infrastructure.md](09-Deployment-and-Infrastructure.md) |
| **Helpers** | `app/helpers/TenantResolver.php`, `AuditQueryBuilder.php`, `CredentialEncryption.php` | Referenced in relevant domain docs |
| **CLI Tasks** | `app/tasks/ApiWorkerTask.php`, `app/tasks/EmailTask.php`, `app/console.php` | [09-Deployment-and-Infrastructure.md](09-Deployment-and-Infrastructure.md) |
| **Migrations** | `app/migrations/` (SQL + PHP runners) | [05-Models-and-ORM-Practices.md](05-Models-and-ORM-Practices.md) |
| **Email Templates** | `resource/email_templates/` (7 files) | [07-Service-Layer.md](07-Service-Layer.md) |

---

## Domain Documentation Sources

The project includes existing domain documentation in two locations:

- **`resource/mdSource/`** — 22 files covering database schema, data domains, employee management, leave management, overtime, milestones, org chart, email service, UUID approach, and more
- **`resource/mdSourceWorkflow/`** — 11 files covering import pipelines, translation system, and org hierarchy workflows

These documents serve as the source material for business logic and data model decisions. The `resource/mdArchitect/` directory (this documentation set) complements them by focusing on the technical architecture, framework patterns, and code-level practices.
