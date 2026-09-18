# Service Layer

This document details the service classes that encapsulate business logic, external API integration, and cross-cutting concerns within the application.

---

## Service Layer Overview

Services are registered in the DI container (`services.php`) or instantiated directly within controllers. They encapsulate business logic that doesn't belong in controllers or models.

### Service Inventory

| Service | DI Registration | Extends | Purpose |
|---------|----------------|---------|---------|
| `AuthService` | Commented out | — | Session auth, remember-me, LINE login, registration |
| `EmailService` | `emailService` (shared) | — | SMTP2GO email, queue management, retry logic |
| `TranslationService` | Direct instantiation | `Injectable` | Polymorphic translation CRUD |
| `OrgChartService` | Direct instantiation | `Injectable` | Org chart data, node building, matrix connections |
| `ErrorService` | `errorService` (shared) | — | Structured error handling with error chaining |
| `CurlService` | `curl` (shared) | — | cURL wrapper for HTTP requests |
| `ApiCallerService` | `apicaller` (shared) | — | Legacy API caller with session-based auth |
| `LineAuthService` | Direct instantiation | — | LINE OAuth2 social login integration |

---

## AuthService

Handles user authentication, session management, and social login.

### Constructor
```php
public function __construct(DiInterface $di)
{
    $this->di = $di;
    $this->random = new Random();
    $this->cookies = $di->get('cookies');
    $this->session = $di->get('session');
    $this->user = $this->session->get('user') ? $this->session->get('user') : null;
}
```

### Key Methods

| Method | Purpose |
|--------|---------|
| `attemptLogin(string $email, string $password, bool $remember)` | Authenticate by email/password |
| `loginWithLine(string $lineId, array $profile, bool $remember)` | Create/login via LINE OAuth |
| `loginUser(User $user, bool $remember)` | Set session + optional remember-me cookie |
| `logout()` | Destroy session + remove remember-me token |
| `user()` | Get current authenticated user (session or cookie) |
| `check()` | Boolean check if user is logged in |
| `id()` | Get current user ID |
| `register(array $data)` | Create new user account |

### Remember-Me Flow

1. Generate a base64-safe token via `Phalcon\Encryption\Security\Random`
2. Store token in `remember_tokens` table with 30-day expiry
3. Set signed cookie with JSON payload (`user_id` + `token`)
4. On subsequent visit, `getUserFromRememberToken()` validates the cookie against the database

### Note

The `AuthService` is currently **commented out** in `services.php`. The `LoginController` handles authentication directly with inline SQL queries and session management. The `AuthService` appears to be a more sophisticated implementation that was developed but not yet wired into the active authentication flow.

---

## EmailService

Integrates with SMTP2GO REST API for transactional email delivery, with a queue-based processing model.

### Constructor
```php
public function __construct(DiInterface $di)
{
    $this->di = $di;
    $this->errorService = $di->get('errorService');
    $this->config = $di->get('config');
    $this->curlService = new CurlService();
    $this->db = $di->get('db');
}
```

### Key Methods

#### `queueEmail(string $recipient, string $subject, string $body): array`
Inserts an email into the `email_outbox` table with status `new`. Returns `['success' => true, 'id' => insertId]`.

#### `sendEmail(string $recipient, string $subject, string $bodyHtml): array`
Sends an email immediately via SMTP2GO API:
1. Validates SMTP2GO config (API key, sender)
2. Builds payload with HTML body + auto-generated text body
3. Sends via `CurlService::execute()` with `X-Smtp2go-Api-Key` header
4. Returns success with `email_id` and `request_id`, or error array

#### `sendInternal(): array`
Processes one email from the queue (worker pattern):
1. `BEGIN` transaction
2. `SELECT ... FOR UPDATE SKIP LOCKED` — grabs next `new` or `error` email
3. Updates status to `processing`
4. `COMMIT` — releases lock
5. Calls `sendEmail()` to actually send
6. On success → status = `success`
7. On failure → increments `retry_count`:
   - If `retry_count > MAX_RETRIES (2)` → status = `failed`
   - Otherwise → status = `error`, sets `next_retry_at` with exponential backoff (1 min, then 5 min)

