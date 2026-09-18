# Framework and Stack Overview

This document provides a comprehensive overview of the technology stack, framework architecture, and project structure of the HR system built on Phalcon PHP.

---

## Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend Framework | Phalcon (c-phalcon) | v5.9.2 |
| Runtime | PHP | 8.4 |
| Template Engine | Volt | (bundled with Phalcon 5.x) |
| Database | MariaDB / MySQL | via PDO adapter |
| CSS Framework | Tailwind CSS | v4.1.13+ |
| Frontend JS | Vanilla JS + Font Awesome | — |
| Containerization | Docker (multi-stage) | Node 20 + PHP 8.4 |
| Message Queue | RabbitMQ (AMQP) | via PHP amqp extension |
| Email Provider | SMTP2GO | REST API |
| Dependency Manager (PHP) | Composer | — |
| Dependency Manager (JS) | npm | — |

---

## Project Structure

```
hr.advws.com/
├── app/                        # Application root
│   ├── config/                 # Framework configuration
│   │   ├── config.php          # Global config (DB, paths, keys, integrations)
│   │   ├── loader.php          # Autoloader directory registration
│   │   ├── router.php          # Route definitions (public + tenant-aware)
│   │   └── services.php        # DI container service registrations
│   ├── console.php             # CLI console bootstrap (separate DI)
│   ├── controllers/            # 11 controllers (Dashboard, Index, Tenant, Login, etc.)
│   ├── credentials/            # Service account credentials (Google)
│   ├── helpers/                # Utility classes (TenantResolver, AuditQueryBuilder, CredentialEncryption)
│   ├── migrations/             # SQL migration scripts + PHP runners
│   ├── models/                 # 40 Phalcon MVC models
│   ├── services/               # 10 service classes (Auth, Email, Translation, OrgChart, etc.)
│   ├── tasks/                  # CLI tasks (ApiWorkerTask, EmailTask)
│   ├── traits/                 # TranslateUuidTrait
│   └── views/                  # Volt templates
│       ├── dashboard/          # Main dashboard views (9 items)
│       │   ├── reports/        # HR report views (5 items)
│       │   └── settings/       # Settings sub-views (17 items)
│       ├── error/              # Error pages
│       ├── index/              # Landing/index views
│       ├── layouts/            # Layout templates (admin, main, auth, loginwrap)
│       ├── login/              # Login views
│       └── partials/           # Reusable partials (flash, nav, footer, profile-menu)
├── cache/                      # Volt compiled template cache
├── public/                     # Web root
│   ├── .htaccess               # Apache rewrite rules + caching headers
│   ├── index.php               # Application entry point
│   ├── css/                    # Compiled Tailwind CSS output
│   ├── js/                     # Frontend JavaScript
│   ├── icons/                  # Favicon / app icons
│   └── img/                    # Static images
├── resource/                   # Non-code resources
│   ├── css/                    # Tailwind CSS source (main.css)
│   ├── data/                   # Data files
│   ├── email_templates/        # HTML email templates
│   ├── mdSource/               # Domain documentation source
│   └── mdSourceWorkflow/       # Workflow documentation source
├── vendor/                     # Composer dependencies
├── Dockerfile                  # Multi-stage Docker build
├── start.sh                    # Container entrypoint script
├── composer.json               # PHP dependencies
├── composer.lock               # Locked PHP dependency versions
├── package.json                # npm dependencies (Tailwind CSS)
└── package-lock.json           # Locked npm dependency versions
```

---

## Phalcon Framework Architecture

Phalcon is a C-extension PHP framework, meaning the framework core is compiled as a shared library and loaded as a PHP extension. This provides near-native performance compared to pure-PHP frameworks.

### Key Phalcon Components Used

- **`Phalcon\Mvc\Application`** — Full-stack MVC application handler (`public/index.php`)
- **`Phalcon\Di\FactoryDefault`** — Dependency Injector with auto-registered default services
- **`Phalcon\Mvc\Router`** + **`RouterGroup`** — URL routing with tenant-aware groups
- **`Phalcon\Mvc\View`** + **`VoltEngine`** — Template rendering with Volt compiler
- **`Phalcon\Db\Adapter\Pdo\Mysql`** — MySQL/MariaDB database adapter
- **`Phalcon\Session\Manager`** — Session management with stream adapter
- **`Phalcon\Encryption\Security`** — Security utilities (hashing, CSRF)
- **`Phalcon\Http\Response\Cookies`** — Signed cookie management
- **`Phalcon\Storage\Adapter\Stream`** — File-based cache adapter
- **`Phalcon\Flash\Direct`** — Flash messages with Bootstrap CSS classes
- **`Phalcon\Autoload\Loader`** — Directory-based PSR-4 autoloader
- **`Phalcon\Cli\Console`** — CLI console for background tasks
- **`Phalcon\Acl\Adapter\Memory`** — ACL (imported but currently unused)

