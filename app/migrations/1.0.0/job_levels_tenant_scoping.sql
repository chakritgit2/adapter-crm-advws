-- Job Levels: convert from global/company two-tier to tenant/company two-tier scoping.
-- Hierarchy after this migration:
--   * Tenant-level rows:  tenant_id = X, company_id = NULL  (shared by all companies in the tenant)
--   * Company-level rows: tenant_id = X, company_id = Y     (override for a single company)
-- The previous "global" tier (company_id IS NULL, no tenant_id) is removed; each existing
-- tenant gets its own copy of the 13 default levels at tenant level.
--
-- Prerequisites: job_levels_table.sql and tenant_system.sql must have been applied.
-- This migration is idempotent and safe to re-run.

-- =========================================================
-- 1. Add tenant_id column to job_levels (nullable first for backfill)
-- =========================================================
SET @has_tenant_col = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'job_levels'
    AND column_name = 'tenant_id');

SET @add_tenant_col = IF(@has_tenant_col = 0,
    'ALTER TABLE `job_levels` ADD COLUMN `tenant_id` INT NULL AFTER `id`',
    'SELECT "job_levels.tenant_id already exists" AS message');
PREPARE stmt FROM @add_tenant_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 2. Backfill company-specific rows: tenant_id from the company's tenant
-- =========================================================
UPDATE `job_levels` jl
JOIN `companies` c ON c.id = jl.company_id
SET jl.tenant_id = c.tenant_id
WHERE jl.company_id IS NOT NULL AND jl.tenant_id IS NULL;

-- =========================================================
-- 3. Backfill the original global rows (company_id IS NULL): assign them to
--    the Default tenant. This preserves their ids, so the Thai translations
--    already seeded for Default-tenant companies (pointing at these ids)
--    remain valid.
-- =========================================================
UPDATE `job_levels`
SET `tenant_id` = (SELECT `id` FROM `tenants` WHERE `slug` = 'default' LIMIT 1)
WHERE `company_id` IS NULL AND `tenant_id` IS NULL;

-- Safety net: any row still missing tenant_id (e.g. orphaned company_id) -> Default tenant
UPDATE `job_levels`
SET `tenant_id` = (SELECT `id` FROM `tenants` WHERE `slug` = 'default' LIMIT 1)
WHERE `tenant_id` IS NULL;

-- =========================================================
-- 4. Seed the 13 default levels for every OTHER tenant (non-Default)
--    at tenant level (tenant_id set, company_id NULL).
--    Idempotent: skips (tenant, code) combinations that already exist.
--    (The old unique key allowed duplicate NULL company_id rows, so the
--     NOT EXISTS guard is what enforces idempotency here.)
-- =========================================================
INSERT IGNORE INTO `job_levels` (`tenant_id`, `company_id`, `code`, `category`, `name`, `sort_order`, `is_active`)
SELECT t.id, NULL, d.code, d.category, d.name, d.sort_order, 1
FROM `tenants` t
CROSS JOIN (
    SELECT 'D5' AS code, 'Executive'  AS category, 'Chief Executive Officer'    AS name, 1  AS sort_order
    UNION ALL SELECT 'D4', 'Executive',  'Chief x Officer',             2
    UNION ALL SELECT 'D3', 'Executive',  'Senior Director',             3
    UNION ALL SELECT 'D2', 'Executive',  'Director',                    4
    UNION ALL SELECT 'D1', 'Executive',  'Assistant Director',          5
    UNION ALL SELECT 'M5', 'Management', 'Senior Manager',              6
    UNION ALL SELECT 'M4', 'Management', 'Manager',                     7
    UNION ALL SELECT 'M3', 'Management', 'Assistant Manager',           8
    UNION ALL SELECT 'M2', 'Management', 'Section Manager',             9
    UNION ALL SELECT 'M1', 'Management', 'Assistant Section Manager',  10
    UNION ALL SELECT 'O3', 'Officer',    'Supervisor',                 11
    UNION ALL SELECT 'O2', 'Officer',    'Senior Officer',             12
    UNION ALL SELECT 'O1', 'Officer',    'Officer',                    13
) d
WHERE t.slug != 'default'
  AND NOT EXISTS (
      SELECT 1 FROM `job_levels` jl
      WHERE jl.tenant_id = t.id AND jl.company_id IS NULL AND jl.code = d.code
  );

-- =========================================================
-- 5. Re-seed Thai translations for non-Default tenants' companies.
--    The original job_levels_table.sql seeded translations for EVERY company
--    pointing at the (then-global) job_level ids, which are now Default-tenant
--    rows. Non-Default tenants' companies need translations pointing at THEIR
--    own tenant's job_level ids. Default-tenant translations are left untouched
--    (they already reference the correct, preserved ids).
-- =========================================================

-- 5a. Remove stale cross-tenant translations for non-Default tenant companies
DELETE t FROM `translations` t
JOIN `companies` c ON c.id = t.company_id
JOIN `tenants` tn ON tn.id = c.tenant_id
WHERE t.target_table = 'job_levels'
  AND tn.slug != 'default';

-- 5b. Install Thai language for non-Default tenant companies (if missing)
INSERT INTO `tenant_languages` (`company_id`, `language_code`, `language_name`, `is_active`)
SELECT c.id, 'th', 'Thai', 1
FROM `companies` c
JOIN `tenants` tn ON tn.id = c.tenant_id
WHERE tn.slug != 'default'
  AND NOT EXISTS (
      SELECT 1 FROM `tenant_languages` tl
      WHERE tl.company_id = c.id AND tl.language_code = 'th'
  );

