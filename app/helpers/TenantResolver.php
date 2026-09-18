<?php

declare(strict_types=1);

use Phalcon\Db\Adapter\Pdo\Mysql;

class TenantResolver
{
    private Mysql $db;

    public function __construct(Mysql $db)
    {
        $this->db = $db;
    }

    // =========================================================
    // Tenant-level resolution
    // =========================================================

    public function resolveTenantBySlug(string $slug): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, name, slug, status, plan, billing_email, created_at FROM tenants WHERE slug = :slug LIMIT 1",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            ['slug' => $slug]
        ) ?: null;
    }

    public function userHasTenantAccess(int $adminUserId, int $tenantId): bool
    {
        $row = $this->db->fetchOne(
            "SELECT 1 FROM tenant_user_map WHERE admin_user_id = :admin_user_id AND tenant_id = :tenant_id LIMIT 1",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            [
                'admin_user_id' => $adminUserId,
                'tenant_id' => $tenantId,
            ]
        );

        return !empty($row);
    }

    public function userIsTenantAdmin(int $adminUserId, int $tenantId): bool
    {
        $row = $this->db->fetchOne(
            "SELECT 1 FROM tenant_user_map WHERE admin_user_id = :admin_user_id AND tenant_id = :tenant_id AND role = 'tenant_admin' LIMIT 1",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            [
                'admin_user_id' => $adminUserId,
                'tenant_id' => $tenantId,
            ]
        );

        return !empty($row);
    }

    public function findTenantsForUser(int $adminUserId, bool $activeOnly = true): array
    {
        return $this->db->fetchAll(
            "SELECT t.id, t.name, t.slug, t.status, t.plan, t.billing_email " .
            "FROM tenants t " .
            "JOIN tenant_user_map tum ON tum.tenant_id = t.id " .
            "WHERE tum.admin_user_id = :admin_user_id " . ($activeOnly ? "AND t.status = 'active'" : "") .
            " ORDER BY t.name ASC",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            ['admin_user_id' => $adminUserId]
        );
    }

    public function findDefaultTenantForUser(int $adminUserId): ?array
    {
        $tenants = $this->findTenantsForUser($adminUserId, true);
        return $tenants[0] ?? null;
    }

    // =========================================================
    // Company-level resolution (now tenant-aware)
    // =========================================================

    /**
     * Resolve a company by slug within a specific tenant.
     * The slug is unique per-tenant, not globally.
     */
    public function resolveCompanyBySlug(string $slug, int $tenantId): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, tenant_id, name, slug, status FROM companies WHERE slug = :slug AND tenant_id = :tenant_id LIMIT 1",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            ['slug' => $slug, 'tenant_id' => $tenantId]
        ) ?: null;
    }

    /**
     * Legacy: resolve a company by slug without tenant context.
     * Used by routes that haven't been migrated yet.
     */
    public function resolveBySlug(string $slug): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, tenant_id, name, slug, status FROM companies WHERE slug = :slug LIMIT 1",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            ['slug' => $slug]
        ) ?: null;
    }

    public function userHasAccess(int $adminUserId, int $companyId): bool
    {
        $row = $this->db->fetchOne(
            "SELECT 1 FROM company_user_map WHERE admin_user_id = :admin_user_id AND company_id = :company_id LIMIT 1",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            [
                'admin_user_id' => $adminUserId,
                'company_id' => $companyId,
            ]
        );

        return !empty($row);
    }

    /**
     * Two-tier access check: a user has access to a company if:
     * 1. They have a tenant_user_map entry for the company's tenant (tenant-level access), OR
     * 2. They have a company_user_map entry for this specific company (company-level access)
     */
    public function userHasCompanyAccess(int $adminUserId, int $companyId): bool
    {
        // Check direct company-level access
        if ($this->userHasAccess($adminUserId, $companyId)) {
            return true;
        }

        // Check tenant-level access (user has access to the tenant that owns this company)
        $row = $this->db->fetchOne(
            "SELECT 1 FROM tenant_user_map tum " .
            "JOIN companies c ON c.tenant_id = tum.tenant_id " .
            "WHERE tum.admin_user_id = :admin_user_id AND c.id = :company_id LIMIT 1",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            [
                'admin_user_id' => $adminUserId,
                'company_id' => $companyId,
            ]
        );

        return !empty($row);
    }

    public function findCompaniesForUser(int $adminUserId, bool $activeOnly = true): array
    {
        return $this->db->fetchAll(
            "SELECT c.id, c.tenant_id, c.name, c.slug, c.status, t.slug AS tenant_slug, t.name AS tenant_name " .
            "FROM companies c " .
            "LEFT JOIN tenants t ON t.id = c.tenant_id " .
            "WHERE (c.id IN (SELECT company_id FROM company_user_map WHERE admin_user_id = :admin_user_id1) " .
            "       OR c.tenant_id IN (SELECT tenant_id FROM tenant_user_map WHERE admin_user_id = :admin_user_id2)) " .
            ($activeOnly ? "AND c.status = 'active' " : "") .
            "ORDER BY c.name ASC",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            [
                'admin_user_id1' => $adminUserId,
                'admin_user_id2' => $adminUserId,
            ]
        );
    }

    /**
     * Find companies under a specific tenant that the user has access to.
     */
    public function findCompaniesForUserInTenant(int $adminUserId, int $tenantId, bool $activeOnly = true): array
    {
        return $this->db->fetchAll(
            "SELECT c.id, c.tenant_id, c.name, c.slug, c.status " .
            "FROM companies c " .
            "WHERE c.tenant_id = :tenant_id " .
            "AND (c.id IN (SELECT company_id FROM company_user_map WHERE admin_user_id = :admin_user_id1) " .
            "     OR :tenant_id2 IN (SELECT tenant_id FROM tenant_user_map WHERE admin_user_id = :admin_user_id2)) " .
            ($activeOnly ? "AND c.status = 'active' " : "") .
            "ORDER BY c.name ASC",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            [
                'tenant_id' => $tenantId,
                'admin_user_id1' => $adminUserId,
                'tenant_id2' => $tenantId,
                'admin_user_id2' => $adminUserId,
            ]
        );
    }

    public function findDefaultCompanyForUser(int $adminUserId): ?array
    {
        $companies = $this->findCompaniesForUser($adminUserId, true);
        return $companies[0] ?? null;
    }

    /**
     * Find the user's default company within a specific tenant.
     *
     * If the user has a default_company_id set on admin_users and that
     * company belongs to the given tenant (and the user still has access),
     * return it. Otherwise fall back to the first accessible company in
     * the tenant (alphabetical order).
     */
    public function findDefaultCompanyForUserInTenant(int $adminUserId, int $tenantId): ?array
    {
        $companies = $this->findCompaniesForUserInTenant($adminUserId, $tenantId, false);
        if (empty($companies)) {
            return null;
        }

        $defaultCompanyId = $this->db->fetchOne(
            "SELECT default_company_id FROM admin_users WHERE id = :id LIMIT 1",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => $adminUserId]
        );
        $defaultCompanyId = $defaultCompanyId ? (int)$defaultCompanyId['default_company_id'] : 0;

        if ($defaultCompanyId > 0) {
            foreach ($companies as $company) {
                if ((int)$company['id'] === $defaultCompanyId && $company['status'] === 'active') {
                    return $company;
                }
            }
        }

        // Fall back to first active company
        foreach ($companies as $company) {
            if ($company['status'] === 'active') {
                return $company;
            }
        }

        return $companies[0];
    }
}
