# Security Practices

This document details the security mechanisms implemented across the application, including encryption, password hashing, session management, cookie signing, CSRF considerations, and input validation.

---

## Security Overview

| Mechanism | Implementation | Status |
|-----------|---------------|--------|
| Password Hashing | Custom `base64_encode` + `sha256` | Active |
| API Credential Encryption | AES-256-GCM via `CredentialEncryption` | Active |
| Session Management | `Phalcon\Session\Manager` with Stream adapter | Active |
| Cookie Signing | `Phalcon\Http\Response\Cookies` with sign key | Active |
| Security Component | `Phalcon\Encryption\Security` (work factor 12) | Active |
| CSRF Protection | Token-based (commented out) | Inactive |
| Input Filtering | Phalcon request type filtering | Active |
| Multi-Tenant Isolation | `company_id` on all queries + `TenantResolver` | Active |
| UUID Public IDs | `TranslateUuidTrait` — internal IDs never exposed | Active |

---

## CredentialEncryption — AES-256-GCM

The `CredentialEncryption` helper class provides symmetric encryption for sensitive API credentials stored in the database.

### Implementation

```php
class CredentialEncryption
{
    private string $key;
    private string $cipher = 'aes-256-gcm';

    public function __construct(string $key)
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Encryption key cannot be empty.');
        }
        $this->key = hash('sha256', $key, true);
    }
```

### Encryption Flow

```php
public function encrypt(string $plaintext): string
{
    $iv = random_bytes(12);                    // 12-byte IV for GCM
    $tag = '';
    $ciphertext = openssl_encrypt(
        $plaintext, $this->cipher, $this->key,
        OPENSSL_RAW_DATA, $iv, $tag, '', 16    // 16-byte auth tag
    );
    return base64_encode($iv . $tag . $ciphertext);
}
```

The encrypted output is: `base64(IV[12] + TAG[16] + CIPHERTEXT)`.

### Decryption Flow

```php
public function decrypt(string $encrypted): string
{
    $data = base64_decode($encrypted);
    $iv = substr($data, 0, 12);                // Extract IV
    $tag = substr($data, 12, 16);              // Extract auth tag
    $ciphertext = substr($data, 28);           // Extract ciphertext
    return openssl_decrypt(
        $ciphertext, $this->cipher, $this->key,
        OPENSSL_RAW_DATA, $iv, $tag
    );
}
```

### Key Management

The encryption key is configured in `config.php`:
```php
'encryption' => [
    'credentialsKey' => 'adc-api-credentials-secure-key-2026-change-me',
],
```

The key is SHA-256 hashed before use, producing a 32-byte key suitable for AES-256.

### Security Properties

- **AES-256-GCM** — Authenticated encryption (confidentiality + integrity)
- **Random IV** — Fresh 12-byte IV per encryption operation via `random_bytes()`
- **Auth tag** — 16-byte GCM authentication tag detects tampering
- **Validation** — Minimum 28-byte length check on decryption (IV + tag minimum)

---

## Password Hashing

### Current Implementation

The `LoginController` uses a custom password hashing approach:

```php
static public function encryptPass($password)
{
    $encpass = base64_encode($password);
    $encpass = hash("sha256", $encpass);
    return $encpass;
}
```

This is used for both:
- **Login verification** — Comparing submitted password hash against stored `password_hash`
- **Password setup** — Hashing new passwords before storage

### Authentication Query

```php
$user = $this->db->fetchOne(
    "SELECT * FROM admin_users WHERE email = :email AND password_hash = :password",
    Phalcon\Db\Enum::FETCH_ASSOC,
    ['email' => $email, 'password' => $password]
);
```

### Security Considerations

The current approach uses `sha256` without a salt, which is less secure than bcrypt or Argon2. The `Phalcon\Encryption\Security` component (registered with work factor 12) is available but not used for password hashing. The `AuthService` class references a `$user->verifyPassword()` method, suggesting a more secure implementation was planned but not yet activated.

---

## Session Management

### Configuration

```php
$di->setShared('session', function () {
    $session = new SessionManager();
    $stream = new SessionStream([
        'savePath' => sys_get_temp_dir(),
    ]);
    $session->setAdapter($stream)->start();
    return $session;
});
```

- **Adapter:** `Phalcon\Session\Adapter\Stream` — File-based session storage
- **Save path:** System temp directory (`sys_get_temp_dir()`)
- **Auto-start:** Session starts immediately when the service is accessed

### Session Data

The `auth` session key stores user identity:

```php
$this->session->set('auth', [
    'id' => $user['id'],
    'email' => $user['email'],
    'name' => $user['name'],
    'role' => $user['role'],
]);
```

Additional session keys:
- `active_language` — Currently selected language for translations
- `returnUrl` — URL to return to after login

### Session Destruction

```php
public function logoutAction()
{
    $this->session->destroy();
    $this->response->redirect($this->config['loginPath']);
}
```

---

## Cookie Security

### Configuration

