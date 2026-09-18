# UDA-03: Dynamic Routing & Extraction (Passive Real-Time Fetch)

## 1. System Objective

The proposed passive adapter endpoint is a synchronous HTTP execution path that resolves an administrator-defined API slot, authenticates the caller, executes a parameterized query against the linked external connection, and returns a normalized response.

`app/controllers/AdapterApiController.php` implements this endpoint. It is a read-only, API-key-authenticated execution path with SQL parameter binding and a static JSON-filter path for MongoDB.

## 2. Current Router Behavior

The active web router is `app/config/router.php`. It uses explicit Phalcon routes and two route groups:

- `/{tenant_slug}/{company_slug}` for company-scoped dashboard, settings, and reporting pages;
- `/{tenant_slug}` for tenant-level redirect and company selection behavior.

The current public `/api{params:.*}` route remains a legacy redirect to `IndexController::legacyRedirectAction`. The specific `/api/v1/{api_name}` route is registered after that fallback and dispatches to `AdapterApiController::fetchAction`; it uses endpoint-level API-key verification rather than browser-session authentication.

When adding the adapter route, register it explicitly and before the generic legacy `/api` fallback according to Phalcon's reverse route matching behavior. Avoid allowing a generic tenant-slug route to swallow adapter paths.

## 3. Proposed Request Contract

Target route:

```text
GET /api/v1/{api_name}
```

Authentication should accept a credential from `X-API-Key`, or from `apikey` only if compatibility requires it. Prefer the header so keys are less likely to appear in access logs and browser history. Compare a supplied key against a stored verifier using a constant-time comparison; never log the key.

A request should be rejected before connecting to the external database when:

- the endpoint does not exist or is disabled (`404` or a deliberately indistinguishable `404`);
- the API key is missing or invalid (`401`);
- the endpoint is not permitted for the requested tenant/company context (`403`);
- required query parameters are missing or unexpected (`400`).

The exact status policy should be documented and tested consistently.

## 4. Safe Query Extraction

The endpoint record supplies the query template. Request parameters supply values only:

```sql
SELECT * FROM subsidiary_products WHERE user_id = :user_id
```

The implementation should parse the named placeholders from the template, allow only the corresponding request keys, and bind values through the selected driver. It must never concatenate request data into SQL. Table names, column names, sort expressions, operators, and complete SQL fragments must not be user-controlled.

Use the Phalcon DB adapter or a dedicated PDO connection for supported SQL drivers. MongoDB requires a separately reviewed driver and BSON filter mapping; it must not be treated as SQL with string substitution. Set connection, statement, row-count, and response-size limits so an endpoint cannot unintentionally dump an entire vendor database.

## 5. Suggested Execution Flow

1. Read the route parameter and normalize/validate `api_name`.
2. Load the enabled endpoint and its connection metadata from the UDA store.
3. Authenticate the API key before touching the remote connection.
4. Determine the trusted tenant/company scope and validate endpoint ownership.
5. Extract only declared placeholders from the request.
6. Bind values with the correct scalar type and execute with a timeout.
7. Convert rows/documents to the translator's input shape.
8. Return JSON with a stable content type, request correlation ID, and bounded payload.
9. Record duration, endpoint ID, row count, and outcome without secrets or raw query parameters.

Remote driver errors should be mapped to a generic `502`/`503` response for callers while detailed diagnostics go to protected server logs. Do not return SQL statements, hostnames, usernames, stack traces, or credential material.

## 6. Current Application Integration

If the endpoint is hosted inside the current CRM application, the controller should be added to the autoloaded controller directories and its route should be kept separate from `TenantBaseController` unless the request carries a verified CRM session. Machine-to-machine adapter calls will normally not have the browser session used by `LoginController`, so API-key authentication and tenant binding must be designed independently.

The current DI `db` service points to the CRM MariaDB database. A future adapter implementation should create a controlled connection from validated endpoint metadata rather than mutating the shared CRM connection. Connection creation belongs in a dedicated service with allow-listed drivers and safe timeout settings.

## 7. Acceptance Criteria

- The `/api/v1` route is registered; production enablement should wait for an integration test against a representative external source.
- Invalid keys cannot trigger external connections.
- All dynamic values are bound parameters.
- Disabled endpoints and unauthorized tenant/company requests are blocked.
- Responses and logs do not leak credentials or vendor SQL details.
- Large results, slow queries, and remote outages fail predictably.
