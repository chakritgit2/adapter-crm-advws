<?php

declare(strict_types=1);

use Phalcon\Db\Enum as DbEnum;
use Phalcon\Di\Injectable;

class TranslationService extends Injectable
{
    protected Phalcon\Db\Adapter\AdapterInterface $db;

    public function __construct(Phalcon\Db\Adapter\AdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Get all active languages installed for a tenant.
     */
    public function getInstalledLanguages(int $companyId): array
    {
        return $this->db->fetchAll(
            "SELECT id, language_code, language_name, is_active, created_at FROM tenant_languages WHERE company_id = :company_id ORDER BY language_name ASC",
            DbEnum::FETCH_ASSOC,
            ['company_id' => $companyId]
        );
    }

    /**
     * Get all languages (including inactive) for a tenant.
     */
    public function getAllLanguages(int $companyId): array
    {
        return $this->db->fetchAll(
            "SELECT id, language_code, language_name, is_active, created_at FROM tenant_languages WHERE company_id = :company_id ORDER BY language_name ASC",
            DbEnum::FETCH_ASSOC,
            ['company_id' => $companyId]
        );
    }

    /**
     * Install a new language for a tenant.
     */
    public function installLanguage(int $companyId, string $code, string $name): bool
    {
        $code = trim($code);
        $name = trim($name);

        if (empty($code) || empty($name)) {
            return false;
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM tenant_languages WHERE company_id = :company_id AND language_code = :code LIMIT 1",
            DbEnum::FETCH_ASSOC,
            ['company_id' => $companyId, 'code' => $code]
        );

        if ($existing) {
            return false;
        }

        $this->db->execute(
            "INSERT INTO tenant_languages (company_id, language_code, language_name, is_active) VALUES (:company_id, :code, :name, 1)",
            ['company_id' => $companyId, 'code' => $code, 'name' => $name]
        );

        return true;
    }

    /**
     * Uninstall a language and delete all its translations for the tenant.
     */
    public function uninstallLanguage(int $companyId, int $languageId): bool
    {
        $lang = $this->db->fetchOne(
            "SELECT language_code FROM tenant_languages WHERE id = :id AND company_id = :company_id LIMIT 1",
            DbEnum::FETCH_ASSOC,
            ['id' => $languageId, 'company_id' => $companyId]
        );

        if (!$lang) {
            return false;
        }

        $this->db->begin();

        try {
            $this->db->execute(
                "DELETE FROM translations WHERE company_id = :company_id AND language_code = :code",
                ['company_id' => $companyId, 'code' => $lang['language_code']]
            );

            $this->db->execute(
                "DELETE FROM tenant_languages WHERE id = :id AND company_id = :company_id",
                ['id' => $languageId, 'company_id' => $companyId]
            );

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Check if a target table+column is in the allowed_targets config.
     */
    public function isTargetAllowed(string $table, string $column): bool
    {
        $config = $this->getDI()->get('config');
        $allowed = $config->path('localization.allowed_targets', null);

        if (!$allowed) {
            return false;
        }

        $allowedArray = $allowed->toArray();
        return isset($allowedArray[$table]) && in_array($column, $allowedArray[$table], true);
    }

    /**
     * Check if a language is installed and active for a tenant.
     */
    public function isLanguageInstalled(int $companyId, string $code): bool
    {
        $row = $this->db->fetchOne(
            "SELECT id FROM tenant_languages WHERE company_id = :company_id AND language_code = :code AND is_active = 1 LIMIT 1",
            DbEnum::FETCH_ASSOC,
            ['company_id' => $companyId, 'code' => $code]
        );

        return (bool)$row;
    }

    /**
     * Upsert a translation record.
     */
    public function upsertTranslation(int $companyId, string $lang, string $table, string $column, int $targetId, string $value): bool
    {
        if (!$this->isTargetAllowed($table, $column)) {
            return false;
        }

        if (!$this->isLanguageInstalled($companyId, $lang)) {
            return false;
        }

        $value = trim($value);
        if ($value === '') {
            return false;
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM translations WHERE company_id = :company_id AND language_code = :lang AND target_table = :table AND target_column = :column AND target_id = :target_id LIMIT 1",
            DbEnum::FETCH_ASSOC,
            [
                'company_id' => $companyId,
                'lang' => $lang,
                'table' => $table,
                'column' => $column,
                'target_id' => $targetId,
            ]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE translations SET translation_value = :value WHERE id = :id",
                ['value' => $value, 'id' => $existing['id']]
            );
        } else {
            $this->db->execute(
                "INSERT INTO translations (company_id, language_code, target_table, target_column, target_id, translation_value) VALUES (:company_id, :lang, :table, :column, :target_id, :value)",
                [
                    'company_id' => $companyId,
                    'lang' => $lang,
                    'table' => $table,
                    'column' => $column,
                    'target_id' => $targetId,
                    'value' => $value,
                ]
            );
        }

        return true;
    }

    /**
     * Delete a single translation row (with tenant isolation).
     */
    public function deleteTranslation(int $companyId, int $translationId): bool
    {
        $row = $this->db->fetchOne(
            "SELECT id FROM translations WHERE id = :id AND company_id = :company_id LIMIT 1",
            DbEnum::FETCH_ASSOC,
            ['id' => $translationId, 'company_id' => $companyId]
        );

        if (!$row) {
            return false;
        }

        $this->db->execute(
            "DELETE FROM translations WHERE id = :id AND company_id = :company_id",
            ['id' => $translationId, 'company_id' => $companyId]
        );

        return true;
    }

    /**
     * Get all translations for a specific record, grouped by language_code -> column -> value.
     */
    public function getTranslationsForRecord(int $companyId, string $table, int $targetId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT id, language_code, target_column, translation_value FROM translations WHERE company_id = :company_id AND target_table = :table AND target_id = :target_id ORDER BY language_code, target_column",
            DbEnum::FETCH_ASSOC,
            ['company_id' => $companyId, 'table' => $table, 'target_id' => $targetId]
        );

        $result = [];
        foreach ($rows as $row) {
            $result[$row['language_code']][$row['target_column']] = [
                'id' => (int)$row['id'],
                'value' => $row['translation_value'],
            ];
        }

        return $result;
    }

    /**
     * Get a single translated value with fallback.
     */
    public function getTranslatedValue(int $companyId, string $lang, string $table, string $column, int $targetId, string $fallback): string
    {
        $row = $this->db->fetchOne(
            "SELECT translation_value FROM translations WHERE company_id = :company_id AND language_code = :lang AND target_table = :table AND target_column = :column AND target_id = :target_id LIMIT 1",
            DbEnum::FETCH_ASSOC,
            [
                'company_id' => $companyId,
                'lang' => $lang,
                'table' => $table,
                'column' => $column,
                'target_id' => $targetId,
            ]
        );

        return $row ? $row['translation_value'] : $fallback;
    }

    /**
     * Get the allowed targets config as a plain array.
     */
    public function getAllowedTargets(): array
    {
        $config = $this->getDI()->get('config');
        $allowed = $config->path('localization.allowed_targets', null);

        if (!$allowed) {
            return [];
        }

        return $allowed->toArray();
    }
}
