# -----------------------------------------
# STAGE 1: The UI Engine (Svelte & Tailwind)
# -----------------------------------------
FROM node:20-bookworm-slim AS frontend-builder
WORKDIR /app

# 1. Install frontend dependencies first (better Docker caching)
COPY package.json package-lock.json ./

# Install dependencies (npm ci is reproducible from package-lock.json)
RUN npm ci

# 2. Copy frontend source code
COPY . .

# 3. Compile Tailwind CSS 4 with the npm-installed @tailwindcss/cli.
#    Version is sourced from package.json (single source of truth).
#    The CLI auto-detects content from the .volt templates copied above.
RUN npx @tailwindcss/cli -i /app/resource/css/main.css -o /app/public/css/main.css --minify

# -----------------------------------------
# STAGE 2: The API Engine (Phalcon PHP + SSHD)
# -----------------------------------------
FROM phalconphp/cphalcon:v5.9.2-php8.4

# 1. Switch to root to perform administrative installations
USER root
WORKDIR /app

# 2. Install dependencies (Added openssh-server here)
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

# 3. Prepare SSHD environment & configure base root login
RUN mkdir -p /run/sshd \
    && echo 'PermitRootLogin yes' >> /etc/ssh/sshd_config \
    && echo 'PasswordAuthentication yes' >> /etc/ssh/sshd_config

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy PHP backend application code
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader


# Bring over the compiled Tailwind UI from Stage 1
COPY --from=frontend-builder /app/public /app/public

# Set proper permissions for PHP-FPM
# Note: Root needs access for SSH, but app files can belong to www-data
RUN chown -R www-data:www-data /app

# 4. Add the runtime script to handle ENV vars and start services
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose PHP-FPM port. (The SSH port will be dynamic, but you map it at runtime)
EXPOSE 9000

# Use the script to launch both SSHD and PHP-FPM
CMD ["/usr/local/bin/start.sh"]