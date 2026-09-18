-- Persistent adapter connection and API endpoint logs.
-- Apply after 001_adapter_current_codebase.sql.

CREATE TABLE IF NOT EXISTS adapter_connection_logs (
    id BIGINT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    company_id INT NULL,
    connection_id INT NULL,
    event_type VARCHAR(40) NOT NULL DEFAULT 'test',
    status ENUM('success', 'failure') NOT NULL,
    duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
    message VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_adapter_connection_logs_scope (tenant_id, company_id, created_at),
    KEY idx_adapter_connection_logs_connection (connection_id, created_at),
    CONSTRAINT fk_adapter_connection_logs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    CONSTRAINT fk_adapter_connection_logs_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL,
    CONSTRAINT fk_adapter_connection_logs_connection FOREIGN KEY (connection_id) REFERENCES adapter_connections (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS adapter_endpoint_logs (
    id BIGINT NOT NULL AUTO_INCREMENT,
    tenant_id INT NULL,
    company_id INT NULL,
    endpoint_id INT NULL,
    api_name VARCHAR(100) NOT NULL,
    request_id VARCHAR(64) NULL,
    status_code SMALLINT UNSIGNED NOT NULL,
    auth_result ENUM('accepted', 'invalid', 'not_found', 'unknown') NOT NULL DEFAULT 'unknown',
    row_count INT UNSIGNED NOT NULL DEFAULT 0,
    duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
    error_code VARCHAR(80) NULL,
    client_ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_adapter_endpoint_logs_scope (tenant_id, company_id, created_at),
    KEY idx_adapter_endpoint_logs_endpoint (endpoint_id, created_at),
    CONSTRAINT fk_adapter_endpoint_logs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
    CONSTRAINT fk_adapter_endpoint_logs_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL,
    CONSTRAINT fk_adapter_endpoint_logs_endpoint FOREIGN KEY (endpoint_id) REFERENCES adapter_endpoints (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