### Email Queue States

```
new → processing → success
                  ↘ error (retry) → processing → success
                                  ↘ failed (max retries exceeded)
```

### HTML to Text Conversion

```php
private function stripHtmlToText(string $html): string
{
    $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $text = preg_replace('/<\/p>/i', "\n", $text ?? '');
    $text = strip_tags($text ?? '');
    return trim($text);
}
```

---

## TranslationService

Manages the polymorphic translation system — per-tenant, per-language, per-field translations.

### Constructor
```php
public function __construct(Phalcon\Db\Adapter\AdapterInterface $db)
{
    $this->db = $db;
}
```

Extends `Phalcon\Di\Injectable` for DI access (used to read `localization.allowed_targets` config).

### Key Methods

| Method | Purpose |
|--------|---------|
| `getInstalledLanguages(int $companyId)` | Active languages for a tenant |
| `getAllLanguages(int $companyId)` | All languages (including inactive) |
| `installLanguage(int $companyId, string $code, string $name)` | Add a new language (checks for duplicates) |
| `uninstallLanguage(int $companyId, int $languageId)` | Remove language + all translations (transactional) |
| `isTargetAllowed(string $table, string $column)` | Check if table+column is in `allowed_targets` config |
| `isLanguageInstalled(int $companyId, string $code)` | Check if language is active for tenant |
| `upsertTranslation(...)` | Insert or update a translation record |
| `deleteTranslation(int $companyId, int $translationId)` | Delete with tenant isolation check |
| `getTranslationsForRecord(...)` | Get all translations for a record, grouped by language → column |
| `getTranslatedValue(...)` | Get single translated value with fallback |
| `getAllowedTargets()` | Get allowed targets config as array |

### Translation Storage Pattern

Translations are stored in a single `translations` table with polymorphic references:

```
translations
├── company_id      (tenant isolation)
├── language_code   (e.g., 'th', 'es')
├── target_table    (e.g., 'employees', 'positions')
├── target_column   (e.g., 'first_name', 'job_title')
├── target_id       (integer ID of the record)
└── translation_value (the translated string)
```

This approach avoids schema duplication and allows any entity field to be translatable without adding columns.

---

## OrgChartService

Provides data for the D3.js organizational chart visualization.

### Constructor
```php
public function __construct(Phalcon\Db\Adapter\AdapterInterface $db)
{
    $this->db = $db;
}
```

### Key Methods

| Method | Purpose |
|--------|---------|
| `getPositions(int $companyId, ?string $activeLanguage, int $adminUserId)` | Fetch all positions with employee assignments, optionally with translation joins |
| `buildChartNodes(array $positions, bool $canViewSalary)` | Transform positions into D3 chart node format |
| `getMatrixConnections(int $companyId)` | Fetch dotted-line (matrix) reporting connections |

### Translation-Aware Queries

When a non-English language is active, `getPositions()` joins the `translations` table multiple times with `COALESCE` to provide translated values with fallback to the original:

```sql
SELECT p.id, p.parent_position_id, p.is_approved,
  COALESCE(t_jt.translation_value, p.job_title) AS job_title,
  COALESCE(t_dep.translation_value, p.department) AS department,
  COALESCE(t_fn.translation_value, e.first_name) AS first_name,
  COALESCE(t_ln.translation_value, e.last_name) AS last_name,
  e.salary, ...
FROM positions p
LEFT JOIN position_assignments pa ON pa.position_id = p.id AND pa.end_date IS NULL
LEFT JOIN employees e ON e.id = pa.employee_id
LEFT JOIN translations t_jt ON t_jt.company_id = p.company_id AND t_jt.target_table = 'positions' AND t_jt.target_column = 'job_title' AND t_jt.target_id = p.id AND t_jt.language_code = :lang
LEFT JOIN translations t_dep ON ...
WHERE p.company_id = :company_id
```

---

## ErrorService

Provides a structured error handling pattern used across services. Instead of throwing exceptions, services return error arrays that can be checked with `isError()`.

### Error Array Structure

```php
[
    'error_code' => 'SMTP2GO_SEND_FAILED',
    'error_message' => 'HTTP 500',
    'error_message_display' => '',
    'error_prior' => [
        // Chained prior errors for debugging
    ]
]
```