-- 5c. Seed Thai translations for the job_levels `name` column (non-Default tenants)
INSERT INTO `translations` (`company_id`, `language_code`, `target_table`, `target_column`, `target_id`, `translation_value`)
SELECT c.id, 'th', 'job_levels', 'name', jl.id,
    CASE jl.code
        WHEN 'D5' THEN 'ประธานเจ้าหน้าที่บริหาร'
        WHEN 'D4' THEN 'ประธานเจ้าหน้าที่ฝ่าย'
        WHEN 'D3' THEN 'ผู้อำนวยการฝ่ายอาวุโส'
        WHEN 'D2' THEN 'ผู้อำนวยการฝ่าย'
        WHEN 'D1' THEN 'ผู้ช่วยผู้อำนวยการฝ่าย'
        WHEN 'M5' THEN 'ผู้จัดการฝ่ายอาวุโส'
        WHEN 'M4' THEN 'ผู้จัดการฝ่าย'
        WHEN 'M3' THEN 'ผู้ช่วยผู้จัดการฝ่าย'
        WHEN 'M2' THEN 'ผู้จัดการแผนก'
        WHEN 'M1' THEN 'ผู้ช่วยผู้จัดการแผนก'
        WHEN 'O3' THEN 'หัวหน้างาน'
        WHEN 'O2' THEN 'เจ้าหน้าที่อาวุโส'
        WHEN 'O1' THEN 'เจ้าหน้าที่'
    END
FROM `job_levels` jl
JOIN `companies` c ON c.tenant_id = jl.tenant_id
JOIN `tenants` tn ON tn.id = jl.tenant_id
WHERE jl.company_id IS NULL
  AND tn.slug != 'default'
  AND NOT EXISTS (
      SELECT 1 FROM `translations` t
      WHERE t.company_id = c.id
        AND t.language_code = 'th'
        AND t.target_table = 'job_levels'
        AND t.target_column = 'name'
        AND t.target_id = jl.id
  );

-- 5d. Seed Thai translations for the job_levels `category` column (non-Default tenants)
INSERT INTO `translations` (`company_id`, `language_code`, `target_table`, `target_column`, `target_id`, `translation_value`)
SELECT c.id, 'th', 'job_levels', 'category', jl.id,
    CASE jl.category
        WHEN 'Executive'  THEN 'ระดับบริหาร'
        WHEN 'Management' THEN 'ระดับผู้จัดการ'
        WHEN 'Officer'    THEN 'ระดับเจ้าหน้าที่'
    END
FROM `job_levels` jl
JOIN `companies` c ON c.tenant_id = jl.tenant_id
JOIN `tenants` tn ON tn.id = jl.tenant_id
WHERE jl.company_id IS NULL
  AND tn.slug != 'default'
  AND NOT EXISTS (
      SELECT 1 FROM `translations` t
      WHERE t.company_id = c.id
        AND t.language_code = 'th'
        AND t.target_table = 'job_levels'
        AND t.target_column = 'category'
        AND t.target_id = jl.id
  );

-- =========================================================
-- 6. Make tenant_id NOT NULL, add FK + composite index
-- =========================================================
SET @has_tenant_fk = (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
    AND table_name = 'job_levels'
    AND constraint_type = 'FOREIGN KEY'
    AND constraint_name = 'fk_job_levels_tenant');

SET @add_tenant_fk = IF(@has_tenant_fk = 0,
    'ALTER TABLE `job_levels` MODIFY COLUMN `tenant_id` INT NOT NULL, ADD KEY `idx_job_levels_tenant_company` (`tenant_id`, `company_id`), ADD CONSTRAINT `fk_job_levels_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE',
    'SELECT "job_levels tenant FK already exists" AS message');
PREPARE stmt FROM @add_tenant_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 7. Replace the unique key to enforce per-(tenant, scope, code) uniqueness.
--    MySQL treats multiple NULLs as distinct, so a plain UNIQUE(tenant_id, company_id, code)
--    would allow duplicate tenant-level rows (company_id NULL). A generated column
--    maps NULL company_id to 0 so the unique key works for both tiers.
-- =========================================================
SET @has_scope_key = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'job_levels'
    AND column_name = 'company_scope_key');

SET @add_scope_key = IF(@has_scope_key = 0,
    'ALTER TABLE `job_levels` ADD COLUMN `company_scope_key` INT UNSIGNED AS (COALESCE(`company_id`, 0)) VIRTUAL',
    'SELECT "company_scope_key already exists" AS message');
PREPARE stmt FROM @add_scope_key;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_old_uk = (SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'job_levels'
    AND index_name = 'uk_job_levels_company_code'
    AND non_unique = 0);

SET @drop_old_uk = IF(@has_old_uk > 0,
    'ALTER TABLE `job_levels` DROP INDEX `uk_job_levels_company_code`',
    'SELECT "uk_job_levels_company_code already dropped" AS message');
PREPARE stmt FROM @drop_old_uk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_new_uk = (SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'job_levels'
    AND index_name = 'uk_job_levels_tenant_scope_code'
    AND non_unique = 0);

SET @add_new_uk = IF(@has_new_uk = 0,
    'ALTER TABLE `job_levels` ADD UNIQUE KEY `uk_job_levels_tenant_scope_code` (`tenant_id`, `company_scope_key`, `code`)',
    'SELECT "uk_job_levels_tenant_scope_code already exists" AS message');
PREPARE stmt FROM @add_new_uk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
