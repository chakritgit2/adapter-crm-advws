<?php

declare(strict_types=1);

use Phalcon\Db\Enum as DbEnum;

/**
 * HR Reports — company-scoped, date-filtered analytics across workforce,
 * leave, overtime, and turnover/tenure domains.
 *
 * Routes live under /{tenant_slug}/{company_slug}/dashboard/reports/...
 * and are mounted in app/config/router.php.
 */
class HrReportsController extends TenantBaseController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->db = $this->getDI()->get('db');
    }

    /**
     * Resolve a date range from query params (?from=&to=), defaulting to the
     * current calendar year. Returns [from, to] as 'Y-m-d' strings.
     */
    private function resolveDateRange(): array
    {
        $from = trim((string)$this->request->getQuery('from', 'string', ''));
        $to   = trim((string)$this->request->getQuery('to', 'string', ''));

        $year = (int)date('Y');
        if ($from === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = sprintf('%04d-01-01', $year);
        }
        if ($to === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = sprintf('%04d-12-31', $year);
        }
        // Guard against inverted ranges.
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        return [$from, $to];
    }

    /**
     * Reports hub — landing page with summary KPIs and category cards.
     */
    public function indexAction()
    {
        $this->view->setVar('title', $this->locale->t('reports.title'));
        $companyId = $this->currentCompanyId;
        [$from, $to] = $this->resolveDateRange();

        // --- Summary KPIs (current snapshot, not date-scoped) ---
        $totalEmployees = (int)$this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM employees WHERE company_id = :cid",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        )['cnt'];

        $totalPositions = (int)$this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM positions WHERE company_id = :cid",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        )['cnt'];

        $vacantPositions = (int)$this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM positions p
             WHERE p.company_id = :cid
               AND NOT EXISTS (
                   SELECT 1 FROM position_assignments pa
                   WHERE pa.position_id = p.id AND pa.end_date IS NULL
               )",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        )['cnt'];

        $pendingLeave = (int)$this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM leave_requests
             WHERE company_id = :cid AND status = 'pending'",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        )['cnt'];

        $pendingOvertime = (int)$this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM overtime_requests
             WHERE company_id = :cid AND status = 'pending'",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        )['cnt'];

        // Hires/exits within the date range (from milestones).
        $movementRow = $this->db->fetchOne(
            "SELECT
                SUM(CASE WHEN met.name = 'Onboarded' THEN 1 ELSE 0 END) AS hires,
                SUM(CASE WHEN met.name IN ('Voluntary Resignation','Involuntary Termination') THEN 1 ELSE 0 END) AS exits
             FROM employee_milestones em
             INNER JOIN milestone_event_types met ON met.id = em.event_type_id
             WHERE em.company_id = :cid AND em.event_date BETWEEN :from AND :to",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );
        $hires = (int)($movementRow['hires'] ?? 0);
        $exits = (int)($movementRow['exits'] ?? 0);

        $this->view->setVar('totalEmployees', $totalEmployees);
        $this->view->setVar('totalPositions', $totalPositions);
        $this->view->setVar('vacantPositions', $vacantPositions);
        $this->view->setVar('pendingLeave', $pendingLeave);
        $this->view->setVar('pendingOvertime', $pendingOvertime);
        $this->view->setVar('hires', $hires);
        $this->view->setVar('exits', $exits);
        $this->view->setVar('from', $from);
        $this->view->setVar('to', $to);
        $this->view->pick('dashboard/reports/index');
    }

    /**
     * Workforce & Headcount drill-down.
     */
    public function workforceAction()
    {
        $this->view->setVar('title', $this->locale->t('reports.workforce.title'));
        $companyId = $this->currentCompanyId;
        [$from, $to] = $this->resolveDateRange();

        // 1.1 Headcount by department (active assignments).
        $byDepartment = $this->db->fetchAll(
            "SELECT COALESCE(NULLIF(p.department,''), '—') AS department, COUNT(DISTINCT pa.employee_id) AS headcount
             FROM position_assignments pa
             INNER JOIN positions p ON p.id = pa.position_id AND p.company_id = :cid
             WHERE pa.end_date IS NULL
             GROUP BY p.department
             ORDER BY headcount DESC, department ASC",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        );

        // 1.2 Headcount by employment type.
        $byEmploymentType = $this->db->fetchAll(
            "SELECT COALESCE(NULLIF(e.employment_type,''), '—') AS employment_type, COUNT(*) AS headcount
             FROM employees e
             WHERE e.company_id = :cid
             GROUP BY e.employment_type
             ORDER BY headcount DESC, employment_type ASC",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        );

        // 1.3 Headcount by job level — ordered by the job_levels catalog sort_order
        // (company-level rows override tenant-level rows for the same code, matching
        // JobLevelsService). Free-text codes not present in the catalog sort last.
        $byJobLevel = $this->db->fetchAll(
            "SELECT COALESCE(NULLIF(p.job_level,''), '—') AS job_level, COUNT(DISTINCT pa.employee_id) AS headcount
             FROM position_assignments pa
             INNER JOIN positions p ON p.id = pa.position_id AND p.company_id = :cid
             LEFT JOIN (
                 SELECT code, sort_order FROM job_levels
                 WHERE tenant_id = :tenant_id AND company_id = :cid AND is_active = 1
                 UNION ALL
                 SELECT jl.code, jl.sort_order FROM job_levels jl
                 WHERE jl.tenant_id = :tenant_id AND jl.company_id IS NULL AND jl.is_active = 1
                   AND NOT EXISTS (
                       SELECT 1 FROM job_levels jl2
                       WHERE jl2.tenant_id = :tenant_id AND jl2.code = jl.code
                         AND jl2.company_id = :cid AND jl2.is_active = 1
                   )
             ) jl ON jl.code = p.job_level COLLATE utf8mb4_unicode_ci
             WHERE pa.end_date IS NULL
             GROUP BY p.job_level, jl.sort_order
             ORDER BY (jl.sort_order IS NULL), jl.sort_order ASC, p.job_level ASC",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'tenant_id' => $this->currentTenantId]
        );

        // 1.4 Hires vs exits per month (within range).
        $hiresExits = $this->db->fetchAll(
            "SELECT DATE_FORMAT(em.event_date, '%Y-%m') AS month,
                SUM(CASE WHEN met.name = 'Onboarded' THEN 1 ELSE 0 END) AS hires,
                SUM(CASE WHEN met.name IN ('Voluntary Resignation','Involuntary Termination') THEN 1 ELSE 0 END) AS exits
             FROM employee_milestones em
             INNER JOIN milestone_event_types met ON met.id = em.event_type_id
             WHERE em.company_id = :cid AND em.event_date BETWEEN :from AND :to
             GROUP BY month
             ORDER BY month ASC",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );

        // 1.5 Vacant vs filled positions.
        $positionFill = $this->db->fetchOne(
            "SELECT
                COUNT(DISTINCT p.id) AS total,
                COUNT(DISTINCT CASE WHEN filled.employee_id IS NOT NULL THEN p.id END) AS filled
             FROM positions p
             LEFT JOIN (
                 SELECT position_id, employee_id FROM position_assignments WHERE end_date IS NULL
             ) filled ON filled.position_id = p.id
             WHERE p.company_id = :cid",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        );
        $totalPos = (int)($positionFill['total'] ?? 0);
        $filledPos = (int)($positionFill['filled'] ?? 0);
        $vacantPos = $totalPos - $filledPos;

        $this->view->setVar('byDepartment', $byDepartment);
        $this->view->setVar('byEmploymentType', $byEmploymentType);
        $this->view->setVar('byJobLevel', $byJobLevel);
        $this->view->setVar('hiresExits', $hiresExits);
        $this->view->setVar('totalPositions', $totalPos);
        $this->view->setVar('filledPositions', $filledPos);
        $this->view->setVar('vacantPositions', $vacantPos);
        $this->view->setVar('from', $from);
        $this->view->setVar('to', $to);
        $this->view->pick('dashboard/reports/workforce');
    }

    /**
     * Leave & Absence drill-down.
     */
    public function leaveAction()
    {
        $this->view->setVar('title', $this->locale->t('reports.leave.title'));
        $companyId = $this->currentCompanyId;
        [$from, $to] = $this->resolveDateRange();
        $year = (int)substr($from, 0, 4);

        // 3.1 Leave utilization by type (current year balances).
        $utilization = $this->db->fetchAll(
            "SELECT lt.name AS leave_type,
                lt.workday_hours,
                SUM(lb.allowance_minutes) AS allowance_minutes,
                SUM(lb.used_minutes) AS used_minutes,
                ROUND(SUM(lb.used_minutes) / NULLIF(SUM(lb.allowance_minutes),0) * 100, 1) AS pct_used
             FROM leave_balances lb
             INNER JOIN leave_types lt ON lt.id = lb.leave_type_id
             WHERE lb.company_id = :cid AND lb.year = :year
             GROUP BY lt.id, lt.name, lt.workday_hours
             ORDER BY lt.name ASC",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'year' => (string)$year]
        );

        // 3.2 Employees near leave limit (used > 80% of allowance).
        $nearLimit = $this->db->fetchAll(
            "SELECT e.public_id, e.first_name, e.last_name, lt.name AS leave_type,
                lb.allowance_minutes, lb.used_minutes,
                ROUND(lb.used_minutes / NULLIF(lb.allowance_minutes,0) * 100, 1) AS pct_used
             FROM leave_balances lb
             INNER JOIN employees e ON e.id = lb.employee_id
             INNER JOIN leave_types lt ON lt.id = lb.leave_type_id
             WHERE lb.company_id = :cid AND lb.year = :year
               AND lb.allowance_minutes > 0
               AND (lb.used_minutes / lb.allowance_minutes) >= 0.80
             ORDER BY pct_used DESC
             LIMIT 100",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'year' => (string)$year]
        );

        // 3.3 Pending leave approvals with aging.
        $pendingAging = $this->db->fetchAll(
            "SELECT lr.public_id, e.first_name, e.last_name, lt.name AS leave_type,
                lr.start_date, lr.end_date, lr.requested_minutes, lr.created_at,
                DATEDIFF(CURDATE(), lr.created_at) AS age_days
             FROM leave_requests lr
             INNER JOIN employees e ON e.id = lr.employee_id
             INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
             WHERE lr.company_id = :cid AND lr.status = 'pending'
             ORDER BY lr.created_at ASC
             LIMIT 100",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        );

        // 3.4 Leave approval / rejection counts (within range).
        $approvalStats = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS cnt
             FROM leave_requests
             WHERE company_id = :cid
               AND (start_date BETWEEN :from AND :to OR end_date BETWEEN :from AND :to)
             GROUP BY status",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );

        // 3.5 Leave taken by department (approved, within range).
        $byDepartment = $this->db->fetchAll(
            "SELECT COALESCE(NULLIF(p.department,''), '—') AS department,
                COUNT(DISTINCT lr.id) AS requests,
                SUM(lr.requested_minutes) AS total_minutes
             FROM leave_requests lr
             INNER JOIN employees e ON e.id = lr.employee_id
             LEFT JOIN position_assignments pa ON pa.employee_id = e.id AND pa.end_date IS NULL
             LEFT JOIN positions p ON p.id = pa.position_id
             WHERE lr.company_id = :cid AND lr.status = 'approved'
               AND lr.start_date BETWEEN :from AND :to
             GROUP BY p.department
             ORDER BY total_minutes DESC, department ASC",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );

        // 3.6 Negative balances (used > allowance).
        $negativeBalances = $this->db->fetchAll(
            "SELECT e.public_id, e.first_name, e.last_name, lt.name AS leave_type,
                lb.allowance_minutes, lb.used_minutes,
                (lb.used_minutes - lb.allowance_minutes) AS over_minutes
             FROM leave_balances lb
             INNER JOIN employees e ON e.id = lb.employee_id
             INNER JOIN leave_types lt ON lt.id = lb.leave_type_id
             WHERE lb.company_id = :cid AND lb.year = :year
               AND lb.used_minutes > lb.allowance_minutes
             ORDER BY over_minutes DESC
             LIMIT 100",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'year' => (string)$year]
        );

        $this->view->setVar('utilization', $utilization);
        $this->view->setVar('nearLimit', $nearLimit);
        $this->view->setVar('pendingAging', $pendingAging);
        $this->view->setVar('approvalStats', $approvalStats);
        $this->view->setVar('byDepartment', $byDepartment);
        $this->view->setVar('negativeBalances', $negativeBalances);
        $this->view->setVar('from', $from);
        $this->view->setVar('to', $to);
        $this->view->setVar('year', $year);
        $this->view->pick('dashboard/reports/leave');
    }

    /**
     * Overtime drill-down.
     */
    public function overtimeAction()
    {
        $this->view->setVar('title', $this->locale->t('reports.overtime.title'));
        $companyId = $this->currentCompanyId;
        [$from, $to] = $this->resolveDateRange();

        // 4.1 Overtime hours by employee (approved, within range).
        $byEmployee = $this->db->fetchAll(
            "SELECT e.public_id, e.first_name, e.last_name,
                COUNT(orq.id) AS requests,
                SUM(orq.worked_minutes) AS total_minutes,
                ROUND(SUM(orq.worked_minutes) / 60.0, 1) AS total_hours
             FROM overtime_requests orq
             INNER JOIN employees e ON e.id = orq.employee_id
             WHERE orq.company_id = :cid AND orq.status = 'approved'
               AND DATE(orq.start_time) BETWEEN :from AND :to
             GROUP BY e.id, e.public_id, e.first_name, e.last_name
             ORDER BY total_minutes DESC
             LIMIT 100",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );

        // 4.2 Overtime hours by department.
        $byDepartment = $this->db->fetchAll(
            "SELECT COALESCE(NULLIF(p.department,''), '—') AS department,
                COUNT(orq.id) AS requests,
                SUM(orq.worked_minutes) AS total_minutes
             FROM overtime_requests orq
             INNER JOIN employees e ON e.id = orq.employee_id
             LEFT JOIN position_assignments pa ON pa.employee_id = e.id AND pa.end_date IS NULL
             LEFT JOIN positions p ON p.id = pa.position_id
             WHERE orq.company_id = :cid AND orq.status = 'approved'
               AND DATE(orq.start_time) BETWEEN :from AND :to
             GROUP BY p.department
             ORDER BY total_minutes DESC, department ASC",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );

        // 4.3 Pending overtime approvals with aging.
        $pendingAging = $this->db->fetchAll(
            "SELECT orq.public_id, e.first_name, e.last_name,
                orq.start_time, orq.end_time, orq.worked_minutes, orq.compensation_type,
                orq.created_at, DATEDIFF(CURDATE(), orq.created_at) AS age_days
             FROM overtime_requests orq
             INNER JOIN employees e ON e.id = orq.employee_id
             WHERE orq.company_id = :cid AND orq.status = 'pending'
             ORDER BY orq.created_at ASC
             LIMIT 100",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        );

        // 4.4 Payout vs TOIL split (within range).
        $compensationSplit = $this->db->fetchAll(
            "SELECT compensation_type, status, COUNT(*) AS cnt, SUM(worked_minutes) AS total_minutes
             FROM overtime_requests
             WHERE company_id = :cid
               AND DATE(start_time) BETWEEN :from AND :to
             GROUP BY compensation_type, status
             ORDER BY compensation_type, status",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );

        // 4.5 Overtime trend by month (approved, within range).
        $byMonth = $this->db->fetchAll(
            "SELECT DATE_FORMAT(orq.start_time, '%Y-%m') AS month,
                SUM(orq.worked_minutes) AS total_minutes,
                COUNT(*) AS requests
             FROM overtime_requests orq
             WHERE orq.company_id = :cid AND orq.status = 'approved'
               AND DATE(orq.start_time) BETWEEN :from AND :to
             GROUP BY month
             ORDER BY month ASC",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );

        $this->view->setVar('byEmployee', $byEmployee);
        $this->view->setVar('byDepartment', $byDepartment);
        $this->view->setVar('pendingAging', $pendingAging);
        $this->view->setVar('compensationSplit', $compensationSplit);
        $this->view->setVar('byMonth', $byMonth);
        $this->view->setVar('from', $from);
        $this->view->setVar('to', $to);
        $this->view->pick('dashboard/reports/overtime');
    }

    /**
     * Tenure, Turnover & Movement drill-down.
     */
    public function turnoverAction()
    {
        $this->view->setVar('title', $this->locale->t('reports.turnover.title'));
        $companyId = $this->currentCompanyId;
        [$from, $to] = $this->resolveDateRange();

        $totalEmployees = (int)$this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM employees WHERE company_id = :cid",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        )['cnt'];

        // 5.1 Turnover rate (exits in range / current headcount).
        $exitsRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM employee_milestones em
             INNER JOIN milestone_event_types met ON met.id = em.event_type_id
             WHERE em.company_id = :cid
               AND met.name IN ('Voluntary Resignation','Involuntary Termination')
               AND em.event_date BETWEEN :from AND :to",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );
        $exits = (int)($exitsRow['cnt'] ?? 0);
        $turnoverRate = $totalEmployees > 0 ? round($exits / $totalEmployees * 100, 2) : 0;

        // 5.2 Turnover by department.
        $turnoverByDept = $this->db->fetchAll(
            "SELECT COALESCE(NULLIF(p.department,''), '—') AS department, COUNT(DISTINCT em.id) AS exits
             FROM employee_milestones em
             INNER JOIN milestone_event_types met ON met.id = em.event_type_id
             LEFT JOIN position_assignments pa ON pa.employee_id = em.employee_id
                AND pa.start_date <= em.event_date AND (pa.end_date IS NULL OR pa.end_date >= em.event_date)
             LEFT JOIN positions p ON p.id = pa.position_id
             WHERE em.company_id = :cid
               AND met.name IN ('Voluntary Resignation','Involuntary Termination')
               AND em.event_date BETWEEN :from AND :to
             GROUP BY p.department
             ORDER BY exits DESC, department ASC",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );

        // 5.3 Average tenure (years) of currently active employees.
        $avgTenureRow = $this->db->fetchOne(
            "SELECT AVG(tenure_years) AS avg_years FROM (
                SELECT TIMESTAMPDIFF(YEAR, MIN(pa.start_date), CURDATE()) AS tenure_years
                FROM position_assignments pa
                INNER JOIN employees e ON e.id = pa.employee_id AND e.company_id = :cid
                WHERE pa.end_date IS NULL
                GROUP BY pa.employee_id
            ) t",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        );
        $avgTenure = $avgTenureRow && $avgTenureRow['avg_years'] !== null ? round((float)$avgTenureRow['avg_years'], 1) : 0;

        // 5.4 Upcoming service anniversaries (next 90 days) from Onboarded milestones.
        $anniversaries = $this->db->fetchAll(
            "SELECT e.public_id, e.first_name, e.last_name, em.event_date AS hire_date,
                TIMESTAMPDIFF(YEAR, em.event_date, CURDATE()) AS years
             FROM employee_milestones em
             INNER JOIN milestone_event_types met ON met.id = em.event_type_id
             INNER JOIN employees e ON e.id = em.employee_id
             WHERE em.company_id = :cid AND met.name = 'Onboarded'
               AND DATE_FORMAT(em.event_date, '%m-%d') BETWEEN DATE_FORMAT(CURDATE(), '%m-%d')
                   AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 90 DAY), '%m-%d')
             ORDER BY MONTH(em.event_date) ASC, DAY(em.event_date) ASC
             LIMIT 50",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId]
        );

        // 5.5 Internal mobility (transfers/promotions within range).
        $mobility = $this->db->fetchAll(
            "SELECT met.name AS event_type, COUNT(*) AS cnt
             FROM employee_milestones em
             INNER JOIN milestone_event_types met ON met.id = em.event_type_id
             WHERE em.company_id = :cid
               AND met.name IN ('Promoted','Lateral Transfer','Title Change')
               AND em.event_date BETWEEN :from AND :to
             GROUP BY met.name
             ORDER BY cnt DESC",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );

        // 5.6 Early-tenure attrition (exits within 6 months of onboarding).
        $earlyAttrition = $this->db->fetchAll(
            "SELECT e.public_id, e.first_name, e.last_name,
                hire.event_date AS hire_date, exit_ev.event_date AS exit_date,
                met.name AS exit_type,
                ROUND(TIMESTAMPDIFF(DAY, hire.event_date, exit_ev.event_date) / 30.0, 1) AS tenure_months
             FROM employee_milestones exit_ev
             INNER JOIN milestone_event_types met ON met.id = exit_ev.event_type_id
             INNER JOIN employees e ON e.id = exit_ev.employee_id
             INNER JOIN (
                 SELECT employee_id, MIN(event_date) AS event_date
                 FROM employee_milestones em2
                 INNER JOIN milestone_event_types m2 ON m2.id = em2.event_type_id AND m2.name = 'Onboarded'
                 WHERE em2.company_id = :cid
                 GROUP BY employee_id
             ) hire ON hire.employee_id = exit_ev.employee_id
             WHERE exit_ev.company_id = :cid
               AND met.name IN ('Voluntary Resignation','Involuntary Termination')
               AND exit_ev.event_date BETWEEN :from AND :to
               AND TIMESTAMPDIFF(DAY, hire.event_date, exit_ev.event_date) <= 180
             ORDER BY exit_ev.event_date DESC
             LIMIT 100",
            DbEnum::FETCH_ASSOC,
            ['cid' => $companyId, 'from' => $from, 'to' => $to]
        );

        $this->view->setVar('totalEmployees', $totalEmployees);
        $this->view->setVar('exits', $exits);
        $this->view->setVar('turnoverRate', $turnoverRate);
        $this->view->setVar('turnoverByDept', $turnoverByDept);
        $this->view->setVar('avgTenure', $avgTenure);
        $this->view->setVar('anniversaries', $anniversaries);
        $this->view->setVar('mobility', $mobility);
        $this->view->setVar('earlyAttrition', $earlyAttrition);
        $this->view->setVar('from', $from);
        $this->view->setVar('to', $to);
        $this->view->pick('dashboard/reports/turnover');
    }
}
