<?php

declare(strict_types=1);

use Phalcon\Db\Enum as DbEnum;
use Phalcon\Di\Injectable;

/**
 * DB-backed job-level lookup service.
 *
 * Reads from the `job_levels` table using a two-tier scoping model:
 *   * Tenant-level rows:  tenant_id = X, company_id = NULL  (shared by all companies in the tenant)
 *   * Company-level rows: tenant_id = X, company_id = Y     (override for a single company)
 * Company-level rows override tenant-level rows with the same code.
 * Falls back to the hardcoded JobLevels helper when the table does not exist or is empty.
 */
class JobLevelsService extends Injectable
{
    protected Phalcon\Db\Adapter\AdapterInterface $db;
    protected int $tenantId;
    protected int $companyId;
    protected ?array $cache = null;

    public function __construct(Phalcon\Db\Adapter\AdapterInterface $db, int $tenantId, int $companyId)
    {
        $this->db = $db;
        $this->tenantId = $tenantId;
        $this->companyId = $companyId;
    }

    /**
     * Load all active levels for this tenant (tenant-level + company-level),
     * ordered by sort_order. Company-level rows override tenant-level rows
     * with the same code. Falls back to hardcoded defaults on error.
     *
     * @return list<array{id: ?int, code: string, category: string, name: string, sort_order: int, is_company_level: bool}>
     */
    protected function load(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        try {
            $rows = $this->db->fetchAll(
                "SELECT id, company_id, code, category, name, sort_order, is_active, can_approve_leave " .
                "FROM job_levels " .
                "WHERE tenant_id = :tenant_id AND (company_id = :company_id OR company_id IS NULL) AND is_active = 1 " .
                "ORDER BY sort_order ASC, code ASC",
                DbEnum::FETCH_ASSOC,
                ['tenant_id' => $this->tenantId, 'company_id' => $this->companyId]
            );

            // Company-level rows override tenant-level rows with the same code
            $byCode = [];
            foreach ($rows as $row) {
                $code = $row['code'];
                if (isset($byCode[$code]) && $byCode[$code]['is_company_level'] === true) {
                    continue; // keep company-level over tenant-level
                }
                $byCode[$code] = [
                    'id'               => $row['id'] ? (int)$row['id'] : null,
                    'code'             => $row['code'],
                    'category'         => $row['category'],
                    'name'             => $row['name'],
                    'sort_order'       => (int)$row['sort_order'],
                    'is_company_level' => $row['company_id'] !== null,
                    'can_approve_leave' => !empty($row['can_approve_leave']),
                ];
            }

            $this->cache = array_values($byCode);
        } catch (\Throwable $e) {
            // Table doesn't exist yet — fall back to hardcoded defaults
            $this->cache = $this->hardcodedFallback();
        }

        return $this->cache;
    }

    /**
     * Build the fallback list from the JobLevels helper.
     *
     * @return list<array{id: ?int, code: string, category: string, name: string, sort_order: int, is_company_level: bool}>
     */
    protected function hardcodedFallback(): array
    {
        $list = [];
        $sortOrder = 0;
        foreach (JobLevels::all() as $lvl) {
            $sortOrder++;
            $list[] = [
                'id'               => null,
                'code'             => $lvl['code'],
                'category'         => $lvl['category'],
                'name'             => $lvl['name'],
                'sort_order'       => $sortOrder,
                'is_company_level' => false,
                'can_approve_leave' => in_array($lvl['code'], ['D5','D4','D3','D2','D1','M5','M4','M3','M2','M1','O3'], true)
                                  || in_array($lvl['category'], ['Executive','Management'], true),
            ];
        }
        return $list;
    }

    /**
     * All levels in rank order.
     *
     * @return list<array{code: string, category: string, name: string}>
     */
    public function all(): array
    {
        return array_map(function ($r) {
            return ['code' => $r['code'], 'category' => $r['category'], 'name' => $r['name']];
        }, $this->load());
    }

    /**
     * Levels grouped by category, ordered by rank.
     *
     * @return array<string, list<array{code: string, name: string}>>
     */
    public function grouped(): array
    {
        $grouped = [];
        foreach ($this->load() as $r) {
            $grouped[$r['category']][] = ['code' => $r['code'], 'name' => $r['name']];
        }
        return $grouped;
    }

    /**
     * Code-keyed map: code => [category, name].
     *
     * @return array<string, array{category: string, name: string}>
     */
    public function map(): array
    {
        $map = [];
        foreach ($this->load() as $r) {
            $map[$r['code']] = ['category' => $r['category'], 'name' => $r['name']];
        }
        return $map;
    }

    /**
     * All valid level codes.
     *
     * @return list<string>
     */
    public function codes(): array
    {
        return array_map(fn($r) => $r['code'], $this->load());
    }

    public function isValid(?string $code): bool
    {
        if ($code === null) {
            return false;
        }
        foreach ($this->load() as $r) {
            if ($r['code'] === $code) {
                return true;
            }
        }
        return false;
    }

    public function categoryOf(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }
        foreach ($this->load() as $r) {
            if ($r['code'] === $code) {
                return $r['category'];
            }
        }
        return null;
    }

    public function nameOf(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }
        foreach ($this->load() as $r) {
            if ($r['code'] === $code) {
                return $r['name'];
            }
        }
        return null;
    }

    /**
     * Whether the given job-level code is permitted to approve leave requests.
     * Returns false for unknown/null codes.
     */
    public function canApproveLeave(?string $code): bool
    {
        if ($code === null) {
            return false;
        }
        foreach ($this->load() as $r) {
            if ($r['code'] === $code) {
                return !empty($r['can_approve_leave']);
            }
        }
        return false;
    }

    /**
     * All job-level codes that are permitted to approve leave requests,
     * in rank order.
     *
     * @return list<string>
     */
    public function approverCodes(): array
    {
        $codes = [];
        foreach ($this->load() as $r) {
            if (!empty($r['can_approve_leave'])) {
                $codes[] = $r['code'];
            }
        }
        return $codes;
    }
}
