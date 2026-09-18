# UDA-01: Architecture & Local Schema

## 1. System Objective

The Universal Data Adapter (UDA) is implemented as an integrated capability in this Phalcon MVC HR/CRM repository. It uses the existing web and CLI bootstraps, tenant/company authorization, and CRM database for adapter metadata while opening separately configured connections to external sources. It remains an integrated deployment rather than a separately packaged service.

The intended UDA would isolate external-vendor connections and transformations from the main application. If it is deployed as a standalone service at `adapter-crm.advws.com`, it should have its own configuration, database credentials, migrations, operational logs, and release process. The current application must remain the system of record for users, tenants, companies, and the CRM-side persistence layer.

## 2. Current Repository Baseline

The current web entry point is `public/index.php`. It creates a Phalcon `FactoryDefault` DI container, loads `app/config/services.php`, loads `app/config/router.php`, registers the autoloader, and handles the request through `Phalcon\Mvc\Application`.

The current database service is a single shared `Phalcon\Db\Adapter\Pdo\Mysql` connection configured in `app/config/services.php` from `app/config/config.php`. The adapter registry now lives in the CRM schema through `adapter_connections` and `adapter_endpoints`; external vendor connections are created on demand by `AdapterConnectionService` rather than pooled.

The implementation now includes:

- `app/migrations/1.0.0/uda_adapter.sql` with connection, endpoint, and sync checkpoint tables;
- `AdapterConnections` and `AdapterEndpoints` models;
- `AdapterController` and `AdapterApiController`;
- `AdapterConnectionService` and `TransformerService`;
- `SyncTask` plus adapter Volt views and explicit router entries.

The `app/config/loader.php` class map includes application controllers, models, services, helpers, traits, middleware, and the configured API-controller directory. Adding UDA classes should follow that existing autoloading convention, or use an explicit namespace and loader entry consistently across web and CLI runtimes.

## 3. Existing Tenant/Company Isolation

The current application already has a two-level scope that must not be confused with the proposed UDA schema:

- `tenants` identifies the parent customer boundary (`id`, `name`, `slug`, `status`, plan, and billing email).
- `companies` belongs to a tenant through `companies.tenant_id` and has a tenant-scoped unique `slug`.
- `tenant_user_map` grants tenant-level access to authenticated admin users.
- `company_user_map` grants direct company access.

`TenantBaseController::initialize()` resolves `tenant_slug` and optional `company_slug`, rejects suspended or inaccessible records, and publishes the resolved IDs and slugs to the view. Any UDA record that is owned by a CRM tenant or company must carry an explicit relationship to that boundary; URL slugs alone are not sufficient authorization data.

## 4. Proposed Local UDA Schema

The following schema is the target design, not a schema currently present in the repository.

### A. `database_connections`

Stores metadata and encrypted credentials for an external vendor database.

| Column | Purpose |
|---|---|
| `id` | Internal primary key |
| `name` | Administrator-facing connection label |
| `engine` | Supported driver, such as `mysql`, `mariadb`, `pgsql`, or `mongodb` |
| `host` / `port` | Remote endpoint |
| `db_name` | Remote database or logical database name |
| `username` | Remote account name, if applicable |
| `password_ciphertext` | Encrypted secret; never expose it in views or API responses |
| `status` | Active, disabled, or failed-test state |
| `last_tested_at` | Last connectivity test timestamp |
| `created_at` / `updated_at` | Audit timestamps |

Use a server-side encryption key supplied by deployment configuration. `Phalcon\Encryption\Security` is registered in the current application for password hashing, but the existing `CredentialEncryption` helper and its configured key must be reviewed before reusing them for reversible secret encryption. Password hashing is not reversible credential encryption.

### B. `exposed_endpoints`

Maps an administrator-defined API slot to a connection and query template.

| Column | Purpose |
|---|---|
| `id` | Internal primary key |
| `connection_id` | Foreign key to `database_connections.id` |
| `api_name` | Public route segment; unique within the adapter |
| `query_template` | Parameterized query using named placeholders |
| `api_key_hash` | Prefer a hash rather than storing the bearer key in plaintext |
| `enabled` | Emergency disable switch |
| `created_at` / `updated_at` | Audit timestamps |

If the adapter is tenant-aware, include `tenant_id` and/or `company_id` as foreign keys and enforce them before query execution. Define indexes and delete behavior in a migration rather than relying on the illustrative fields above.

## 5. Implementation Boundary

A future implementation should add migrations and models under the same application conventions, but should preferably live in a separately deployable UDA application if external connections are a hard isolation requirement. Do not add arbitrary external credentials to `app/config/config.php`; the current file contains environment-specific settings and should be migrated to environment variables or a secret manager before this feature is productionized.

The CRM-side tenant/company identifiers in the UDA payload must be resolved from trusted adapter configuration or a signed request context. They must never be accepted blindly from ordinary query-string parameters.

## 6. Acceptance Criteria

- The UDA migration is additive and must be reviewed/applied after the tenant-system migration; it does not alter existing HR/CRM tables.
- External secrets are encrypted at rest and redacted from logs, Volt variables, and error responses.
- Every endpoint is linked to a connection and, where applicable, an explicit tenant/company scope.
- Failed migrations, unavailable remote databases, and disabled endpoints produce actionable audit records without leaking connection details.
- The UDA can be disabled independently without breaking normal login, dashboard, reporting, or tenant routing in the current application.
