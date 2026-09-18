-- Tenant System: create tenants table, tenant_user_map table, add tenant_id to companies.
-- This migration is idempotent and safe to re-run.
-- Backfill strategy: single shared "Default" tenant; all existing companies assigned to it.

-- =========================================================
-- 1. Create the tenants table
-- =========================================================
SET @tenants_exists = (SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE()
    AND table_name = 'tenants');

SET @create_tenants = IF(@tenants_exists = 0,
    'CREATE TABLE `tenants` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL,
        `status` ENUM(''active'',''suspended'') NOT NULL DEFAULT ''active'',
        `plan` VARCHAR(50) NULL DEFAULT NULL,
        `billing_email` VARCHAR(255) NULL DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_tenants_slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    'SELECT "tenants table already exists" AS message');
PREPARE stmt FROM @create_tenants;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 2. Create the tenant_user_map table (tenant-level IAM)
-- =========================================================
SET @tum_exists = (SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE()
    AND table_name = 'tenant_user_map');

SET @create_tum = IF(@tum_exists = 0,
    'CREATE TABLE `tenant_user_map` (
        `admin_user_id` INT NOT NULL,
        `tenant_id` INT NOT NULL,
        `role` VARCHAR(50) NOT NULL DEFAULT ''tenant_member'',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`admin_user_id`, `tenant_id`),
        KEY `idx_tum_tenant` (`tenant_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    'SELECT "tenant_user_map table already exists" AS message');
PREPARE stmt FROM @create_tum;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 3. Add tenant_id column to companies (nullable first for backfill)
-- =========================================================
SET @has_tenant_col = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'companies'
    AND column_name = 'tenant_id');

SET @add_tenant_col = IF(@has_tenant_col = 0,
    'ALTER TABLE `companies` ADD COLUMN `tenant_id` INT NULL AFTER `id`',
    'SELECT "companies.tenant_id already exists" AS message');
PREPARE stmt FROM @add_tenant_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 4. Backfill: create the single shared "Default" tenant
-- =========================================================
INSERT IGNORE INTO `tenants` (`name`, `slug`, `status`, `plan`, `billing_email`, `created_at`)
VALUES ('Default', 'default', 'active', 'standard', NULL, NOW());

-- =========================================================
-- 5. Backfill: assign all existing companies with NULL tenant_id to the Default tenant
-- =========================================================
UPDATE `companies`
SET `tenant_id` = (SELECT `id` FROM `tenants` WHERE `slug` = 'default' LIMIT 1)
WHERE `tenant_id` IS NULL;

-- =========================================================
-- 6. Make tenant_id NOT NULL, add FK + index, and change slug uniqueness to per-tenant
-- =========================================================
SET @has_tenant_fk = (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
    AND table_name = 'companies'
    AND constraint_type = 'FOREIGN KEY'
    AND constraint_name = 'fk_companies_tenant');

SET @add_tenant_fk = IF(@has_tenant_fk = 0,
    'ALTER TABLE `companies`
        MODIFY COLUMN `tenant_id` INT NOT NULL,
        ADD KEY `idx_companies_tenant` (`tenant_id`),
        ADD CONSTRAINT `fk_companies_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE',
    'SELECT "companies tenant FK already exists" AS message');
PREPARE stmt FROM @add_tenant_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Change slug uniqueness from global to per-tenant (tenant_id, slug)
SET @has_global_slug_key = (SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'companies'
    AND index_name = 'slug'
    AND non_unique = 0);

SET @drop_global_slug = IF(@has_global_slug_key > 0,
    'ALTER TABLE `companies` DROP INDEX `slug`',
    'SELECT "slug unique key already dropped" AS message');
PREPARE stmt FROM @drop_global_slug;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_tenant_slug_key = (SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'companies'
    AND index_name = 'uk_companies_tenant_slug'
    AND non_unique = 0);

SET @add_tenant_slug = IF(@has_tenant_slug_key = 0,
    'ALTER TABLE `companies` ADD UNIQUE KEY `uk_companies_tenant_slug` (`tenant_id`, `slug`)',
    'SELECT "uk_companies_tenant_slug already exists" AS message');
PREPARE stmt FROM @add_tenant_slug;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 7. Backfill tenant_user_map from company_user_map
--    Every user who has access to a company gets tenant_member access
--    to that company's tenant. Users with Super Admin role get tenant_admin.
-- =========================================================
INSERT IGNORE INTO `tenant_user_map` (`admin_user_id`, `tenant_id`, `role`, `created_at`)
SELECT DISTINCT
    cum.admin_user_id,
    c.tenant_id,
    CASE WHEN cum.role = 'Super Admin' THEN 'tenant_admin' ELSE 'tenant_member' END,
    NOW()
FROM `company_user_map` cum
JOIN `companies` c ON c.id = cum.company_id;

-- =========================================================
-- 8. Also ensure Super Admin users (from admin_users) get tenant_admin on the Default tenant
-- =========================================================
INSERT IGNORE INTO `tenant_user_map` (`admin_user_id`, `tenant_id`, `role`, `created_at`)
SELECT
    au.id,
    (SELECT `id` FROM `tenants` WHERE `slug` = 'default' LIMIT 1),
    'tenant_admin',
    NOW()
FROM `admin_users` au
WHERE au.role = 'Super Admin';