### Key Methods

| Method | Purpose |
|--------|---------|
| `error($ERROR_CODE, $ERROR_MESSAGE, $ERROR_PRIOR)` | Create an error array, chaining prior errors |
| `isError($RESULT_OBJECT)` | Check if a result is an error array (has `error_code` or `error_message`) |

### Usage Pattern

```php
// In a service
return $this->errorService->error('EMAIL_QUEUE_FAILED', $e->getMessage());

// In a controller or calling service
$result = $emailService->sendEmail(...);
if ($this->errorService->isError($result)) {
    // Handle error — $result['error_message'] contains details
}
```

### Error Chaining

When a service calls another service that returns an error, the `error()` method chains the prior error into `error_prior`, preserving the full error trace:

```php
if ($this->errorService->isError($ERROR_PRIOR)) {
    array_push($ERROR_PRIOR['error_prior'], [...]);
    $ERROR_MESSAGE = "{$ERROR_PRIOR['error_message']}|E:{$ERROR_CODE}";
    $ERROR_CODE = $ERROR_PRIOR['error_code'];
}
```

---

## CurlService

A cURL wrapper service used for all outbound HTTP requests.

### Configuration

```php
define("CURL_CONNECT_TIMEOUT", 30);
define("CURL_TIMEOUT", 60);
define("CURL_DEFAULT_CONTENT_TYPE_HEADER", "application/json");
$CURL_CONFIG['CURL_VERBOSE'] = false;                    // Must be FALSE in production
$CURL_CONFIG['CURLOPT_SSL_VERIFYHOST_AND_PEER'] = true;  // Must be TRUE in production
```

### Key Methods

| Method | Purpose |
|--------|---------|
| `execute($url, $method, $headers, $params, $isJson)` | Execute a cURL request |

### Request Methods

```php
const REQUEST_GET = 'GET';
const REQUEST_POST = 'POST';
const REQUEST_DELETE = 'DELETE';
const REQUEST_PATCH = 'PATCH';
```

### Parameters

- When `$isJson = true` — params are JSON-encoded and sent as request body
- When `$isJson = false` — params are URL-encoded via `http_build_query()`
- Headers are passed as key-value pairs and converted to `Key: Value` format

---

## ApiCallerService

A legacy service for calling an external API (samtstore.com) with session-based authentication.

### Constructor

The constructor validates the session and retrieves a `login_key` from the database:

```php
public function __construct($dbInstance) {
    $this->db = $dbInstance;
    if (!isset($_SESSION['auth']['id']) || $_SESSION['auth']['id'] <= 0) {
        header('Location: login');
    }
    $this->loginKey = $this->db->fetchOne(
        "SELECT `key` FROM login_key WHERE owner_type = 1 AND owner_id = :owner_id",
        \Phalcon\Db\Enum::FETCH_ASSOC,
        ["owner_id" => $_SESSION['auth']['id']]
    );
    // ...
}
```

### Key Methods

| Method | Purpose |
|--------|---------|
| `callApi(string $endpoint, array $_body, array $headers, int $timeout)` | Call external API with session-based auth |

This service is registered in the DI container as `apicaller` with its own database connection instance.

---

## LineAuthService

Implements LINE OAuth2 social login integration.

### LINE API Endpoints

| Constant | URL |
|----------|-----|
| `AUTH_URL` | `https://access.line.me/oauth2/v2.1/authorize` |
| `TOKEN_URL` | `https://api.line.me/oauth2/v2.1/token` |
| `PROFILE_URL` | `https://api.line.me/v2/profile` |
| `VERIFY_URL` | `https://api.line.me/oauth2/v2.1/verify` |
| `REVOKE_URL` | `https://api.line.me/oauth2/v2.1/revoke` |

### Constructor

Accepts a configuration array with `client_id`, `client_secret`, `redirect_uri`, and `channel_secret`.

### Integration with AuthService

The `AuthService::loginWithLine()` method uses LINE profile data to find or create a user, then logs them in. The `LineAuthService` handles the OAuth2 flow (authorization redirect, token exchange, profile fetch).
