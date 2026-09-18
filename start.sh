#!/bin/bash
set -euo pipefail

# Default fallbacks if environment variables are not provided
SSH_PORT=${SSH_PORT:-2222}
SSH_PASSWORD=${SSH_PASSWORD:-"root"}

# Default to production for security, overridden by Kubernetes YAML
ENV_MODE=${ENV_MODE:-"production"}

# Set the timezone to Asia/Bangkok (overrides any inherited value at runtime)
TZ=${TZ:-"Asia/Bangkok"}
export TZ
ln -snf "/usr/share/zoneinfo/${TZ}" /etc/localtime && echo "${TZ}" > /etc/timezone

# 1-3. Only enable SSH if ENV_MODE is exactly "production"
if [[ "$ENV_MODE" == "development" ]]; then
    echo "ENV_MODE is development: Enabling SSH..."
    
    # Set the root password dynamically
    echo "root:${SSH_PASSWORD}" | chpasswd

    # Configure the SSH Port dynamically
    sed -i '/^Port /d' /etc/ssh/sshd_config
    echo "Port ${SSH_PORT}" >> /etc/ssh/sshd_config

    # Start the SSH daemon in the background
    /usr/sbin/sshd
else
    echo "ENV_MODE is production: Skipping SSH / Clone Git..."
fi

mkdir -p cache
chown www-data:www-data cache

# 5. Start PHP-FPM in the foreground (keeps the container running)
exec php-fpm