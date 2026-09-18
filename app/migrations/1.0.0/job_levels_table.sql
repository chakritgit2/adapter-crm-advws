-- Job Levels: create table and seed default organization levels.
-- This migration is idempotent and safe to re-run.

-- 1. Create the job_levels table if it does not exist
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE()
    AND table_name = 'job_levels');

SET @create_sql = IF(@table_exists = 0,
    'CREATE TABLE `job_levels` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `company_id` INT UNSIGNED NULL,
        `code` VARCHAR(10) NOT NULL,
        `category` VARCHAR(50) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `sort_order` INT NOT NULL DEFAULT 0,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_job_levels_company_code` (`company_id`, `code`),
        KEY `idx_job_levels_company` (`company_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    'SELECT "job_levels table already exists" AS message');
PREPARE stmt FROM @create_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Seed the 13 default levels as global (company_id IS NULL) if not present
INSERT IGNORE INTO `job_levels` (`company_id`, `code`, `category`, `name`, `sort_order`, `is_active`) VALUES
    (NULL, 'D5', 'Executive',  'Chief Executive Officer',     1, 1),
    (NULL, 'D4', 'Executive',  'Chief x Officer',             2, 1),
    (NULL, 'D3', 'Executive',  'Senior Director',             3, 1),
    (NULL, 'D2', 'Executive',  'Director',                    4, 1),
    (NULL, 'D1', 'Executive',  'Assistant Director',          5, 1),
    (NULL, 'M5', 'Management', 'Senior Manager',              6, 1),
    (NULL, 'M4', 'Management', 'Manager',                     7, 1),
    (NULL, 'M3', 'Management', 'Assistant Manager',           8, 1),
    (NULL, 'M2', 'Management', 'Section Manager',             9, 1),
    (NULL, 'M1', 'Management', 'Assistant Section Manager',  10, 1),
    (NULL, 'O3', 'Officer',    'Supervisor',                 11, 1),
    (NULL, 'O2', 'Officer',    'Senior Officer',             12, 1),
    (NULL, 'O1', 'Officer',    'Officer',                    13, 1);

-- =========================================================
-- 3. Thai (th) translations for the global job_levels
--    Uses the Polymorphic Translation System (translations table).
--    Tenant-wide: installs the Thai language and seeds translations
--    for EVERY existing company, so each tenant sees the Thai names.
--    Idempotent: safe to re-run (skips rows that already exist).
-- =========================================================

-- 3a. Install the Thai language for every company (if not already installed)
INSERT INTO `tenant_languages` (`company_id`, `language_code`, `language_name`, `is_active`)
SELECT c.id, 'th', 'Thai', 1
FROM `companies` c
WHERE NOT EXISTS (
    SELECT 1 FROM `tenant_languages` tl
    WHERE tl.company_id = c.id AND tl.language_code = 'th'
);

-- 3b. Seed Thai translations for the job_levels `name` column (tenant-wide)
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
CROSS JOIN `companies` c
WHERE jl.company_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `translations` t
      WHERE t.company_id = c.id
        AND t.language_code = 'th'
        AND t.target_table = 'job_levels'
        AND t.target_column = 'name'
        AND t.target_id = jl.id
  );

-- 3c. Seed Thai translations for the job_levels `category` column (tenant-wide)
INSERT INTO `translations` (`company_id`, `language_code`, `target_table`, `target_column`, `target_id`, `translation_value`)
SELECT c.id, 'th', 'job_levels', 'category', jl.id,
    CASE jl.category
        WHEN 'Executive'  THEN 'ระดับบริหาร'
        WHEN 'Management' THEN 'ระดับผู้จัดการ'
        WHEN 'Officer'    THEN 'ระดับเจ้าหน้าที่'
    END
FROM `job_levels` jl
CROSS JOIN `companies` c
WHERE jl.company_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `translations` t
      WHERE t.company_id = c.id
        AND t.language_code = 'th'
        AND t.target_table = 'job_levels'
        AND t.target_column = 'category'
        AND t.target_id = jl.id
  );