```php
$di->setShared('cookies', function () {
    $config = $this->getConfig();
    $cookies = new Phalcon\Http\Response\Cookies();
    $cookies->setSignKey($config->cookie->signKey);
    return $cookies;
});
```

Cookies are signed with a long random key to prevent tampering. The sign key is configured in `config.php`:

```php
'cookie' => [
    'signKey' => "tpdL(/ldn`Oneh]7S_wNkR6ql-<*Y-Y55SnPx>}3*>`&N9>E2n)wQBb8EmjL&LpSAMTADC"
],
```

### Remember-Me Cookie (AuthService)

The `AuthService` sets remember-me cookies with security flags:

```php
$this->cookies->set(
    self::REMEMBER_ME_COOKIE,
    json_encode(['user_id' => $user->id, 'token' => $token]),
    $expires,    // 30 days
    '/',         // path
    true,        // httpOnly
    null,        // domain
    true         // secure
);
```

---

## Phalcon Security Component

```php
$di->setShared('security', function () {
    $security = new Phalcon\Encryption\Security();
    $security->setWorkFactor(12);
    return $security;
});
```

- **Work factor 12** — bcrypt cost factor (2^12 = 4,096 iterations)
- Available for password hashing and token generation
- CSRF token methods (`getTokenKey()`, `getToken()`) are available but currently unused

---

## CSRF Protection

CSRF protection is **commented out** in the current codebase. The infrastructure exists but is not active:

### In LoginController (commented out)
```php
// $tokenKey = $this->security->getTokenKey();
// $tokenValue = $this->security->getToken();
// $this->session->set('csrfKey', $tokenKey);
// $this->session->set('csrfValue', $tokenValue);
```

### In services.php (commented out)
```php
// $di->setShared('csrf', function () {
//     return new class () {
//         public function generateTokenKey() { return bin2hex(random_bytes(16)); }
//         public function generateTokenValue() { return bin2hex(random_bytes(32)); }
//     };
// });
```

### Recommendation

CSRF protection should be enabled for all POST routes, especially for state-changing operations (create, update, delete, approve, reject).

---

## Set-Password Token Flow

New admin users receive a set-password link with a token:

### Token Generation
A unique token is stored in `admin_users.set_password_token` and sent via email invitation.

### Token Validation
```php
$user = $this->db->fetchOne(
    "SELECT id FROM admin_users WHERE set_password_token = :token LIMIT 1",
    Phalcon\Db\Enum::FETCH_ASSOC,
    ['token' => $token]
);
```

### Password Complexity Requirements
```php
if (strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[^A-Za-z0-9]/', $password)) {
    // Reject: must be 8+ chars with uppercase, lowercase, number, and special character
}
```

### Token Consumption
After setting the password, the token is nullified:
```php
"UPDATE admin_users SET password_hash = :password_hash, set_password_token = NULL WHERE id = :id"
```

This ensures tokens are single-use.

---

## Input Filtering

### Phalcon Request Type Filtering

Controllers use Phalcon's built-in type filtering for request data:

```php
$email = $this->request->getPost('email', 'string');
$password = $this->request->getPost('password', 'string', '');
$code = trim($this->request->getPost('language_code', 'string', ''));
```

Available filters: `string`, `int`, `float`, `boolean`, `email`, `absint`, `striptags`, `trim`, `alphanum`, `lower`, `upper`.

### Parameterized SQL Queries

All database queries use parameterized bindings to prevent SQL injection:

```php
// Named parameters
$this->db->fetchAll($sql, \Phalcon\Db\Enum::FETCH_ASSOC, [
    'company_id' => $companyId,
    'lang' => $activeLanguage,
]);

// Positional parameters
$this->db->execute(
    "INSERT INTO email_outbox (recipient, subject, body, status) VALUES (?, ?, ?, ?)",
    [$recipient, $subject, $body, EmailOutbox::STATUS_NEW]
);
```

---

## .htaccess Security

### Authorization Header Forwarding

```apache
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
```

This forwards the `Authorization` header through Apache's rewrite to PHP, which is necessary for API authentication when PHP runs behind Apache's mod_rewrite.

### Static Asset Protection

```apache
RewriteCond %{REQUEST_URI} \.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|pdf|zip)$ [NC]
RewriteRule .* - [L]
```

Static assets are served directly without going through `index.php`, preventing unnecessary PHP execution.

---

## Multi-Tenant Data Isolation

### Query-Level Isolation

Every database query that touches tenant-scoped data includes a `company_id` filter:

```php
"WHERE p.company_id = :company_id"
"AND e.company_id = :company_id"
```

### UUID-Based Access

The `TranslateUuidTrait::findByUuid()` method enforces tenant isolation during UUID lookups:

```php
if ($companyId !== null) {
    $conditions .= ' AND company_id = :company_id:';
    $bind['company_id'] = $companyId;
}
```

### TenantBaseController Guard

The `beforeExecuteRoute()` hook validates:
1. User is authenticated
2. Tenant slug resolves to an active company
3. User has access to the company (via `company_user_map`)
4. Company is not suspended

This ensures no cross-tenant data access is possible through the web application.
