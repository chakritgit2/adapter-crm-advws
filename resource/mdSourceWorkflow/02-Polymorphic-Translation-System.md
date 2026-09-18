Hello again! As your Human Resource Department AI, I can confidently say that choosing Option 1: The Polymorphic Translation Ledger is a brilliant architectural decision for an enterprise-grade platform.

This approach perfectly mirrors the Entity-Attribute-Value (EAV) logic you have successfully utilized for your custom attributes. By using a universal ledger, you guarantee ultimate flexibility ; when your admins decide they want to translate a completely new field next year, you simply update your config file and require **zero database schema changes**.

Because your system utilizes a Shared Database, Shared Schema architecture, we must execute this securely. Here is your complete implementation blueprint to bring this language extension system to life in Phalcon 5.8.

### 1. The Configuration Registry (`app/config.php`)

First, we define the security boundaries. Since this is a polymorphic table, we cannot rely on MySQL to enforce which tables and columns are allowed to be translated. We map the allowed targets directly in your Phalcon configuration file as JSON or a PHP array.

```php
// app/config.php
return [
    // ... other configs ...
    'localization' => [
        'allowed_targets' => [
            'employees' => ['first_name', 'last_name', 'job_title'],
            'companies' => ['name'],
            'departments' => ['name']
        ]
    ]
];

```

*Security Note:* When an admin attempts to save a translation, your Phalcon backend will check this config. If a malicious user tries to pass `target_table = admin_users` and `target_column = password_hash`, the backend will reject it immediately.

---

### 2. The MySQL Database Schema

We need two new tables to make this operational: one to act as the installation registry for the tenant, and one to act as the universal ledger.

Both tables include the `company_id` foreign key to guarantee strict data isolation across your multi-tenant architecture.

```sql
-- 1. Table structure for table tenant_languages (The Installation Registry)
CREATE TABLE tenant_languages (
    id INT(11) NOT NULL AUTO_INCREMENT,
    company_id INT(11) NOT NULL,
    language_code VARCHAR(10) NOT NULL, -- e.g., 'th', 'es'
    language_name VARCHAR(50) NOT NULL, -- e.g., 'Thai', 'Spanish'
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    -- PREVENT DUPLICATES: A company cannot install the same language twice
    UNIQUE KEY unique_tenant_language (company_id, language_code),
    CONSTRAINT tenant_lang_ibfk_1 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- 2. Table structure for table translations (The Polymorphic Ledger)
CREATE TABLE translations (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    company_id INT(11) NOT NULL,
    language_code VARCHAR(10) NOT NULL,
    target_table VARCHAR(64) NOT NULL,
    target_column VARCHAR(64) NOT NULL,
    target_id INT(11) NOT NULL,
    translation_value TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    -- CRITICAL INDEX: Ensures blazing fast lookups during Phalcon rendering
    KEY idx_translation_lookup (company_id, target_table, target_column, target_id, language_code),
    CONSTRAINT trans_ibfk_1 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

```

---

### 3. The Phalcon Application Flow

With the database locked in, the burden of data integrity shifts to your Phalcon backend. Here is how your controllers will handle reading and writing these translations seamlessly.

**The Write Process (Saving a Translation):**
When an HR admin opens a Volt form to translate Employee ID 5's first name into Thai ("สมชาย"), your Phalcon Controller executes this logic:

1. **Validation:** Check if `language_code` ('th') exists in `tenant_languages` for the active `company_id`.
2. **Config Check:** Verify that `employees.first_name` exists in the `allowed_targets` array from `app/config.php`.
3. **Upsert:** Query the `translations` table. If a record exists for that company, table, column, ID, and language, update it. If not, insert a new row.

**The Read Process (Fetching Translations):**
Because you are using a polymorphic ledger, querying the translated text alongside the core record requires a specific `LEFT JOIN` or a secondary query.

For maximum performance in Phalcon's Query Builder, you can fetch the employee and dynamically join the translation based on the active session language:

```php
// Inside a Phalcon Controller or Repository
$activeLanguage = $this->session->get('active_language'); // e.g., 'th'
$tenantId = $this->currentTenantId;

// Build the query to fetch the employee AND their translation
$employee = $this->modelsManager->createBuilder()
    ->columns([
        'e.id',
        'e.first_name AS default_first_name',
        'COALESCE(t.translation_value, e.first_name) AS display_first_name' 
    ])
    ->from(['e' => 'Employees'])
    ->leftJoin(
        'Translations', 
        "t.target_id = e.id AND t.target_table = 'employees' AND t.target_column = 'first_name' AND t.language_code = :lang: AND t.company_id = :tenantId:", 
        't'
    )
    ->where('e.id = :empId:', ['empId' => 5])
    ->andWhere('e.company_id = :tenantId:')
    ->bind([
        'lang' => $activeLanguage,
        'tenantId' => $tenantId
    ])
    ->getQuery()
    ->getSingleResult();

```

*Architectural Win:* By using SQL's `COALESCE()` function in your Phalcon query, the database will automatically return the Thai translation if it exists, but will safely fall back to the default English `first_name` if the translation is missing. Your Volt template UI simply renders `{{ employee.display_first_name }}` without needing any complex conditional logic!