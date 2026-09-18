# Deployment and Infrastructure

This document details the Docker containerization, build pipeline, runtime configuration, web server setup, and deployment process.

---

## Docker Multi-Stage Build

The `Dockerfile` uses a 2-stage build to separate frontend asset compilation from the PHP runtime.

### Stage 1: Frontend Builder

```dockerfile
FROM node:20-bookworm-slim AS frontend-builder
WORKDIR /app

COPY package.json package-lock.json ./
COPY . .
RUN npm install
# RUN npm run build  (Svelte build — currently commented out)
```

- **Base image:** `node:20-bookworm-slim`
- **Purpose:** Install npm dependencies and compile frontend assets
- **Note:** The Svelte build step is commented out; only Tailwind CSS is actively used

### Stage 2: PHP Runtime

```dockerfile
FROM phalconphp/cphalcon:v5.9.2-php8.4
```

- **Base image:** Official Phalcon image with c-phalcon v5.9.2 on PHP 8.4
- **Purpose:** Production runtime with PHP-FPM

#### System Dependencies Installed

```dockerfile
RUN apt-get update && apt-get install -y \
    curl \
    librabbitmq-dev \
    libssh-dev \
    libxml2-dev \
    git \
    unzip \
    openssh-server \
    && docker-php-ext-install pdo_mysql dom bcmath \
    && curl -sSLo /tmp/amqp.tgz https://pecl.php.net/get/amqp \
    && pecl install /tmp/amqp.tgz \
    && docker-php-ext-enable amqp \
    && rm /tmp/amqp.tgz
```

| Package / Extension | Purpose |
|---------------------|---------|
| `curl` | HTTP client for external API calls |
| `librabbitmq-dev` + `amqp` (PECL) | RabbitMQ message queue support |
| `libssh-dev` | SSH2 support |
| `libxml2-dev` + `dom` | XML/DOM processing |
| `pdo_mysql` | MySQL/MariaDB database driver |
| `bcmath` | Arbitrary precision math |
| `openssh-server` | SSH access for development |
| `git` | Version control (for dev-mode git clone) |
| `unzip` | Archive extraction |

#### SSHD Configuration (Development Only)

```dockerfile
RUN mkdir -p /run/sshd \
    && echo 'PermitRootLogin yes' >> /etc/ssh/sshd_config \
    && echo 'PasswordAuthentication yes' >> /etc/ssh/sshd_config
```

SSH is configured but only started in development mode (controlled by `start.sh`).

#### Composer Installation

```dockerfile
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --optimize-autoloader
```

Composer is installed from the official Docker image. Dependencies are installed with `--no-dev` (production) and `--optimize-autoloader` for better class resolution performance.

#### Frontend Asset Copy

```dockerfile
COPY --from=frontend-builder /app/public /app/public
```

Compiled frontend assets from Stage 1 are copied into the runtime image.

#### Permissions

```dockerfile
RUN chown -R www-data:www-data /app
```

All application files are owned by `www-data` (the PHP-FPM user).

#### Port and Entrypoint

```dockerfile
EXPOSE 9000
CMD ["/usr/local/bin/start.sh"]
```

PHP-FPM listens on port 9000 (standard FPM port). The container runs `start.sh` as its entrypoint.

---

## start.sh — Container Entrypoint

The `start.sh` script handles environment-specific initialization and starts PHP-FPM.

### Development Mode (`ENV_MODE=development`)

```bash
if [[ "$ENV_MODE" == "development" ]]; then
    # 1. Set root password
    echo "root:${SSH_PASSWORD}" | chpasswd

    # 2. Configure SSH port
    sed -i '/^Port /d' /etc/ssh/sshd_config
    echo "Port ${SSH_PORT}" >> /etc/ssh/sshd_config

    # 3. Start SSH daemon
    /usr/sbin/sshd

    # 4. Git clone (fresh checkout)
    git clone https://<token>@github.com/ADVWS/hr-advws .
fi
```

In development mode:
- SSH is enabled with configurable port (`SSH_PORT`, default 2222) and password (`SSH_PASSWORD`, default "root")
- The application is freshly cloned from GitHub, overwriting the Docker build's copy
- This allows live development without rebuilding the image

### Production Mode (`ENV_MODE=production`)

```bash
else
    echo "ENV_MODE is production: Skipping SSH / Clone Git..."
fi
```

In production mode:
- No SSH access
- No git clone — uses the code baked into the Docker image

### Cache Directory

```bash
mkdir cache
chown www-data:www-data cache
```

The `cache/` directory is created and owned by `www-data` for Volt compiled templates.

### Tailwind CSS Build

