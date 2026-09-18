# Configuration and Dependency Injection Services

This document details the configuration files, dependency injection container setup, and service registrations that form the backbone of the Phalcon application.

---

## Configuration Files Overview

| File | Purpose |
|------|---------|
| `app/config/config.php` | Global configuration array (database, paths, keys, integrations) |
| `app/config/services.php` | DI container service registrations (web) |
| `app/config/loader.php` | Autoloader directory registration |
| `app/config/router.php` | Route definitions (see Routing & Multi-Tenant doc) |
| `app/console.php` | CLI console bootstrap with separate DI |

---

## config.php — Global Configuration

The configuration is returned as a `Phalcon\Config\Config` object. Two path constants are defined at the top:

```php
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');
```

### Configuration Sections

#### Database
```php
'database' => [
    'adapter'  => 'Mysql',
    'host'     => 'mariadb.cdi-advws',
    'username' => 'hr',
    'password' => '[b1dx[MkpKn!NcR/',
    'dbname'   => 'hr',
    'charset'  => 'utf8',
]
```
The adapter string is dynamically resolved in `services.php` as `'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter`.

#### Application Paths
All directory paths are derived from `APP_PATH`:
- `controllersDir`, `modelsDir`, `servicesDir`, `helpersDir`, `traitsDir`
- `viewsDir`, `cacheDir` (at `BASE_PATH/cache/`)
- `baseUri` set to `/`

#### Application Metadata
- `appName`: `'HR'`
- `appPublicName`: Thai display name for the HR department
- `loginPath`: `'/loginhrm'` (custom login URL path)

#### Encryption
- `credentialsKey`: Used by `CredentialEncryption` helper for AES-256-GCM encryption of API credentials

#### Cookie Signing
- `signKey`: Long random string for cookie integrity verification

#### SMTP2GO Email
- `api_key`: SMTP2GO REST API key
- `sender`: Display sender address (`HR ADVWS <hr@advws.com>`)

#### Google Service Account
- `service_account_json`: Path to Google service account JSON file

#### N8N Webhook
- URL, method (`POST`), and custom headers including an API key

#### Localization
- `allowed_targets`: Whitelist of entity tables and columns that can be translated (polymorphic translation system)

---

## services.php — DI Container Registration

The `services.php` file is included into the `FactoryDefault` DI instance (`$di`). It registers shared services using closures.

### Registered Services

#### 1. `config` (shared)
```php
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});
```
Lazily loads the config file on first access.

#### 2. `session` (shared)
```php
$di->setShared('session', function () {
    $session = new SessionManager();
    $stream = new SessionStream(['savePath' => sys_get_temp_dir()]);
    $session->setAdapter($stream)->start();
    return $session;
});
```
Uses `Phalcon\Session\Manager` with a `Stream` adapter. Session files are stored in the system temp directory.

#### 3. `url` (shared)
```php
$di->setShared('url', function () {
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);
    return $url;
});
```
URL resolver for generating application URLs.

#### 4. `view` (shared) — Volt Engine Setup
This is the most complex service registration. It configures:
- `Phalcon\Mvc\View` with the views directory
- Registers two template engines: `.volt` and `.phtml`
- Volt options: compiled path = cache directory, `always => true` (no caching, recompile every request for development)
- **Custom Volt compiler functions** (see Volt Templates & Views doc for full list)

#### 5. `security` (shared)
```php
$di->setShared('security', function () {
    $security = new Phalcon\Encryption\Security();
    $security->setWorkFactor(12);
    return $security;
});
```
Phalcon security component with bcrypt work factor 12.

#### 6. `db` (shared)
Dynamically instantiates the PDO adapter class based on config:
```php
$class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
return new $class($params);
```
Handles PostgreSQL adapter difference (no charset param).

#### 7. `modelsMetadata` (shared)
```php
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});
```
Uses `Phalcon\Mvc\Model\Metadata\Memory` — model metadata is stored in memory (not persisted).

#### 8. `flash` (non-shared)
Flash message service with Bootstrap CSS classes:
- `error` → `alert alert-danger`
- `success` → `alert alert-success`
- `notice` → `alert alert-info`
- `warning` → `alert alert-warning`

#### 9. `cache` (shared)
```php
$di->setShared('cache', function () {
    $serializerFactory = new SerializerFactory();
    $adapter = new Stream($serializerFactory, $options);
    return $adapter;
});
```
File-based cache with JSON serializer, 7200-second (2-hour) lifetime, stored in `BASE_PATH/cache/`.

#### 10. `cookies` (shared)
```php
$di->setShared('cookies', function () {
    $cookies = new Phalcon\Http\Response\Cookies();
    $cookies->setSignKey($config->cookie->signKey);
    return $cookies;
});
```
Cookie manager with signing key for integrity verification.

#### 11. `apicaller` (shared)
Creates an `ApiCallerService` with its own database connection instance (separate from the shared `db` service).

#### 12. `curl` (shared)
Registers the `CurlService` wrapper class.

#### 13. `errorService` (shared)
Registers the `ErrorService` with the DI container injected.

#### 14. `emailService` (shared)
Registers the `EmailService` with the DI container injected.

### Commented-Out Services
- `auth` — AuthService registration (commented out; auth is handled directly in LoginController)
- `dispatcher` — Event-based dispatcher with middleware (commented out; two versions exist)
- `csrf` — Custom CSRF token generator (commented out)

### Global Helper Functions
Two global functions are defined at the bottom of `services.php`:

- **`format_minutes($totalMinutes)`** — Converts minutes to human-readable duration (days/hours/minutes based on 480-minute workday)
- **`vd($ANYTHING, $SHOW_CALLER_FUNCS)`** — Debug var_dump wrapper with optional caller info

---

## loader.php — Autoloader

```php
$loader = new \Phalcon\Autoload\Loader();
$loader->setDirectories([
    $config->application->apiControllersDir,
    $config->application->controllersDir,
    $config->application->modelsDir,
    $config->application->traitsDir,
    $config->application->middleWareDir,
    $config->application->servicesDir,
    $config->application->helpersDir
]);
$loader->register();
```

Uses Phalcon's directory-based autoloader. Classes are resolved by class name within the registered directories. `clearstatcache(true)` is called at the top to help Docker volume mounts discover new files.

---

## console.php — CLI Bootstrap

A separate CLI entry point using `Phalcon\Cli\Console` with a `Phalcon\Di\FactoryDefault\Cli` DI container:

- Registers `config`, `db`, `errorService`, `emailService` services
- Loader registers `tasks/`, `models/`, `services/`, `helpers/` directories
- Also registers `App\Helpers` namespace mapping
- Parses `$argv` for task name, action, and params
- Catches `Phalcon\Cli\Dispatcher\Exception` for CLI errors

This is used for background tasks like the email worker (`EmailTask`) and API worker (`ApiWorkerTask`).