### Application Bootstrap Flow

The entry point at `public/index.php` follows this sequence:

1. Define `BASE_PATH` and `APP_PATH` constants
2. Require Composer autoloader (`vendor/autoload.php`)
3. Instantiate `FactoryDefault` DI container
4. Include `config/services.php` — register all shared services
5. Include `config/router.php` — define and mount all routes
6. Get config from DI for inline setup
7. Include `config/loader.php` — register autoloader directories
8. Create `Phalcon\Mvc\Application` with DI
9. Handle request and output content

---

## Composer Dependencies

```json
{
    "require": {
        "phalcon/zephir": "dev-development"
    },
    "require-dev": {
        "phalcon/ide-stubs": "^5.14"
    }
}
```

The project relies primarily on the Phalcon C-extension itself rather than Composer packages. The `ide-stubs` package provides IDE autocompletion for Phalcon classes.

---

## npm Dependencies

```json
{
    "dependencies": {
        "@tailwindcss/cli": "^4.1.13",
        "tailwindcss": "^4.1.13"
    }
}
```

Tailwind CSS 4 is the sole frontend dependency, used for utility-first styling across all Volt templates.

---

## Docker Image Architecture

The Dockerfile uses a **2-stage build**:

### Stage 1: Frontend Builder (`node:20-bookworm-slim`)
- Copies `package.json` and `package-lock.json`
- Runs `npm install`
- Copies full project source
- (Svelte build step is commented out — currently only Tailwind is used)

### Stage 2: Runtime (`phalconphp/cphalcon:v5.9.2-php8.4`)
- Installs system dependencies: curl, librabbitmq-dev, libssh-dev, libxml2-dev, git, unzip, openssh-server
- Installs PHP extensions: `pdo_mysql`, `dom`, `bcmath`, `amqp` (via PECL)
- Configures SSHD for development access
- Installs Composer
- Copies PHP application code
- Runs `composer install --no-dev --optimize-autoloader`
- Copies compiled frontend assets from Stage 1
- Sets ownership to `www-data`
- Exposes port 9000 (PHP-FPM)
- Entrypoint: `start.sh`

---

## Database

- **Adapter:** MySQL (via `Phalcon\Db\Adapter\Pdo\Mysql`)
- **Host:** `mariadb.cdi-advws` (Docker internal hostname)
- **Database:** `hr`
- **Charset:** UTF-8
- **ORM:** Phalcon MVC Models with `setSource()` table mapping

The database schema covers two major domains:
1. **HR Domain** — employees, positions, leave management, overtime, milestones, translations, tenant management
2. **Legacy Ad Domain** — campaigns, ad groups, keywords, ads, audit alerts, performance tracking (inherited from a prior advertising platform codebase)

---

## External Integrations

| Service | Purpose | Configuration Location |
|---------|---------|------------------------|
| SMTP2GO | Transactional email delivery | `config.php` → `smtp2go` |
| Google Service Account | Google API access | `config.php` → `google` |
| N8N Webhook | Workflow automation | `config.php` → `n8n-webhook` |
| LINE OAuth | Social login (LineAuthService) | Runtime configuration |
| RabbitMQ (AMQP) | Message queue for async tasks | PHP `amqp` extension |
| Microsoft Clarity | Analytics (currently disabled) | `config.php` → `clarityOn` |

---

## Localization

The application supports a **polymorphic translation system** with tenant-scoped languages. Allowed translation targets are configured in `config.php`:

```php
'localization' => [
    'allowed_targets' => [
        'employees' => ['first_name', 'last_name'],
        'positions' => ['job_title', 'department'],
        'leave_types' => ['name'],
        'overtime_policies' => ['name'],
        'milestone_event_types' => ['name'],
    ],
],
```

This allows per-tenant, per-language translations of specific entity fields without duplicating the entire record.
