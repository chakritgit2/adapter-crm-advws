<?php

declare(strict_types=1);

use Phalcon\Db\Enum as DbEnum;
use Phalcon\Di\Injectable;

class OrgChartService extends Injectable
{
    protected Phalcon\Db\Adapter\AdapterInterface $db;

    public function __construct(Phalcon\Db\Adapter\AdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch all positions for a tenant with active employee assignments,
     * optionally applying translations for the given language.
     *
     * @return array<array<string, mixed>>
     */
    public function getPositions(int $companyId, ?string $activeLanguage, int $adminUserId): array
    {
        if ($activeLanguage && $activeLanguage !== 'en') {
            return $this->db->fetchAll(
                "SELECT p.id, p.parent_position_id, p.is_approved, p.job_level, " .
                "COALESCE(t_jt.translation_value, p.job_title) AS job_title, " .
                "COALESCE(t_dep.translation_value, p.department) AS department, " .
                "COALESCE(t_fn.translation_value, e.first_name) AS first_name, " .
                "COALESCE(t_ln.translation_value, e.last_name) AS last_name, " .
                "e.code AS employee_code, " .
                "e.salary, " .
                "(SELECT COUNT(*) FROM position_approvals pa2 WHERE pa2.position_id = p.id) AS approval_count, " .
                "(SELECT COUNT(*) FROM position_approvals pa3 WHERE pa3.position_id = p.id AND pa3.admin_user_id = :admin_user_id) AS current_admin_approved " .
                "FROM positions p " .
                "LEFT JOIN position_assignments pa ON pa.position_id = p.id AND pa.end_date IS NULL " .
                "LEFT JOIN employees e ON e.id = pa.employee_id " .
                "LEFT JOIN translations t_jt ON t_jt.company_id = p.company_id AND t_jt.target_table = 'positions' AND t_jt.target_column = 'job_title' AND t_jt.target_id = p.id AND t_jt.language_code = :lang " .
                "LEFT JOIN translations t_dep ON t_dep.company_id = p.company_id AND t_dep.target_table = 'positions' AND t_dep.target_column = 'department' AND t_dep.target_id = p.id AND t_dep.language_code = :lang " .
                "LEFT JOIN translations t_fn ON t_fn.company_id = e.company_id AND t_fn.target_table = 'employees' AND t_fn.target_column = 'first_name' AND t_fn.target_id = e.id AND t_fn.language_code = :lang " .
                "LEFT JOIN translations t_ln ON t_ln.company_id = e.company_id AND t_ln.target_table = 'employees' AND t_ln.target_column = 'last_name' AND t_ln.target_id = e.id AND t_ln.language_code = :lang " .
                "WHERE p.company_id = :company_id",
                DbEnum::FETCH_ASSOC,
                ['company_id' => $companyId, 'admin_user_id' => $adminUserId, 'lang' => $activeLanguage]
            );
        }

        return $this->db->fetchAll(
            "SELECT p.id, p.parent_position_id, p.job_title, p.department, p.job_level, p.is_approved, e.first_name, e.last_name, e.code AS employee_code, e.salary, " .
            "(SELECT COUNT(*) FROM position_approvals pa2 WHERE pa2.position_id = p.id) AS approval_count, " .
            "(SELECT COUNT(*) FROM position_approvals pa3 WHERE pa3.position_id = p.id AND pa3.admin_user_id = :admin_user_id) AS current_admin_approved " .
            "FROM positions p " .
            "LEFT JOIN position_assignments pa ON pa.position_id = p.id AND pa.end_date IS NULL " .
            "LEFT JOIN employees e ON e.id = pa.employee_id " .
            "WHERE p.company_id = :company_id",
            DbEnum::FETCH_ASSOC,
            ['company_id' => $companyId, 'admin_user_id' => $adminUserId]
        );
    }

    /**
     * Build salary and children maps from a set of position rows.
     *
     * @param array<array<string, mixed>> $positions
     * @return array{salaryMap: array<int, int>, childrenMap: array<int, array<int>>}
     */
    public function buildSalaryAndChildrenMaps(array $positions): array
    {
        $salaryMap = [];
        $childrenMap = [];
        foreach ($positions as $pos) {
            $positionId = (int)$pos['id'];
            $salaryMap[$positionId] = (int)($pos['salary'] ?? 0);
            $parentId = !empty($pos['parent_position_id']) ? (int)$pos['parent_position_id'] : null;
            if ($parentId !== null) {
                $childrenMap[$parentId][] = $positionId;
            }
        }
        return ['salaryMap' => $salaryMap, 'childrenMap' => $childrenMap];
    }

    /**
     * Recursively calculate the cumulative salary for a position and all its descendants.
     */
    public function calculateGroupSalary(int $positionId, array $salaryMap, array $childrenMap, array &$memo = []): int
    {
        if (isset($memo[$positionId])) {
            return $memo[$positionId];
        }
        $total = $salaryMap[$positionId] ?? 0;
        foreach ($childrenMap[$positionId] ?? [] as $childId) {
            $total += $this->calculateGroupSalary($childId, $salaryMap, $childrenMap, $memo);
        }
        $memo[$positionId] = $total;
        return $total;
    }

    /**
     * Build the full chart node array for d3-org-chart consumption.
     *
     * @param array<array<string, mixed>> $positions
     * @return array<array<string, mixed>>
     */
    public function buildChartNodes(array $positions, bool $canViewSalary): array
    {
        ['salaryMap' => $salaryMap, 'childrenMap' => $childrenMap] = $this->buildSalaryAndChildrenMaps($positions);

        $groupSalaryMemo = [];
        $nodes = [];
        foreach ($positions as $pos) {
            $positionId = (int)$pos['id'];
            $isVacant = empty($pos['first_name']);
            $fullName = $isVacant
                ? 'Vacant Position'
                : trim($pos['first_name'] . ' ' . $pos['last_name']);

            $nodes[] = [
                'id'                     => (string)$pos['id'],
                'parentId'               => $pos['parent_position_id'] ? (string)$pos['parent_position_id'] : "",
                'positionName'           => $pos['job_title'],
                'jobLevel'               => $pos['job_level'] ?? null,
                'employeeName'           => $fullName,
                'employeeCode'           => $isVacant ? null : ($pos['employee_code'] ?? null),
                'isVacant'               => $isVacant,
                'isStructural'           => isset($childrenMap[$positionId]),
                'name'                   => $isVacant ? 'Vacant Seat' : trim($pos['first_name'] . ' ' . $pos['last_name']),
                'role'                   => $pos['job_title'],
                'dept'                   => $pos['department'],
                'is_approved'            => (bool)$pos['is_approved'],
                'approval_count'         => (int)$pos['approval_count'],
                'current_admin_approved' => (bool)$pos['current_admin_approved'],
                'salary'                 => $canViewSalary ? $salaryMap[$positionId] : 0,
                'group_salary'           => $canViewSalary ? $this->calculateGroupSalary($positionId, $salaryMap, $childrenMap, $groupSalaryMemo) : 0,
            ];
        }
        return $nodes;
    }

    /**
     * Fetch matrix connections for a tenant.
     *
     * @return array<array<string, mixed>>
     */
    public function getMatrixConnections(int $companyId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT id, from_position_id, to_position_id, connection_label FROM matrix_connections WHERE company_id = :company_id",
            DbEnum::FETCH_ASSOC,
            ['company_id' => $companyId]
        );

        $connections = [];
        foreach ($rows as $row) {
            $connections[] = [
                'id'    => (int)$row['id'],
                'from'  => (string)$row['from_position_id'],
                'to'    => (string)$row['to_position_id'],
                'label' => $row['connection_label'] ?? 'Dotted Line',
            ];
        }
        return $connections;
    }
}