```bash
cd /app/app
curl -sLO https://github.com/tailwindlabs/tailwindcss/releases/download/v4.3.2/tailwindcss-linux-x64
chmod +x tailwindcss-linux-x64
```

The Tailwind CSS 4 standalone binary is downloaded at runtime (not baked into the image).

#### Production Tailwind Build

```bash
if [[ "$ENV_MODE" == "production" ]]; then
    ./tailwindcss-linux-x64 -i ../resource/css/main.css -o ../public/css/main.css --minify
```

One-time minified build for production.

#### Development Tailwind Watch

```bash
else
    ./tailwindcss-linux-x64 -i ../resource/css/main.css -o ../public/css/main.css --watch --minify &
fi
```

Watch mode runs in the background (`&`) — recompiles CSS on file changes. Both modes use `--minify`.

### PHP-FPM Start

```bash
exec php-fpm
```

PHP-FPM is started in the foreground (`exec` replaces the shell process), keeping the container running.

---

## Environment Variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `ENV_MODE` | — | `development` or `production` (controls SSH, git clone, Tailwind mode) |
| `SSH_PORT` | `2222` | SSH port for development access |
| `SSH_PASSWORD` | `root` | Root SSH password for development |
| `BASE_PATH` | Auto-detected | Base application path (used in `config.php`) |

---

## Web Server Configuration

### Apache .htaccess (`public/.htaccess`)

```apache
AddDefaultCharset UTF-8

<IfModule mod_rewrite.c>
    RewriteEngine On

    # Forward Authorization header
    RewriteCond %{HTTP:Authorization} ^(.*)
    RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]

    # Serve static assets directly
    RewriteCond %{REQUEST_URI} \.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|pdf|zip)$ [NC]
    RewriteRule .* - [L]

    # Route all other requests to index.php
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php?_url=/$1 [QSA,L]
</IfModule>
```

### Cache Headers

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 6 month"
</IfModule>

<IfModule mod_headers.c>
    <FilesMatch "\.(gif|webp)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
    <FilesMatch "\.css$">
        Header set Cache-Control "public, max-age=2592000, immutable"
    </FilesMatch>
</IfModule>
```

- Images (GIF, WebP): 1-year cache with `immutable`
- CSS: 6-month cache with `immutable`

### Application Entry Point (`public/index.php`)

```php
$di = new FactoryDefault();
include APP_PATH . '/config/services.php';
include APP_PATH . '/config/router.php';
$config = $di->getConfig();
include APP_PATH . '/config/loader.php';
$application = new \Phalcon\Mvc\Application($di);
echo $application->handle($_SERVER['REQUEST_URI'])->getContent();
```

The entry point:
1. Creates the DI container with default services
2. Registers custom services
3. Configures routes
4. Loads the autoloader
5. Creates the MVC application
6. Handles the request and outputs content

### Error Handling

```php
} catch (\Exception $e) {
    echo $e->getMessage() . '<br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}
```

Exceptions are caught and displayed directly. In production, this should be replaced with a proper error handler that logs errors and shows a user-friendly error page.

---

## CLI Console

The `app/console.php` file provides a separate CLI entry point for background tasks:

```php
$di = new Cli();
$console = new Console($di);
$arguments = [
    'task' => $arguments[0] ?? null,
    'action' => $arguments[1] ?? null,
    'params' => $params
];
$console->handle($arguments);
```

### CLI Tasks

| Task | Purpose |
|------|---------|
| `EmailTask` | Process email queue (calls `EmailService::sendInternal()`) |
| `ApiWorkerTask` | Background API worker (38KB — extensive task) |

### CLI Usage

```bash
php app/console.php EmailTask send
php app/console.php ApiWorkerTask process
```

The CLI console has its own DI container (`Phalcon\Di\FactoryDefault\Cli`) with a subset of services (config, db, errorService, emailService).

---

## Deployment Workflow

### Development

1. Build Docker image: `docker build -t hr-advws .`
2. Run container with `ENV_MODE=development`
3. SSH access available on configured port
4. Git clone pulls latest code on container start
5. Tailwind CSS runs in watch mode
6. Volt templates recompile on every request (`always => true`)

### Production

1. Build Docker image: `docker build -t hr-advws .`
2. Run container with `ENV_MODE=production`
3. No SSH access
4. Code is baked into the image (no git clone)
5. Tailwind CSS compiled once with minification
6. Volt caching should be enabled (`always => false`) for performance
7. PHP-FPM serves requests on port 9000
8. A reverse proxy (nginx/Apache) should forward HTTP requests to PHP-FPM

### Database

The application connects to `mariadb.cdi-advws` — a Docker internal hostname suggesting MariaDB runs as a separate container in the same Docker network. The database name is `hr` with UTF-8 charset.
