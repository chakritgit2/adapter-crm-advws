-- Current-codebase reconciliation migration.
--
-- Baseline: resource/mdSource/--Database-Schema.md
-- This migration is additive and restores the UDA tables required by the
-- current AdapterController, AdapterApiController, and SyncTask code.
-- Apply tenant_system/base schema first so tenants and companies exist.

CREATE TABLE IF NOT EXISTS adapter_connections (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    company_id INT NULL,
    name VARCHAR(120) NOT NULL,
    engine ENUM('mysql', 'mariadb', 'pgsql', 'mongodb') NOT NULL,
    host VARCHAR(255) NOT NULL,
    port INT NOT NULL,
    db_name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NULL,
    password_ciphertext TEXT NULL,
    options_json JSON NULL,
    status ENUM('active', 'disabled', 'failed') NOT NULL DEFAULT 'active',
    last_tested_at DATETIME NULL,
    last_error VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_adapter_connections_scope (tenant_id, company_id),
    CONSTRAINT fk_adapter_connections_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    CONSTRAINT fk_adapter_connections_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS adapter_endpoints (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    company_id INT NULL,
    connection_id INT NOT NULL,
    api_name VARCHAR(100) NOT NULL,
    query_template TEXT NOT NULL,
    api_key_hash VARCHAR(255) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    sync_enabled TINYINT(1) NOT NULL DEFAULT 0,
    sync_cursor_column VARCHAR(100) NULL,
    webhook_url VARCHAR(500) NULL,
    webhook_api_key_ciphertext TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_adapter_endpoint_name (api_name),
    KEY idx_adapter_endpoints_scope (tenant_id, company_id),
    CONSTRAINT fk_adapter_endpoints_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    CONSTRAINT fk_adapter_endpoints_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL,
    CONSTRAINT fk_adapter_endpoints_connection FOREIGN KEY (connection_id) REFERENCES adapter_connections (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS adapter_sync_checkpoints (
    id INT NOT NULL AUTO_INCREMENT,
    endpoint_id INT NOT NULL,
    cursor_value VARCHAR(255) NULL,
    status ENUM('idle', 'running', 'failed') NOT NULL DEFAULT 'idle',
    last_attempted_at DATETIME NULL,
    last_successful_at DATETIME NULL,
    last_error VARCHAR(500) NULL,
    lease_token CHAR(36) NULL,
    lease_expires_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_adapter_checkpoint_endpoint (endpoint_id),
    CONSTRAINT fk_adapter_checkpoint_endpoint FOREIGN KEY (endpoint_id) REFERENCES adapter_endpoints (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
