<?php

declare(strict_types=1);

use Phalcon\Db\Enum as DbEnum;

/**
 * HR Reports — company-scoped, date-filtered summary analytics.
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

}
