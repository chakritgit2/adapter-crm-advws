<?php

use Phalcon\Http\Response;
use Phalcon\Db\Enum as DbEnum;


class DashboardController extends TenantBaseController
{
    
    public function initialize(): void
    {
        parent::initialize();
        $this->db = $this->getDI()->get('db');
    }

    protected function savePostedTranslations(string $targetTable, int $targetId): void
    {
        $translations = $this->request->getPost('translations');
        if (!is_array($translations) || empty($translations)) {
            return;
        }

        $translationService = new TranslationService($this->db);
        foreach ($translations as $langCode => $columns) {
            if (!is_array($columns)) {
                continue;
            }
            foreach ($columns as $column => $value) {
                $value = trim((string)$value);
                if ($value !== '') {
                    $translationService->upsertTranslation(
                        $this->currentCompanyId,
                        $langCode,
                        $targetTable,
                        $column,
                        $targetId,
                        $value
                    );
                }
            }
        }
    }

    /**
     * Write an audit record to activity_logs.
     */
    protected function logActivity(string $action, string $targetTable, int $targetId, ?array $oldValues = null, ?array $newValues = null): void
    {
        $auth = $this->session->get('auth');
        $adminUserId = (int)($auth['id'] ?? 0);

        if ($adminUserId === 0) {
            return;
        }

        $this->db->execute(
            "INSERT INTO activity_logs (company_id, admin_user_id, action, target_table, target_id, old_values, new_values) " .
            "VALUES (:company_id, :admin_user_id, :action, :target_table, :target_id, :old_values, :new_values)",
            [
                'company_id' => $this->currentCompanyId,
                'admin_user_id' => $adminUserId,
                'action' => $action,
                'target_table' => $targetTable,
                'target_id' => $targetId,
                'old_values' => $oldValues !== null ? json_encode($oldValues) : null,
                'new_values' => $newValues !== null ? json_encode($newValues) : null,
            ]
        );
    }

    public function companiesDashboardAction()
    {
        $this->view->setVar('title', $this->locale->t('dashboard.company_dashboard.title'));

        $companyId = $this->currentCompanyId;

        // Basic metrics
        $totalEmployees = 0;
        $employeeCountRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM employees WHERE company_id = :company_id",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $companyId]
        );
        if ($employeeCountRow) {
            $totalEmployees = (int)$employeeCountRow['cnt'];
        }

        $totalPositions = 0;
        $positionCountRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM positions WHERE company_id = :company_id",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $companyId]
        );
        if ($positionCountRow) {
            $totalPositions = (int)$positionCountRow['cnt'];
        }

        // Demographics: age brackets (fixed order, always show all brackets)
        $ageBracketSql = "
            SELECT 
                b.label AS age_bracket,
                b.sort_order,
                COUNT(e.id) AS employee_count
            FROM (
                SELECT '18-25' AS label, 1 AS sort_order, 18 AS min_age, 25 AS max_age
                UNION ALL SELECT '26-35', 2, 26, 35
                UNION ALL SELECT '36-45', 3, 36, 45
                UNION ALL SELECT '46-55', 4, 46, 55
                UNION ALL SELECT '55+', 5, 56, 999
            ) b
            LEFT JOIN employees e ON e.company_id = :company_id
                AND e.date_of_birth IS NOT NULL
                AND TIMESTAMPDIFF(YEAR, e.date_of_birth, CURDATE()) BETWEEN b.min_age AND b.max_age
            GROUP BY b.label, b.sort_order
            ORDER BY b.sort_order ASC
        ";
        $ageBracketResult = $this->db->query($ageBracketSql, ['company_id' => $companyId]);
        $ageBracketResult->setFetchMode(Phalcon\Db\Enum::FETCH_ASSOC);
        $ageBrackets = $ageBracketResult->fetchAll();

        $ageChartPayload = [];
        foreach ($ageBrackets as $bracket) {
            $ageChartPayload[] = [
                'label' => $bracket['age_bracket'],
                'count' => (int)$bracket['employee_count']
            ];
        }
        $ageChartPayloadJson = htmlspecialchars(json_encode($ageChartPayload), ENT_QUOTES, 'UTF-8');

        // Demographics: upcoming birthdays
        $upcomingBirthdaysSql = "
            SELECT 
                id, 
                first_name, 
                last_name, 
                date_of_birth,
                DATE_FORMAT(date_of_birth, '%M %D') AS formatted_birthday
            FROM employees
            WHERE company_id = :company_id AND date_of_birth IS NOT NULL
            AND (
                DATE_FORMAT(date_of_birth, '%m-%d') BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 30 DAY), '%m-%d')
                OR
                (MONTH(CURDATE()) = 12 AND DATE_FORMAT(date_of_birth, '%m-%d') BETWEEN '01-01' AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 30 DAY), '%m-%d'))
            )
            ORDER BY MONTH(date_of_birth) ASC, DAY(date_of_birth) ASC
        ";
        $upcomingBirthdaysResult = $this->db->query($upcomingBirthdaysSql, ['company_id' => $companyId]);
        $upcomingBirthdaysResult->setFetchMode(Phalcon\Db\Enum::FETCH_ASSOC);
        $upcomingBirthdays = $upcomingBirthdaysResult->fetchAll();

        // Custom attributes visible on dashboard
        $customAttributes = $this->db->fetchAll(
            "SELECT id, name, attribute_entity, field_type, dropdown_choice FROM custom_attributes WHERE company_id = :company_id AND show_in_dashboard = 1 AND attribute_entity = 'employees'",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $companyId]
        );

        $attributeAggregates = [];
        $dashboardCards = [];
        $dashboardCharts = [];

        foreach ($customAttributes as $attr) {
            $attrId = (int)$attr['id'];
            $fieldType = $attr['field_type'];
            $name = $attr['name'];

            if ($fieldType === 'number') {
                $avgRow = $this->db->fetchOne(
                    "SELECT AVG(CAST(eav.value AS DECIMAL(10,2))) AS avg_value FROM employee_attribute_values eav INNER JOIN employees e ON e.id = eav.employee_id WHERE eav.attribute_id = :attribute_id AND e.company_id = :company_id",
                    Phalcon\Db\Enum::FETCH_ASSOC,
                    ['attribute_id' => $attrId, 'company_id' => $companyId]
                );
                $avgValue = $avgRow && $avgRow['avg_value'] !== null ? round((float)$avgRow['avg_value'], 2) : 0;
                $dashboardCards[] = [
                    'id' => $attrId,
                    'name' => $name,
                    'value' => $avgValue
                ];
            } elseif (in_array($fieldType, ['boolean', 'dropdown'], true)) {
                $labels = [];
                $data = [];
                $values = [];

                if ($fieldType === 'boolean') {
                    $yesRow = $this->db->fetchOne(
                        "SELECT COUNT(*) AS cnt FROM employee_attribute_values eav INNER JOIN employees e ON e.id = eav.employee_id WHERE eav.attribute_id = :attribute_id AND e.company_id = :company_id AND eav.value IN ('1', 'true', 'yes')",
                        Phalcon\Db\Enum::FETCH_ASSOC,
                        ['attribute_id' => $attrId, 'company_id' => $companyId]
                    );
                    $yesCount = $yesRow ? (int)$yesRow['cnt'] : 0;
                    $noCount = $totalEmployees - $yesCount;
                    $labels = ['Yes', 'No'];
                    $data = [$yesCount, $noCount];
                    $values = ['1', '0'];
                } else {
                    $valueRows = $this->db->fetchAll(
                        "SELECT eav.value, COUNT(*) AS cnt FROM employee_attribute_values eav INNER JOIN employees e ON e.id = eav.employee_id WHERE eav.attribute_id = :attribute_id AND e.company_id = :company_id GROUP BY eav.value",
                        Phalcon\Db\Enum::FETCH_ASSOC,
                        ['attribute_id' => $attrId, 'company_id' => $companyId]
                    );
                    foreach ($valueRows as $row) {
                        $labels[] = (string)$row['value'];
                        $data[] = (int)$row['cnt'];
                        $values[] = (string)$row['value'];
                    }
                }

                $dashboardCharts[] = [
                    'id' => $attrId,
                    'name' => $name,
                    'field_type' => $fieldType,
                    'json' => htmlspecialchars(json_encode([
                        'id' => $attrId,
                        'name' => $name,
                        'labels' => $labels,
                        'data' => $data,
                        'values' => $values
                    ]), ENT_QUOTES, 'UTF-8')
                ];
            }
        }

        // Roster custom attribute columns (all field types flagged for dashboard)
        $dashboardRosterAttributes = array_map(function ($attr) {
            return [
                'id' => (int)$attr['id'],
                'name' => $attr['name'],
                'field_type' => $attr['field_type']
            ];
        }, $customAttributes);

        // Roster
        $roster = $this->db->fetchAll(
            "SELECT e.id, e.public_id, e.first_name, e.last_name, e.email, e.date_of_birth, TIMESTAMPDIFF(YEAR, e.date_of_birth, CURDATE()) AS age, p.job_title, p.department " .
            "FROM employees e " .
            "LEFT JOIN position_assignments pa ON pa.employee_id = e.id AND pa.end_date IS NULL " .
            "LEFT JOIN positions p ON p.id = pa.position_id " .
            "WHERE e.company_id = :company_id " .
            "ORDER BY e.last_name ASC, e.first_name ASC",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $companyId]
        );
        $roster = $this->attachEmployeeAttributeValues($roster, $companyId);
        $rosterJson = htmlspecialchars(json_encode($roster), ENT_QUOTES, 'UTF-8');
        $dashboardRosterAttributesJson = htmlspecialchars(json_encode($dashboardRosterAttributes), ENT_QUOTES, 'UTF-8');
        $tenantSlugJson = htmlspecialchars(json_encode($this->currentTenantSlug . '/' . ($this->currentCompanySlug ?? '')), ENT_QUOTES, 'UTF-8');

        $this->view->setVar('totalEmployees', $totalEmployees);
        $this->view->setVar('totalPositions', $totalPositions);
        $this->view->setVar('ageBrackets', $ageBrackets);
        $this->view->setVar('ageChartPayloadJson', $ageChartPayloadJson);
        $this->view->setVar('upcomingBirthdays', $upcomingBirthdays);
        $this->view->setVar('dashboardCards', $dashboardCards);
        $this->view->setVar('dashboardCharts', $dashboardCharts);
        $this->view->setVar('dashboardRosterAttributes', $dashboardRosterAttributes);
        $this->view->setVar('dashboardRosterAttributesJson', $dashboardRosterAttributesJson);
        $this->view->setVar('roster', $roster);
        $this->view->setVar('rosterJson', $rosterJson);
        $this->view->setVar('tenantSlugJson', $tenantSlugJson);
        $this->view->pick('dashboard/companies-dashboard');
    }

    public function filterEmployeesAction()
    {
        $attributeId = (int)$this->request->getQuery('attribute_id', 'int', 0);
        $attributeValue = $this->request->getQuery('value', 'string', '');

        $companyId = $this->currentCompanyId;

        $sql = "
            SELECT e.id, e.public_id, e.first_name, e.last_name, e.email, e.date_of_birth, TIMESTAMPDIFF(YEAR, e.date_of_birth, CURDATE()) AS age, p.job_title, p.department
            FROM employees e
            INNER JOIN employee_attribute_values eav ON eav.employee_id = e.id
            LEFT JOIN position_assignments pa ON pa.employee_id = e.id AND pa.end_date IS NULL
            LEFT JOIN positions p ON p.id = pa.position_id
            WHERE e.company_id = :company_id AND eav.attribute_id = :attribute_id AND eav.value = :value
            ORDER BY e.last_name ASC, e.first_name ASC
        ";
        $employees = $this->db->fetchAll(
            $sql,
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $companyId, 'attribute_id' => $attributeId, 'value' => $attributeValue]
        );

        $employees = $this->attachEmployeeAttributeValues($employees, $companyId);

        $this->response->setJsonContent([
            'status' => 'success',
            'data' => $employees
        ]);
        return $this->response->send();
    }

    public function filterEmployeesByAgeAction()
    {
        $ageBracket = $this->request->getQuery('age_bracket', 'string', '');
        $companyId = $this->currentCompanyId;

        $bracketMap = [
            '18-25' => [18, 25],
            '26-35' => [26, 35],
            '36-45' => [36, 45],
            '46-55' => [46, 55],
            '55+' => [56, 999]
        ];

        $range = $bracketMap[$ageBracket] ?? null;
        if (!$range) {
            $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Invalid age bracket.'
            ]);
            return $this->response->send();
        }

        $employees = $this->db->fetchAll(
            "SELECT e.id, e.public_id, e.first_name, e.last_name, e.email, e.date_of_birth, TIMESTAMPDIFF(YEAR, e.date_of_birth, CURDATE()) AS age, p.job_title, p.department " .
            "FROM employees e " .
            "LEFT JOIN position_assignments pa ON pa.employee_id = e.id AND pa.end_date IS NULL " .
            "LEFT JOIN positions p ON p.id = pa.position_id " .
            "WHERE e.company_id = :company_id AND e.date_of_birth IS NOT NULL AND TIMESTAMPDIFF(YEAR, e.date_of_birth, CURDATE()) BETWEEN :min_age AND :max_age " .
            "ORDER BY e.last_name ASC, e.first_name ASC",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $companyId, 'min_age' => $range[0], 'max_age' => $range[1]]
        );
        $employees = $this->attachEmployeeAttributeValues($employees, $companyId);

        $this->response->setJsonContent([
            'status' => 'success',
            'data' => $employees
        ]);
        return $this->response->send();
    }

    private function getDashboardRosterAttributes(int $companyId): array
    {
        return $this->db->fetchAll(
            "SELECT id, name, field_type FROM custom_attributes WHERE company_id = :company_id AND show_in_dashboard = 1 AND attribute_entity = 'employees'",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $companyId]
        );
    }

    private function attachEmployeeAttributeValues(array $employees, int $companyId): array
    {
        if (empty($employees)) {
            return $employees;
        }

        $dashboardRosterAttributes = $this->getDashboardRosterAttributes($companyId);

        if (empty($dashboardRosterAttributes)) {
            foreach ($employees as &$emp) {
                $emp['attributes'] = [];
                if (!empty($emp['public_id'])) {
                    unset($emp['id']);
                }
            }
            unset($emp);
            return $employees;
        }

        $employeeIds = array_column($employees, 'id');
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $attrIds = array_column($dashboardRosterAttributes, 'id');
        $attrPlaceholders = implode(',', array_fill(0, count($attrIds), '?'));

        $values = $this->db->fetchAll(
            "SELECT eav.employee_id, eav.attribute_id, eav.value, ca.name AS attribute_name, ca.field_type " .
            "FROM employee_attribute_values eav " .
            "INNER JOIN custom_attributes ca ON ca.id = eav.attribute_id " .
            "WHERE eav.employee_id IN ({$placeholders}) AND eav.attribute_id IN ({$attrPlaceholders})",
            Phalcon\Db\Enum::FETCH_ASSOC,
            array_merge($employeeIds, $attrIds)
        );

        $attributeValues = [];
        foreach ($values as $v) {
            $attributeValues[(int)$v['employee_id']][$v['attribute_name']] = [
                'value' => $v['value'],
                'field_type' => $v['field_type']
            ];
        }

        foreach ($employees as &$emp) {
            $emp['attributes'] = $attributeValues[(int)$emp['id']] ?? [];
            if (!empty($emp['public_id'])) {
                unset($emp['id']);
            }
        }
        unset($emp);

        return $employees;
    }

    public function settingsAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.system.title'));
        $this->view->pick('dashboard/settings/index');
    }

    /**
     * Company Holidays — Super Admin calendar management.
     * Route: GET /{tenant_slug}/{company_slug}/dashboard/settings/company-holidays
     *
     * Shows a 12-month calendar for the selected year. Super Admins can
     * right-click any day to mark/unmark it as a Company Holiday. Holiday
     * days are rendered with a distinct colour so they are easy to
     * distinguish from regular days.
     */
    private function retiredCompanyHolidaysAction()
    {
        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_company_holidays'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings'));
            return;
        }

        // Year selector (defaults to current year). Clamp to a sane range.
        $year = (int)$this->request->get('year', 'int', (int)date('Y'));
        $currentYear = (int)date('Y');
        if ($year < $currentYear - 5 || $year > $currentYear + 10) {
            $year = $currentYear;
        }

        // Load all holidays for this company + year as a date => name map.
        $rows = $this->db->fetchAll(
            "SELECT holiday_date, name FROM company_holidays " .
            "WHERE company_id = :company_id AND YEAR(holiday_date) = :year " .
            "ORDER BY holiday_date ASC",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId, 'year' => $year]
        );

        $holidays = [];
        foreach ($rows as $row) {
            $holidays[$row['holiday_date']] = $row['name'];
        }

        $this->view->setVar('title', $this->locale->t('settings.company_holidays.title'));
        $this->view->setVar('selectedYear', $year);
        $this->view->setVar('currentYear', $currentYear);
        $this->view->setVar('holidaysJson', json_encode($holidays));
        $this->view->setVar('isSuperAdmin', true);
        $this->view->setVar('toggleUrl', $this->tenantUrl('/dashboard/settings/company-holidays/toggle'));
        $this->view->pick('dashboard/settings/company-holidays');
    }

    /**
     * POST /dashboard/settings/company-holidays/toggle
     *
     * Inline AJAX endpoint used by the Company Holidays calendar. Super
     * Admins right-click a day to mark it as a Company Holiday (insert) or
     * unmark it (delete). An optional holiday name may be supplied when
     * marking. Returns JSON.
     */
    private function retiredCompanyHolidaysToggleAction()
    {
        $this->view->disable();

        if (!$this->isSuperAdmin()) {
            $this->response->setStatusCode(403, 'Forbidden');
            $this->response->setJsonContent(['success' => false, 'message' => $this->locale->t('settings.company_holidays.not_authorized')]);
            return $this->response->send();
        }

        if (!$this->request->isPost()) {
            $this->response->setStatusCode(405, 'Method Not Allowed');
            $this->response->setJsonContent(['success' => false, 'message' => 'POST required.']);
            return $this->response->send();
        }

        $date = trim((string)$this->request->getPost('date', 'string', ''));
        $name = trim((string)$this->request->getPost('name', 'string', ''));

        // Validate YYYY-MM-DD and a real calendar date.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
            $this->response->setStatusCode(400, 'Bad Request');
            $this->response->setJsonContent(['success' => false, 'message' => $this->locale->t('settings.company_holidays.invalid_date')]);
            return $this->response->send();
        }

        $existing = $this->db->fetchOne(
            "SELECT id, name FROM company_holidays WHERE company_id = :company_id AND holiday_date = :date LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId, 'date' => $date]
        );

        if ($existing) {
            // Unmark → delete the holiday.
            $this->db->execute(
                "DELETE FROM company_holidays WHERE id = :id AND company_id = :company_id",
                ['id' => (int)$existing['id'], 'company_id' => $this->currentCompanyId]
            );

            $this->logActivity('delete', 'company_holidays', (int)$existing['id'], ['holiday_date' => $date, 'name' => $existing['name']], null);

            $this->response->setJsonContent([
                'success' => true,
                'is_holiday' => false,
                'date' => $date,
            ]);
            return $this->response->send();
        }

        // Mark → insert the holiday (name optional).
        $this->db->execute(
            "INSERT INTO company_holidays (company_id, holiday_date, name) VALUES (:company_id, :date, :name)",
            [
                'company_id' => $this->currentCompanyId,
                'date' => $date,
                'name' => $name !== '' ? $name : null,
            ]
        );

        $newId = (int)$this->db->lastInsertId();

        $this->logActivity('create', 'company_holidays', $newId, null, ['holiday_date' => $date, 'name' => $name !== '' ? $name : null]);

        $this->response->setJsonContent([
            'success' => true,
            'is_holiday' => true,
            'date' => $date,
            'name' => $name !== '' ? $name : null,
        ]);
        return $this->response->send();
    }

    /**
     * Help Center — Super Admin operational hub.
     * Route: GET /{tenant_slug}/{company_slug}/dashboard/help-center
     *
     * Gated to the 'Super Admin' role via isSuperAdmin(). The page is
     * read-only and shows platform-wide status counts plus curated links
     * to the operational documentation in resource/mdSource.
     */
    public function helpCenterAction()
    {
        // 1. Role guard — Super Admin only.
        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_help_center'));
            $this->response->redirect($this->tenantUrl('/dashboard'));
            return;
        }

        // 2. View variables.
        $this->view->setVar('title', $this->locale->t('help_center.title'));
        $this->view->setVar('isSuperAdmin', true);

        $this->view->pick('dashboard/help-center');
    }

    /**
     * Employee Milestone Event Types
     */
    private function retiredEmployeeMilestoneEventTypesAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.milestone_event_types.title'));

        $eventTypes = $this->db->fetchAll(
            "SELECT id, name, name_th, color_tag, is_active, company_id " .
            "FROM milestone_event_types " .
            "WHERE company_id = :company_id OR company_id IS NULL " .
            "ORDER BY id IS NULL ASC",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId]
        );

        $this->view->setVar('eventTypes', $eventTypes);
        $this->view->pick('dashboard/settings/milestone-event-types');
    }

    private function retiredEmployeeMilestoneEventTypesCreateAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.milestone_event_types.new.title'));
        $this->view->setVar('mode', 'create');
        $this->view->pick('dashboard/settings/milestone-event-types-action');
    }

    private function retiredEmployeeMilestoneEventTypesStoreAction()
    {
        $name = trim($this->request->getPost('name', 'string', ''));
        $nameTh = trim($this->request->getPost('name_th', 'string', ''));
        $colorTag = trim($this->request->getPost('color_tag', 'string', ''));
        $isActive = $this->request->getPost('is_active', 'int', 0);

        if (empty($name)) {
            $this->flashSession->error($this->locale->t('flash.name_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/employee-milestone-event-types/create'));
            return;
        }

        $this->db->execute(
            "INSERT INTO milestone_event_types (company_id, name, name_th, color_tag, is_active) VALUES (:company_id, :name, :name_th, :color_tag, :is_active)",
            [
                'company_id' => $this->currentCompanyId,
                'name' => $name,
                'name_th' => $nameTh ?: null,
                'color_tag' => $colorTag ?: null,
                'is_active' => $isActive ? 1 : 0
            ]
        );

        $this->flashSession->success($this->locale->t('flash.milestone_event_type_created'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/employee-milestone-event-types'));
    }

    private function retiredEmployeeMilestoneEventTypesEditAction()
    {
        $id = (int)$this->dispatcher->getParam('id');

        $eventType = $this->db->fetchOne(
            "SELECT id, name, name_th, color_tag, is_active, company_id FROM milestone_event_types WHERE id = :id AND (company_id = :company_id OR company_id IS NULL) LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => $id, 'company_id' => $this->currentCompanyId]
        );

        if (!$eventType) {
            $this->flashSession->error($this->locale->t('flash.event_type_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/employee-milestone-event-types'));
            return;
        }

        if (empty($eventType['company_id'])) {
            $this->view->setVar('title', $this->locale->t('settings.milestone_event_types.view.title'));
            $this->view->setVar('mode', 'view');
            $this->view->setVar('eventType', $eventType);
            $this->view->pick('dashboard/settings/milestone-event-types-action');
            return;
        }

        $this->view->setVar('title', $this->locale->t('settings.milestone_event_types.edit.title'));
        $this->view->setVar('mode', 'edit');
        $this->view->setVar('eventType', $eventType);

        $internalId = (int)$eventType['id'];

        $translationService = new TranslationService($this->db);
        $installedLanguages = $translationService->getInstalledLanguages($this->currentCompanyId);
        $existingTranslations = [];
        if (!empty($installedLanguages) && $internalId) {
            $existingTranslations = $translationService->getTranslationsForRecord(
                $this->currentCompanyId,
                'milestone_event_types',
                $internalId
            );
        }

        $this->view->setVar('installedLanguages', $installedLanguages);
        $this->view->setVar('existingTranslations', $existingTranslations);
        $this->view->setVar('eventTypeInternalId', $internalId);
        $this->view->pick('dashboard/settings/milestone-event-types-action');
    }

    private function retiredEmployeeMilestoneEventTypesUpdateAction()
    {
        $id = (int)$this->dispatcher->getParam('id');
        $name = trim($this->request->getPost('name', 'string', ''));
        $nameTh = trim($this->request->getPost('name_th', 'string', ''));
        $colorTag = trim($this->request->getPost('color_tag', 'string', ''));
        $isActive = $this->request->getPost('is_active', 'int', 0);

        if (empty($name)) {
            $this->flashSession->error($this->locale->t('flash.name_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/employee-milestone-event-types/edit/' . $id));
            return;
        }

        $eventType = $this->db->fetchOne(
            "SELECT company_id FROM milestone_event_types WHERE id = :id AND (company_id = :company_id OR company_id IS NULL) LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => $id, 'company_id' => $this->currentCompanyId]
        );

        if (!$eventType) {
            $this->flashSession->error($this->locale->t('flash.event_type_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/employee-milestone-event-types'));
            return;
        }

        if (empty($eventType['company_id'])) {
            $this->flashSession->error($this->locale->t('flash.event_type_system_default'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/employee-milestone-event-types'));
            return;
        }

        $this->db->execute(
            "UPDATE milestone_event_types SET name = :name, name_th = :name_th, color_tag = :color_tag, is_active = :is_active WHERE id = :id AND company_id = :company_id",
            [
                'id' => $id,
                'company_id' => $this->currentCompanyId,
                'name' => $name,
                'name_th' => $nameTh ?: null,
                'color_tag' => $colorTag ?: null,
                'is_active' => $isActive ? 1 : 0
            ]
        );

        $this->flashSession->success($this->locale->t('flash.milestone_event_type_updated'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/employee-milestone-event-types'));
    }

    private function retiredEmployeeMilestoneEventTypesDeleteAction()
    {
        $id = (int)$this->dispatcher->getParam('id');

        $this->db->execute(
            "DELETE FROM milestone_event_types WHERE id = :id AND company_id = :company_id",
            ['id' => $id, 'company_id' => $this->currentCompanyId]
        );

        $this->flashSession->success($this->locale->t('flash.milestone_event_type_deleted'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/employee-milestone-event-types'));
    }

    /**
     * Job Levels
     */
    private function retiredJobLevelsAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.job_levels.title'));

        $levels = $this->db->fetchAll(
            "SELECT id, company_id, code, category, name, sort_order, is_active, can_approve_leave " .
            "FROM job_levels " .
            "WHERE company_id = :company_id OR company_id IS NULL " .
            "ORDER BY sort_order ASC, code ASC",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId]
        );

        $this->view->setVar('jobLevels', $levels);
        $this->view->pick('dashboard/settings/job-levels');
    }

    private function retiredJobLevelsCreateAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.job_levels.new.title'));
        $this->view->setVar('mode', 'create');
        $this->view->pick('dashboard/settings/job-levels-action');
    }

    private function retiredJobLevelsStoreAction()
    {
        $code = strtoupper(trim($this->request->getPost('code', 'string', '')));
        $category = trim($this->request->getPost('category', 'string', ''));
        $name = trim($this->request->getPost('name', 'string', ''));
        $sortOrder = (int)$this->request->getPost('sort_order', 'int', 0);
        $isActive = $this->request->getPost('is_active', 'int', 0);
        $canApproveLeave = $this->request->getPost('can_approve_leave', 'int', 0);

        if (empty($code) || empty($category) || empty($name)) {
            $this->flashSession->error($this->locale->t('flash.job_level_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/job-levels/create'));
            return;
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM job_levels WHERE company_id = :company_id AND code = :code LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId, 'code' => $code]
        );

        if ($existing) {
            $this->flashSession->error($this->locale->t('flash.job_level_duplicate', ['code' => $code]));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/job-levels/create'));
            return;
        }

        $this->db->execute(
            "INSERT INTO job_levels (company_id, code, category, name, sort_order, is_active, can_approve_leave) " .
            "VALUES (:company_id, :code, :category, :name, :sort_order, :is_active, :can_approve_leave)",
            [
                'company_id' => $this->currentCompanyId,
                'code' => $code,
                'category' => $category,
                'name' => $name,
                'sort_order' => $sortOrder,
                'is_active' => $isActive ? 1 : 0,
                'can_approve_leave' => $canApproveLeave ? 1 : 0,
            ]
        );

        $this->flashSession->success($this->locale->t('flash.job_level_created'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/job-levels'));
    }

    private function retiredJobLevelsEditAction()
    {
        $id = (int)$this->dispatcher->getParam('id');

        $level = $this->db->fetchOne(
            "SELECT id, company_id, code, category, name, sort_order, is_active, can_approve_leave " .
            "FROM job_levels WHERE id = :id AND (company_id = :company_id OR company_id IS NULL) LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => $id, 'company_id' => $this->currentCompanyId]
        );

        if (!$level) {
            $this->flashSession->error($this->locale->t('flash.job_level_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/job-levels'));
            return;
        }

        if ($level['company_id'] === null) {
            $this->view->setVar('title', $this->locale->t('settings.job_levels.view.title'));
            $this->view->setVar('mode', 'view');
        } else {
            $this->view->setVar('title', $this->locale->t('settings.job_levels.edit.title'));
            $this->view->setVar('mode', 'edit');
        }

        $this->view->setVar('jobLevel', $level);
        $this->view->pick('dashboard/settings/job-levels-action');
    }

    private function retiredJobLevelsUpdateAction()
    {
        $id = (int)$this->dispatcher->getParam('id');
        $code = strtoupper(trim($this->request->getPost('code', 'string', '')));
        $category = trim($this->request->getPost('category', 'string', ''));
        $name = trim($this->request->getPost('name', 'string', ''));
        $sortOrder = (int)$this->request->getPost('sort_order', 'int', 0);
        $isActive = $this->request->getPost('is_active', 'int', 0);
        $canApproveLeave = $this->request->getPost('can_approve_leave', 'int', 0);

        if (empty($code) || empty($category) || empty($name)) {
            $this->flashSession->error($this->locale->t('flash.job_level_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/job-levels/edit/' . $id));
            return;
        }

        $level = $this->db->fetchOne(
            "SELECT company_id FROM job_levels WHERE id = :id AND (company_id = :company_id OR company_id IS NULL) LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => $id, 'company_id' => $this->currentCompanyId]
        );

        if (!$level) {
            $this->flashSession->error($this->locale->t('flash.job_level_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/job-levels'));
            return;
        }

        if ($level['company_id'] === null) {
            $this->flashSession->error($this->locale->t('flash.job_level_system_default'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/job-levels'));
            return;
        }

        $duplicate = $this->db->fetchOne(
            "SELECT id FROM job_levels WHERE company_id = :company_id AND code = :code AND id != :id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId, 'code' => $code, 'id' => $id]
        );

        if ($duplicate) {
            $this->flashSession->error($this->locale->t('flash.job_level_duplicate', ['code' => $code]));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/job-levels/edit/' . $id));
            return;
        }

        $this->db->execute(
            "UPDATE job_levels SET code = :code, category = :category, name = :name, sort_order = :sort_order, is_active = :is_active, can_approve_leave = :can_approve_leave " .
            "WHERE id = :id AND company_id = :company_id",
            [
                'id' => $id,
                'company_id' => $this->currentCompanyId,
                'code' => $code,
                'category' => $category,
                'name' => $name,
                'sort_order' => $sortOrder,
                'is_active' => $isActive ? 1 : 0,
                'can_approve_leave' => $canApproveLeave ? 1 : 0,
            ]
        );

        $this->flashSession->success($this->locale->t('flash.job_level_updated'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/job-levels'));
    }

    private function retiredJobLevelsDeleteAction()
    {
        $id = (int)$this->dispatcher->getParam('id');

        $this->db->execute(
            "DELETE FROM job_levels WHERE id = :id AND company_id = :company_id",
            ['id' => $id, 'company_id' => $this->currentCompanyId]
        );

        $this->flashSession->success($this->locale->t('flash.job_level_deleted'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/job-levels'));
    }

    /**
     * Custom Attributes
     */
    public function customAttributesAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.custom_attributes.title'));

        $entityFilter = $this->request->getQuery('entity', 'string', '');
        $this->view->setVar('entityFilter', $entityFilter);

        $sql = "SELECT id, name, attribute_entity, field_type, is_required, show_in_list, show_in_dashboard FROM custom_attributes WHERE company_id = :company_id";
        $params = ['company_id' => $this->currentCompanyId];

        if (in_array($entityFilter, ['employees', 'positions', 'companies'], true)) {
            $sql .= " AND attribute_entity = :entity";
            $params['entity'] = $entityFilter;
        }

        $sql .= " ORDER BY name ASC";

        $attributes = $this->db->fetchAll($sql, Phalcon\Db\Enum::FETCH_ASSOC, $params);

        $this->view->setVar('attributes', $attributes);
        $this->view->pick('dashboard/settings/custom-attributes');
    }

    public function customAttributesCreateAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.custom_attributes.new.title'));
        $this->view->setVar('mode', 'create');
        $this->view->pick('dashboard/settings/custom-attributes-action');
    }

    public function customAttributesStoreAction()
    {
        $name = trim($this->request->getPost('name', 'string', ''));
        $attributeEntity = $this->request->getPost('attribute_entity', 'string', '');
        $fieldType = $this->request->getPost('field_type', 'string', 'text');
        $isRequired = $this->request->getPost('is_required', 'int', 0);
        $showInList = $this->request->getPost('show_in_list', 'int', 0);
        $showInDashboard = $this->request->getPost('show_in_dashboard', 'int', 0);

        $dropdownOptionsRaw = $this->request->getPost('dropdown_options', null, '[]');
        $dropdownChoice = json_decode($dropdownOptionsRaw, true);
        if (!is_array($dropdownChoice)) {
            $dropdownChoice = [];
        }
        $dropdownChoice = array_values(array_filter(array_map('trim', $dropdownChoice)));

        if (empty($name) || empty($attributeEntity)) {
            $this->flashSession->error($this->locale->t('flash.attribute_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/custom-attributes/create'));
            return;
        }

        $this->db->execute(
            "INSERT INTO custom_attributes (company_id, name, attribute_entity, field_type, is_required, show_in_list, show_in_dashboard, dropdown_choice) VALUES (:company_id, :name, :attribute_entity, :field_type, :is_required, :show_in_list, :show_in_dashboard, :dropdown_choice)",
            [
                'company_id' => $this->currentCompanyId,
                'name' => $name,
                'attribute_entity' => $attributeEntity,
                'field_type' => $fieldType,
                'is_required' => $isRequired ? 1 : 0,
                'show_in_list' => $showInList ? 1 : 0,
                'show_in_dashboard' => $showInDashboard ? 1 : 0,
                'dropdown_choice' => json_encode($dropdownChoice)
            ]
        );

        $this->flashSession->success($this->locale->t('flash.attribute_created'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/custom-attributes'));
    }

    public function customAttributesEditAction()
    {
        $id = (int)$this->dispatcher->getParam('id');

        $attribute = $this->db->fetchOne(
            "SELECT id, name, attribute_entity, field_type, is_required, show_in_list, show_in_dashboard, dropdown_choice FROM custom_attributes WHERE id = :id AND company_id = :company_id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => $id, 'company_id' => $this->currentCompanyId]
        );

        if (!$attribute) {
            $this->flashSession->error($this->locale->t('flash.attribute_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/custom-attributes'));
            return;
        }

        $this->view->setVar('title', $this->locale->t('settings.custom_attributes.edit.title'));
        $this->view->setVar('mode', 'edit');
        $this->view->setVar('attribute', $attribute);
        $this->view->pick('dashboard/settings/custom-attributes-action');
    }

    public function customAttributesUpdateAction()
    {
        $id = (int)$this->dispatcher->getParam('id');
        $name = trim($this->request->getPost('name', 'string', ''));
        $attributeEntity = $this->request->getPost('attribute_entity', 'string', '');
        $fieldType = $this->request->getPost('field_type', 'string', 'text');
        $isRequired = $this->request->getPost('is_required', 'int', 0);
        $showInList = $this->request->getPost('show_in_list', 'int', 0);
        $showInDashboard = $this->request->getPost('show_in_dashboard', 'int', 0);

        $dropdownOptionsRaw = $this->request->getPost('dropdown_options', null, '[]');
        $dropdownChoice = json_decode($dropdownOptionsRaw, true);
        if (!is_array($dropdownChoice)) {
            $dropdownChoice = [];
        }
        $dropdownChoice = array_values(array_filter(array_map('trim', $dropdownChoice)));

        if (empty($name) || empty($attributeEntity)) {
            $this->flashSession->error($this->locale->t('flash.attribute_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/custom-attributes/edit/' . $id));
            return;
        }

        $this->db->execute(
            "UPDATE custom_attributes SET name = :name, attribute_entity = :attribute_entity, field_type = :field_type, is_required = :is_required, show_in_list = :show_in_list, show_in_dashboard = :show_in_dashboard, dropdown_choice = :dropdown_choice WHERE id = :id AND company_id = :company_id",
            [
                'id' => $id,
                'company_id' => $this->currentCompanyId,
                'name' => $name,
                'attribute_entity' => $attributeEntity,
                'field_type' => $fieldType,
                'is_required' => $isRequired ? 1 : 0,
                'show_in_list' => $showInList ? 1 : 0,
                'show_in_dashboard' => $showInDashboard ? 1 : 0,
                'dropdown_choice' => json_encode($dropdownChoice)
            ]
        );

        $this->flashSession->success($this->locale->t('flash.attribute_updated'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/custom-attributes'));
    }

    public function customAttributesDeleteAction()
    {
        $id = (int)$this->dispatcher->getParam('id');

        $this->db->execute(
            "DELETE FROM custom_attributes WHERE id = :id AND company_id = :company_id",
            ['id' => $id, 'company_id' => $this->currentCompanyId]
        );

        $this->flashSession->success($this->locale->t('flash.attribute_deleted'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/custom-attributes'));
    }

    /**
     * Admin Users
     */
    private function isSuperAdmin(): bool
    {
        $user = $this->session->get('auth');
        return ($user['role'] ?? '') === 'Super Admin';
    }

    public function adminUsersAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.admin_users.title'));

        $adminUsers = $this->db->fetchAll(
            "SELECT id, name, email, role, created_at FROM admin_users ORDER BY name ASC, email ASC",
            Phalcon\Db\Enum::FETCH_ASSOC
        );

        $userIds = array_column($adminUsers, 'id');
        $companyMappings = [];
        if (!empty($userIds)) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $rows = $this->db->fetchAll(
                "SELECT cum.admin_user_id, c.id AS company_id, c.name AS company_name " .
                "FROM company_user_map cum " .
                "JOIN companies c ON c.id = cum.company_id " .
                "WHERE cum.admin_user_id IN (" . $placeholders . ") " .
                "ORDER BY c.name ASC",
                Phalcon\Db\Enum::FETCH_ASSOC,
                $userIds
            );
            foreach ($rows as $row) {
                $companyMappings[(int)$row['admin_user_id']][] = [
                    'id' => (int)$row['company_id'],
                    'name' => $row['company_name']
                ];
            }
        }

        foreach ($adminUsers as &$user) {
            $user['companies'] = $companyMappings[(int)$user['id']] ?? [];
            $user['created_at_display'] = !empty($user['created_at'])
                ? date('M j, Y', strtotime($user['created_at']))
                : '-';
        }
        unset($user);

        $this->view->setVar('adminUsers', $adminUsers);
        $this->view->setVar('isSuperAdmin', $this->isSuperAdmin());
        $this->view->pick('dashboard/settings/admin-users-list');
    }

    public function adminUsersCreateAction()
    {
        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_create_admin'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
            return;
        }

        $companies = $this->db->fetchAll(
            "SELECT id, name FROM companies ORDER BY name ASC",
            Phalcon\Db\Enum::FETCH_ASSOC
        );

        $this->view->setVar('title', $this->locale->t('settings.admin_users.new.title'));
        $this->view->setVar('mode', 'create');
        $this->view->setVar('companies', $companies);
        $this->view->setVar('selectedCompanyIds', []);
        $this->view->pick('dashboard/settings/admin-users-action');
    }

    public function adminUsersStoreAction()
    {
        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_create_admin'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
            return;
        }

        $name = trim($this->request->getPost('name', 'string', ''));
        $email = trim($this->request->getPost('email', 'string', ''));
        $role = $this->request->getPost('role', 'string', '');
        $companyIds = $this->request->getPost('company_ids', null, []);
        if (!is_array($companyIds)) {
            $companyIds = [];
        }
        $companyIds = array_filter(array_map('intval', $companyIds));

        if (empty($name) || empty($email) || empty($role)) {
            $this->flashSession->error($this->locale->t('flash.admin_user_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users/create'));
            return;
        }

        if (!in_array($role, ['Super Admin', 'Admin', 'Member'], true)) {
            $this->flashSession->error($this->locale->t('flash.admin_user_invalid_role'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users/create'));
            return;
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM admin_users WHERE email = :email LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['email' => $email]
        );
        if ($existing) {
            $this->flashSession->error($this->locale->t('flash.admin_user_duplicate'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users/create'));
            return;
        }

        // Set the initial password to "password" and flag the account so the
        // user is forced to choose a new password on first login (see
        // LoginController::authenticateAction and IndexController::changePasswordAction).
        $passwordHash = LoginController::encryptPass('password');

        $this->db->execute(
            "INSERT INTO admin_users (name, email, password_hash, role, must_change_password, created_at) VALUES (:name, :email, :password_hash, :role, 1, NOW())",
            [
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
            ]
        );

        $newUserId = (int)$this->db->lastInsertId();

        // Map the new admin user to the current tenant so tenant-scoped login
        // (TenantResolver::userHasTenantAccess) succeeds. Role is always
        // tenant_admin here.
        // INSERT IGNORE guards against the rare case of a pre-existing mapping row.
        $this->db->execute(
            "INSERT IGNORE INTO tenant_user_map (admin_user_id, tenant_id, role, created_at)
             VALUES (:admin_user_id, :tenant_id, 'tenant_admin', NOW())",
            [
                'admin_user_id' => $newUserId,
                'tenant_id'     => $this->currentTenantId,
            ]
        );

        foreach ($companyIds as $companyId) {
            $this->db->execute(
                "INSERT INTO company_user_map (admin_user_id, company_id, role) VALUES (:admin_user_id, :company_id, :role)",
                [
                    'admin_user_id' => $newUserId,
                    'company_id' => $companyId,
                    'role' => $role
                ]
            );
        }

        $this->flashSession->success($this->locale->t('flash.admin_user_created_default_password'));

        $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
    }

    public function adminUsersEditAction()
    {
        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_edit_admin'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
            return;
        }

        $id = (int)$this->dispatcher->getParam('id');

        $adminUser = $this->db->fetchOne(
            "SELECT id, name, email, role FROM admin_users WHERE id = :id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => $id]
        );

        if (!$adminUser) {
            $this->flashSession->error($this->locale->t('flash.admin_user_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
            return;
        }

        $companies = $this->db->fetchAll(
            "SELECT id, name FROM companies ORDER BY name ASC",
            Phalcon\Db\Enum::FETCH_ASSOC
        );

        $selectedCompanyIds = [];
        $mappings = $this->db->fetchAll(
            "SELECT company_id FROM company_user_map WHERE admin_user_id = :admin_user_id",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['admin_user_id' => $id]
        );
        foreach ($mappings as $mapping) {
            $selectedCompanyIds[] = (int)$mapping['company_id'];
        }

        $this->view->setVar('title', $this->locale->t('settings.admin_users.edit.title'));
        $this->view->setVar('mode', 'edit');
        $this->view->setVar('adminUser', $adminUser);
        $this->view->setVar('companies', $companies);
        $this->view->setVar('selectedCompanyIds', $selectedCompanyIds);
        $this->view->pick('dashboard/settings/admin-users-action');
    }

    public function adminUsersUpdateAction()
    {
        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_update_admin'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
            return;
        }

        $id = (int)$this->dispatcher->getParam('id');

        $name = trim($this->request->getPost('name', 'string', ''));
        $email = trim($this->request->getPost('email', 'string', ''));
        $role = $this->request->getPost('role', 'string', '');
        $companyIds = $this->request->getPost('company_ids', null, []);
        if (!is_array($companyIds)) {
            $companyIds = [];
        }
        $companyIds = array_filter(array_map('intval', $companyIds));

        if (empty($name) || empty($email) || empty($role)) {
            $this->flashSession->error($this->locale->t('flash.admin_user_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users/edit/' . $id));
            return;
        }

        if (!in_array($role, ['Super Admin', 'Admin', 'Member'], true)) {
            $this->flashSession->error($this->locale->t('flash.admin_user_invalid_role'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users/edit/' . $id));
            return;
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM admin_users WHERE email = :email AND id != :id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['email' => $email, 'id' => $id]
        );
        if ($existing) {
            $this->flashSession->error($this->locale->t('flash.admin_user_duplicate'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users/edit/' . $id));
            return;
        }

        $this->db->execute(
            "UPDATE admin_users SET name = :name, email = :email, role = :role WHERE id = :id",
            [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'role' => $role
            ]
        );

        $this->db->execute(
            "DELETE FROM company_user_map WHERE admin_user_id = :admin_user_id",
            ['admin_user_id' => $id]
        );

        foreach ($companyIds as $companyId) {
            $this->db->execute(
                "INSERT INTO company_user_map (admin_user_id, company_id, role) VALUES (:admin_user_id, :company_id, :role)",
                [
                    'admin_user_id' => $id,
                    'company_id' => $companyId,
                    'role' => $role
                ]
            );
        }

        // Keep the tenant mapping in sync: ensure the user is mapped to the
        // current tenant as a tenant_admin (idempotent upsert).
        $this->db->execute(
            "INSERT INTO tenant_user_map (admin_user_id, tenant_id, role, created_at)
             VALUES (:admin_user_id, :tenant_id, 'tenant_admin', NOW())
             ON DUPLICATE KEY UPDATE role = 'tenant_admin'",
            [
                'admin_user_id' => $id,
                'tenant_id'     => $this->currentTenantId,
            ]
        );

        $this->flashSession->success($this->locale->t('flash.admin_user_updated'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
    }

    public function adminUsersDeleteAction()
    {
        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_delete_admin'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
            return;
        }

        $id = (int)$this->dispatcher->getParam('id');

        $auth = $this->session->get('auth');
        if ((int)$auth['id'] === $id) {
            $this->flashSession->error($this->locale->t('flash.admin_user_self_delete'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
            return;
        }

        // Remove the tenant and company mappings before deleting the admin
        // user so no orphaned tenant_user_map / company_user_map rows remain.
        $this->db->execute(
            "DELETE FROM tenant_user_map WHERE admin_user_id = :admin_user_id",
            ['admin_user_id' => $id]
        );

        $this->db->execute(
            "DELETE FROM company_user_map WHERE admin_user_id = :admin_user_id",
            ['admin_user_id' => $id]
        );

        $this->db->execute(
            "DELETE FROM admin_users WHERE id = :id",
            ['id' => $id]
        );

        $this->flashSession->success($this->locale->t('flash.admin_user_deleted'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/admin-users'));
    }

    /**
     * Leave Policies
     */
    private function retiredLeavePoliciesAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.leave_policies.title'));

        $activeLanguage = $this->getEntityLanguage();

        if ($activeLanguage !== 'en') {
            $leaveTypes = $this->db->fetchAll(
                "SELECT lt.public_id, COALESCE(t.translation_value, lt.name) AS name, lt.default_allowance_minutes, lt.workday_hours, lt.is_active, lt.is_toil, lt.year_end_mode, lt.allow_hourly, lt.allow_whole_day, lt.allow_multi_day " .
                "FROM leave_types lt " .
                "LEFT JOIN translations t ON t.company_id = lt.company_id AND t.target_table = 'leave_types' AND t.target_column = 'name' AND t.target_id = lt.id AND t.language_code = :lang " .
                "WHERE lt.company_id = :company_id ORDER BY name ASC",
                Phalcon\Db\Enum::FETCH_ASSOC,
                ['company_id' => $this->currentCompanyId, 'lang' => $activeLanguage]
            );
        } else {
            $leaveTypes = $this->db->fetchAll(
                "SELECT public_id, name, default_allowance_minutes, workday_hours, is_active, is_toil, year_end_mode, allow_hourly, allow_whole_day, allow_multi_day FROM leave_types WHERE company_id = :company_id ORDER BY name ASC",
                Phalcon\Db\Enum::FETCH_ASSOC,
                ['company_id' => $this->currentCompanyId]
            );
        }

        $this->view->setVar('leaveTypes', $leaveTypes);
        $this->view->setVar('isSuperAdmin', $this->isSuperAdmin());
        $this->view->pick('dashboard/settings/leave-policies');
    }

    private function retiredLeavePoliciesCreateAction()
    {
        $translationService = new TranslationService($this->db);
        $installedLanguages = $translationService->getInstalledLanguages($this->currentCompanyId);

        $this->view->setVar('title', $this->locale->t('settings.leave_policies.new.title'));
        $this->view->setVar('mode', 'create');
        $this->view->setVar('installedLanguages', $installedLanguages);
        $this->view->setVar('isSuperAdmin', $this->isSuperAdmin());
        $this->view->pick('dashboard/settings/leave-policies-action');
    }

    private function retiredLeavePoliciesStoreAction()
    {
        $name = trim($this->request->getPost('name', 'string', ''));
        $defaultAllowanceMinutes = (int)$this->request->getPost('default_allowance_minutes', 'int', 0);
        $workdayHours = (float)$this->request->getPost('workday_hours', 'float', 8.0);
        $isActive = (int)$this->request->getPost('is_active', 'int', 0);
        $isToil = (int)$this->request->getPost('is_toil', 'int', 0);
        $yearEndMode = $this->resolveYearEndModeFromRequest();
        [$allowHourly, $allowWholeDay, $allowMultiDay] = $this->resolveLeaveModeFlagsFromRequest();

        if (empty($name)) {
            $this->flashSession->error($this->locale->t('flash.leave_policy_name_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies/create'));
            return;
        }

        // Ensure at least one leave mode remains enabled so employees can
        // actually submit a request under this policy.
        if (!$allowHourly && !$allowWholeDay && !$allowMultiDay) {
            $allowHourly = true;
            $allowWholeDay = true;
            $allowMultiDay = true;
        }

        $publicId = LeaveTypes::generateUuid();

        $this->db->execute(
            "INSERT INTO leave_types (public_id, company_id, name, default_allowance_minutes, workday_hours, is_active, is_toil, year_end_mode, allow_hourly, allow_whole_day, allow_multi_day) VALUES (:public_id, :company_id, :name, :default_allowance_minutes, :workday_hours, :is_active, :is_toil, :year_end_mode, :allow_hourly, :allow_whole_day, :allow_multi_day)",
            [
                'public_id' => $publicId,
                'company_id' => $this->currentCompanyId,
                'name' => $name,
                'default_allowance_minutes' => max(0, $defaultAllowanceMinutes),
                'workday_hours' => max(0.5, $workdayHours),
                'is_active' => $isActive ? 1 : 0,
                'is_toil' => $isToil ? 1 : 0,
                'year_end_mode' => $yearEndMode,
                'allow_hourly' => $allowHourly ? 1 : 0,
                'allow_whole_day' => $allowWholeDay ? 1 : 0,
                'allow_multi_day' => $allowMultiDay ? 1 : 0,
            ]
        );

        $leaveTypeId = (int)$this->db->lastInsertId();
        $this->seedLeaveBalancesForPolicy($leaveTypeId, $this->currentCompanyId, max(0, $defaultAllowanceMinutes));

        $this->savePostedTranslations('leave_types', $leaveTypeId);

        $this->logActivity('create', 'leave_types', $leaveTypeId, null, [
            'name' => $name,
            'default_allowance_minutes' => max(0, $defaultAllowanceMinutes),
            'workday_hours' => max(0.5, $workdayHours),
            'is_active' => $isActive ? 1 : 0,
            'is_toil' => $isToil ? 1 : 0,
            'year_end_mode' => $yearEndMode,
            'allow_hourly' => $allowHourly ? 1 : 0,
            'allow_whole_day' => $allowWholeDay ? 1 : 0,
            'allow_multi_day' => $allowMultiDay ? 1 : 0,
        ]);

        $this->flashSession->success($this->locale->t('flash.leave_policy_created'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies'));
    }

    private function retiredLeavePoliciesEditAction()
    {
        $publicId = $this->dispatcher->getParam('id');
        $leaveType = $this->db->fetchOne(
            "SELECT id, public_id, name, default_allowance_minutes, workday_hours, is_active, is_toil, year_end_mode, allow_hourly, allow_whole_day, allow_multi_day FROM leave_types WHERE public_id = :public_id AND company_id = :company_id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['public_id' => $publicId, 'company_id' => $this->currentCompanyId]
        );

        if (!$leaveType) {
            $this->flashSession->error($this->locale->t('flash.leave_policy_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies'));
            return;
        }

        $internalId = (int)$leaveType['id'];

        $translationService = new TranslationService($this->db);
        $installedLanguages = $translationService->getInstalledLanguages($this->currentCompanyId);
        $existingTranslations = [];
        if (!empty($installedLanguages) && $internalId) {
            $existingTranslations = $translationService->getTranslationsForRecord(
                $this->currentCompanyId,
                'leave_types',
                $internalId
            );
        }

        $this->view->setVar('title', $this->locale->t('settings.leave_policies.edit.title'));
        $this->view->setVar('mode', 'edit');
        $this->view->setVar('leaveType', $leaveType);
        $this->view->setVar('installedLanguages', $installedLanguages);
        $this->view->setVar('existingTranslations', $existingTranslations);
        $this->view->setVar('leaveTypeInternalId', $internalId);

        // Load allowance rules for this leave type
        $allowanceRules = $this->db->fetchAll(
            "SELECT lar.id, lar.public_id, lar.position_id, lar.job_level, lar.min_tenure_months,
                    lar.max_tenure_months, lar.allowance_minutes, lar.priority, lar.is_active,
                    p.job_title AS position_title
             FROM leave_allowance_rules lar
             LEFT JOIN positions p ON p.id = lar.position_id
             WHERE lar.company_id = :company_id AND lar.leave_type_id = :leave_type_id
             ORDER BY lar.priority DESC, lar.id ASC",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId, 'leave_type_id' => $internalId]
        );

        // Load positions for dropdown
        $positions = $this->db->fetchAll(
            "SELECT id, job_title, department FROM positions WHERE company_id = :company_id ORDER BY job_title ASC",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId]
        );

        // Load job levels for dropdown (tenant-global + company-specific)
        $jobLevels = $this->db->fetchAll(
            "SELECT code, name FROM job_levels WHERE company_id IS NULL OR company_id = :company_id ORDER BY sort_order ASC, code ASC",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId]
        );

        $this->view->setVar('allowanceRules', $allowanceRules);
        $this->view->setVar('positions', $positions);
        $this->view->setVar('jobLevels', $jobLevels);
        $this->view->setVar('workdayHours', (float)$leaveType['workday_hours']);
        $this->view->setVar('isSuperAdmin', $this->isSuperAdmin());

        $this->view->pick('dashboard/settings/leave-policies-action');
    }

    private function retiredLeavePoliciesUpdateAction()
    {
        $publicId = $this->dispatcher->getParam('id');
        $name = trim($this->request->getPost('name', 'string', ''));
        $defaultAllowanceMinutes = (int)$this->request->getPost('default_allowance_minutes', 'int', 0);
        $workdayHours = (float)$this->request->getPost('workday_hours', 'float', 8.0);
        $isActive = (int)$this->request->getPost('is_active', 'int', 0);
        $isToil = (int)$this->request->getPost('is_toil', 'int', 0);
        $yearEndMode = $this->resolveYearEndModeFromRequest();
        [$allowHourly, $allowWholeDay, $allowMultiDay] = $this->resolveLeaveModeFlagsFromRequest();

        if (empty($name)) {
            $this->flashSession->error($this->locale->t('flash.leave_policy_name_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies/edit/' . $publicId));
            return;
        }

        // Ensure at least one leave mode remains enabled so employees can
        // actually submit a request under this policy.
        if (!$allowHourly && !$allowWholeDay && !$allowMultiDay) {
            $allowHourly = true;
            $allowWholeDay = true;
            $allowMultiDay = true;
        }

        $this->db->execute(
            "UPDATE leave_types SET name = :name, default_allowance_minutes = :default_allowance_minutes, workday_hours = :workday_hours, is_active = :is_active, is_toil = :is_toil, year_end_mode = :year_end_mode, allow_hourly = :allow_hourly, allow_whole_day = :allow_whole_day, allow_multi_day = :allow_multi_day WHERE public_id = :public_id AND company_id = :company_id",
            [
                'public_id' => $publicId,
                'company_id' => $this->currentCompanyId,
                'name' => $name,
                'default_allowance_minutes' => max(0, $defaultAllowanceMinutes),
                'workday_hours' => max(0.5, $workdayHours),
                'is_active' => $isActive ? 1 : 0,
                'is_toil' => $isToil ? 1 : 0,
                'year_end_mode' => $yearEndMode,
                'allow_hourly' => $allowHourly ? 1 : 0,
                'allow_whole_day' => $allowWholeDay ? 1 : 0,
                'allow_multi_day' => $allowMultiDay ? 1 : 0,
            ]
        );

        $internalId = LeaveTypes::getIdByUuid($publicId, $this->currentCompanyId);
        if ($internalId) {
            $this->savePostedTranslations('leave_types', $internalId);
            $this->logActivity('update', 'leave_types', $internalId, null, [
                'name' => $name,
                'default_allowance_minutes' => max(0, $defaultAllowanceMinutes),
                'workday_hours' => max(0.5, $workdayHours),
                'is_active' => $isActive ? 1 : 0,
                'is_toil' => $isToil ? 1 : 0,
                'year_end_mode' => $yearEndMode,
                'allow_hourly' => $allowHourly ? 1 : 0,
                'allow_whole_day' => $allowWholeDay ? 1 : 0,
                'allow_multi_day' => $allowMultiDay ? 1 : 0,
            ]);
        }

        $this->flashSession->success($this->locale->t('flash.leave_policy_updated'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies'));
    }

    private function retiredLeavePoliciesDeleteAction()
    {
        $publicId = $this->dispatcher->getParam('id');

        $leaveType = $this->db->fetchOne(
            "SELECT id, name FROM leave_types WHERE public_id = :public_id AND company_id = :company_id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['public_id' => $publicId, 'company_id' => $this->currentCompanyId]
        );

        $this->db->execute(
            "DELETE FROM leave_types WHERE public_id = :public_id AND company_id = :company_id",
            ['public_id' => $publicId, 'company_id' => $this->currentCompanyId]
        );

        if ($leaveType) {
            $this->logActivity('delete', 'leave_types', (int)$leaveType['id'], ['name' => $leaveType['name']], null);
        }

        $this->flashSession->success($this->locale->t('flash.leave_policy_deleted'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies'));
    }

    /**
     * Resolve the year_end_mode POST value, enforcing Super Admin permission
     * and validating against the allowed enum values. Non-Super-Admin users
     * always get the default 'reset' (they cannot change the setting).
     */
    private function resolveYearEndModeFromRequest(): string
    {
        if (!$this->isSuperAdmin()) {
            return LeaveTypes::YEAR_END_RESET;
        }

        $mode = trim((string)$this->request->getPost('year_end_mode', 'string', ''));
        return in_array($mode, [LeaveTypes::YEAR_END_RESET, LeaveTypes::YEAR_END_CARRY_FORWARD], true)
            ? $mode
            : LeaveTypes::YEAR_END_RESET;
    }

    /**
     * Resolve the per-mode enable flags (allow_hourly / allow_whole_day /
     * allow_multi_day) from the POST request. Each is a checkbox, so it is
     * "on" only when the corresponding field is present in the POST body.
     *
     * @return array{0:bool,1:bool,2:bool} [allowHourly, allowWholeDay, allowMultiDay]
     */
    private function resolveLeaveModeFlagsFromRequest(): array
    {
        $allowHourly    = $this->request->hasPost('allow_hourly');
        $allowWholeDay  = $this->request->hasPost('allow_whole_day');
        $allowMultiDay  = $this->request->hasPost('allow_multi_day');

        return [$allowHourly, $allowWholeDay, $allowMultiDay];
    }

    /**
     * POST /dashboard/settings/leave-policies/{id}/year-end-mode
     *
     * Inline AJAX endpoint used by the Leave Policies list page. Super Admins
     * can toggle a policy's year-end behaviour between "carry_forward" and
     * "reset" without opening the full edit form. Returns JSON.
     */
    private function retiredLeavePoliciesYearEndModeUpdateAction()
    {
        if (!$this->isSuperAdmin()) {
            $this->response->setStatusCode(403, 'Forbidden');
            $this->response->setJsonContent(['success' => false, 'message' => 'Only Super Admins can change the year-end mode.']);
            return $this->response;
        }

        $publicId = $this->dispatcher->getParam('id');
        $mode = trim((string)$this->request->getPost('year_end_mode', 'string', ''));

        if (!in_array($mode, [LeaveTypes::YEAR_END_RESET, LeaveTypes::YEAR_END_CARRY_FORWARD], true)) {
            $this->response->setStatusCode(400, 'Bad Request');
            $this->response->setJsonContent(['success' => false, 'message' => 'Invalid year-end mode.']);
            return $this->response;
        }

        $leaveType = $this->db->fetchOne(
            "SELECT id, name, year_end_mode FROM leave_types WHERE public_id = :public_id AND company_id = :company_id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['public_id' => $publicId, 'company_id' => $this->currentCompanyId]
        );

        if (!$leaveType) {
            $this->response->setStatusCode(404, 'Not Found');
            $this->response->setJsonContent(['success' => false, 'message' => 'Leave policy not found.']);
            return $this->response;
        }

        $internalId = (int)$leaveType['id'];
        $oldMode = $leaveType['year_end_mode'];

        if ($oldMode !== $mode) {
            $this->db->execute(
                "UPDATE leave_types SET year_end_mode = :year_end_mode WHERE id = :id AND company_id = :company_id",
                [
                    'year_end_mode' => $mode,
                    'id' => $internalId,
                    'company_id' => $this->currentCompanyId,
                ]
            );

            $this->logActivity('update_year_end_mode', 'leave_types', $internalId, ['year_end_mode' => $oldMode], ['year_end_mode' => $mode]);
        }

        $this->response->setJsonContent([
            'success' => true,
            'year_end_mode' => $mode,
            'label' => $mode === LeaveTypes::YEAR_END_CARRY_FORWARD ? 'Carry Forward' : 'Reset',
        ]);
        return $this->response;
    }

    // ──────────────────────────────────────────────────────────────────
    //  Leave Allowance Rules (Position & Tenure-Based)
    // ──────────────────────────────────────────────────────────────────

    /**
     * POST /dashboard/settings/leave-policies/{id}/rules/store
     */
    private function retiredLeaveAllowanceRulesStoreAction()
    {
        $publicId = $this->dispatcher->getParam('id');

        $leaveType = $this->db->fetchOne(
            "SELECT id FROM leave_types WHERE public_id = :public_id AND company_id = :company_id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['public_id' => $publicId, 'company_id' => $this->currentCompanyId]
        );

        if (!$leaveType) {
            $this->flashSession->error($this->locale->t('flash.leave_policy_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies'));
            return;
        }

        $leaveTypeId = (int)$leaveType['id'];
        $positionId = $this->request->getPost('position_id', 'int', 0);
        $jobLevel = trim($this->request->getPost('job_level', 'string', ''));
        $minTenureMonths = $this->request->getPost('min_tenure_months', 'int', null);
        $maxTenureMonths = $this->request->getPost('max_tenure_months', 'int', null);
        $allowanceDays = (float)$this->request->getPost('allowance_days', 'float', 0);
        $allowanceHours = (int)$this->request->getPost('allowance_hours', 'int', 0);
        $allowanceMinutes = (int)$this->request->getPost('allowance_minutes', 'int', 0);
        $priority = (int)$this->request->getPost('priority', 'int', 0);
        $isActive = (int)$this->request->getPost('is_active', 'int', 0);

        // Get workday_hours for this leave type to convert days to minutes
        $workdayHours = (float)($this->db->fetchOne(
            "SELECT workday_hours FROM leave_types WHERE id = :id",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => $leaveTypeId]
        )['workday_hours'] ?? 8.0);

        $totalMinutes = (int) round($allowanceDays * $workdayHours * 60) + ($allowanceHours * 60) + $allowanceMinutes;

        // Convert empty strings to null for nullable fields
        $positionIdValue = $positionId > 0 ? $positionId : null;
        $jobLevelValue = $jobLevel !== '' ? $jobLevel : null;
        $minTenureValue = ($minTenureMonths !== null && $minTenureMonths >= 0) ? (int)$minTenureMonths : null;
        $maxTenureValue = ($maxTenureMonths !== null && $maxTenureMonths >= 0) ? (int)$maxTenureMonths : null;

        $this->db->execute(
            "INSERT INTO leave_allowance_rules (public_id, company_id, leave_type_id, position_id, job_level, min_tenure_months, max_tenure_months, allowance_minutes, priority, is_active) VALUES (:public_id, :company_id, :leave_type_id, :position_id, :job_level, :min_tenure_months, :max_tenure_months, :allowance_minutes, :priority, :is_active)",
            [
                'public_id' => LeaveAllowanceRules::generateUuid(),
                'company_id' => $this->currentCompanyId,
                'leave_type_id' => $leaveTypeId,
                'position_id' => $positionIdValue,
                'job_level' => $jobLevelValue,
                'min_tenure_months' => $minTenureValue,
                'max_tenure_months' => $maxTenureValue,
                'allowance_minutes' => max(0, $totalMinutes),
                'priority' => $priority,
                'is_active' => $isActive ? 1 : 0,
            ]
        );

        $ruleId = (int)$this->db->lastInsertId();

        $this->logActivity('create', 'leave_allowance_rules', $ruleId, null, [
            'leave_type_id' => $leaveTypeId,
            'position_id' => $positionIdValue,
            'job_level' => $jobLevelValue,
            'min_tenure_months' => $minTenureValue,
            'max_tenure_months' => $maxTenureValue,
            'allowance_minutes' => max(0, $totalMinutes),
            'priority' => $priority,
        ]);

        $this->flashSession->success($this->locale->t('flash.allowance_rule_created'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies/edit/' . $publicId . '#allowance-rules'));
    }

    /**
     * POST /dashboard/settings/leave-policies/{id}/rules/update/{ruleId}
     */
    private function retiredLeaveAllowanceRulesUpdateAction()
    {
        $publicId = $this->dispatcher->getParam('id');
        $rulePublicId = $this->dispatcher->getParam('ruleId');

        $leaveType = $this->db->fetchOne(
            "SELECT id, workday_hours FROM leave_types WHERE public_id = :public_id AND company_id = :company_id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['public_id' => $publicId, 'company_id' => $this->currentCompanyId]
        );

        if (!$leaveType) {
            $this->flashSession->error($this->locale->t('flash.leave_policy_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies'));
            return;
        }

        $rule = $this->db->fetchOne(
            "SELECT id FROM leave_allowance_rules WHERE public_id = :public_id AND company_id = :company_id AND leave_type_id = :leave_type_id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['public_id' => $rulePublicId, 'company_id' => $this->currentCompanyId, 'leave_type_id' => $leaveType['id']]
        );

        if (!$rule) {
            $this->flashSession->error($this->locale->t('flash.allowance_rule_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies/edit/' . $publicId));
            return;
        }

        $leaveTypeId = (int)$leaveType['id'];
        $positionId = $this->request->getPost('position_id', 'int', 0);
        $jobLevel = trim($this->request->getPost('job_level', 'string', ''));
        $minTenureMonths = $this->request->getPost('min_tenure_months', 'int', null);
        $maxTenureMonths = $this->request->getPost('max_tenure_months', 'int', null);
        $allowanceDays = (float)$this->request->getPost('allowance_days', 'float', 0);
        $allowanceHours = (int)$this->request->getPost('allowance_hours', 'int', 0);
        $allowanceMinutes = (int)$this->request->getPost('allowance_minutes', 'int', 0);
        $priority = (int)$this->request->getPost('priority', 'int', 0);
        $isActive = (int)$this->request->getPost('is_active', 'int', 0);

        $workdayHours = (float)$leaveType['workday_hours'];
        $totalMinutes = (int) round($allowanceDays * $workdayHours * 60) + ($allowanceHours * 60) + $allowanceMinutes;

        $positionIdValue = $positionId > 0 ? $positionId : null;
        $jobLevelValue = $jobLevel !== '' ? $jobLevel : null;
        $minTenureValue = ($minTenureMonths !== null && $minTenureMonths >= 0) ? (int)$minTenureMonths : null;
        $maxTenureValue = ($maxTenureMonths !== null && $maxTenureMonths >= 0) ? (int)$maxTenureMonths : null;

        $this->db->execute(
            "UPDATE leave_allowance_rules SET position_id = :position_id, job_level = :job_level, min_tenure_months = :min_tenure_months, max_tenure_months = :max_tenure_months, allowance_minutes = :allowance_minutes, priority = :priority, is_active = :is_active WHERE id = :id AND company_id = :company_id",
            [
                'id' => (int)$rule['id'],
                'company_id' => $this->currentCompanyId,
                'position_id' => $positionIdValue,
                'job_level' => $jobLevelValue,
                'min_tenure_months' => $minTenureValue,
                'max_tenure_months' => $maxTenureValue,
                'allowance_minutes' => max(0, $totalMinutes),
                'priority' => $priority,
                'is_active' => $isActive ? 1 : 0,
            ]
        );

        $this->logActivity('update', 'leave_allowance_rules', (int)$rule['id'], null, [
            'position_id' => $positionIdValue,
            'job_level' => $jobLevelValue,
            'allowance_minutes' => max(0, $totalMinutes),
            'priority' => $priority,
        ]);

        $this->flashSession->success($this->locale->t('flash.allowance_rule_updated'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies/edit/' . $publicId . '#allowance-rules'));
    }

    /**
     * POST /dashboard/settings/leave-policies/{id}/rules/delete/{ruleId}
     */
    private function retiredLeaveAllowanceRulesDeleteAction()
    {
        $publicId = $this->dispatcher->getParam('id');
        $rulePublicId = $this->dispatcher->getParam('ruleId');

        $rule = $this->db->fetchOne(
            "SELECT lar.id FROM leave_allowance_rules lar
             INNER JOIN leave_types lt ON lt.id = lar.leave_type_id
             WHERE lar.public_id = :public_id AND lar.company_id = :company_id AND lt.public_id = :lt_public_id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['public_id' => $rulePublicId, 'company_id' => $this->currentCompanyId, 'lt_public_id' => $publicId]
        );

        if ($rule) {
            $this->db->execute(
                "DELETE FROM leave_allowance_rules WHERE public_id = :public_id AND company_id = :company_id",
                ['public_id' => $rulePublicId, 'company_id' => $this->currentCompanyId]
            );

            $this->logActivity('delete', 'leave_allowance_rules', (int)$rule['id'], null, null);
            $this->flashSession->success($this->locale->t('flash.allowance_rule_deleted'));
        } else {
            $this->flashSession->error($this->locale->t('flash.allowance_rule_not_found'));
        }

        $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies/edit/' . $publicId . '#allowance-rules'));
    }

    /**
     * POST /dashboard/settings/leave-policies/{id}/rules/recalculate
     *
     * Re-evaluates all employees' balances for this leave type using the
     * current allowance rules. Only updates balances that don't have any
     * used_minutes (to avoid overwriting balances that are in use).
     */
    private function retiredLeaveAllowanceRulesRecalculateAction()
    {
        $publicId = $this->dispatcher->getParam('id');

        $leaveType = $this->db->fetchOne(
            "SELECT id, name, default_allowance_minutes, workday_hours FROM leave_types WHERE public_id = :public_id AND company_id = :company_id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['public_id' => $publicId, 'company_id' => $this->currentCompanyId]
        );

        if (!$leaveType) {
            $this->flashSession->error($this->locale->t('flash.leave_policy_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies'));
            return;
        }

        $leaveTypeId = (int)$leaveType['id'];
        $defaultAllowance = (int)$leaveType['default_allowance_minutes'];
        $year = date('Y');

        $employees = $this->db->fetchAll(
            "SELECT id FROM employees WHERE company_id = :company_id",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId]
        );

        $updated = 0;
        $created = 0;

        foreach ($employees as $emp) {
            $employeeId = (int)$emp['id'];
            $calculatedMinutes = $this->calculateAllowanceForEmployee($leaveTypeId, $employeeId, $this->currentCompanyId, $defaultAllowance);

            $existing = $this->db->fetchOne(
                "SELECT id, used_minutes FROM leave_balances WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id AND year = :year AND company_id = :company_id LIMIT 1",
                Phalcon\Db\Enum::FETCH_ASSOC,
                [
                    'employee_id' => $employeeId,
                    'leave_type_id' => $leaveTypeId,
                    'year' => $year,
                    'company_id' => $this->currentCompanyId,
                ]
            );

            if ($existing) {
                // Only update allowance if no leave has been used yet
                if ((int)$existing['used_minutes'] === 0) {
                    $this->db->execute(
                        "UPDATE leave_balances SET allowance_minutes = :allowance_minutes WHERE id = :id",
                        [
                            'id' => (int)$existing['id'],
                            'allowance_minutes' => $calculatedMinutes,
                        ]
                    );
                    $updated++;
                }
            } else {
                $this->db->execute(
                    "INSERT INTO leave_balances (public_id, company_id, employee_id, leave_type_id, year, allowance_minutes, used_minutes) VALUES (:public_id, :company_id, :employee_id, :leave_type_id, :year, :allowance_minutes, 0)",
                    [
                        'public_id' => LeaveBalances::generateUuid(),
                        'company_id' => $this->currentCompanyId,
                        'employee_id' => $employeeId,
                        'leave_type_id' => $leaveTypeId,
                        'year' => $year,
                        'allowance_minutes' => $calculatedMinutes,
                    ]
                );
                $created++;
            }
        }

        $this->logActivity('recalculate', 'leave_allowance_rules', $leaveTypeId, null, [
            'leave_type_id' => $leaveTypeId,
            'employees_updated' => $updated,
            'employees_created' => $created,
        ]);

        $this->flashSession->success($this->locale->t('flash.balances_recalculated', ['created' => $created, 'updated' => $updated]));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/leave-policies/edit/' . $publicId . '#allowance-rules'));
    }

    /**
     * Rule evaluation engine.
     *
     * Calculates the allowance in minutes for a specific employee + leave type
     * based on position, job level, and tenure. Falls back to the leave type's
     * default_allowance_minutes if no rule matches.
     */
    private function calculateAllowanceForEmployee(int $leaveTypeId, int $employeeId, int $companyId, int $defaultAllowanceMinutes): int
    {
        // Load active rules for this leave type, sorted by priority DESC
        $rules = $this->db->fetchAll(
            "SELECT position_id, job_level, min_tenure_months, max_tenure_months, allowance_minutes
             FROM leave_allowance_rules
             WHERE company_id = :company_id AND leave_type_id = :leave_type_id AND is_active = 1
             ORDER BY priority DESC, id ASC",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $companyId, 'leave_type_id' => $leaveTypeId]
        );

        // No rules — use default
        if (empty($rules)) {
            return $defaultAllowanceMinutes;
        }

        // Get employee's current position
        $position = $this->db->fetchOne(
            "SELECT p.id, p.job_level
             FROM position_assignments pa
             INNER JOIN positions p ON p.id = pa.position_id
             WHERE pa.employee_id = :employee_id AND pa.end_date IS NULL
               AND p.company_id = :company_id
             ORDER BY pa.start_date DESC LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['employee_id' => $employeeId, 'company_id' => $companyId]
        );

        $employeePositionId = $position ? (int)$position['id'] : null;
        $employeeJobLevel = $position ? trim($position['job_level'] ?? '') : null;

        // Get employee's tenure in months from "Onboarded" milestone
        $joinDate = $this->db->fetchOne(
            "SELECT em.event_date FROM employee_milestones em
             INNER JOIN milestone_event_types met ON met.id = em.event_type_id
             WHERE em.employee_id = :employee_id
               AND em.company_id = :company_id
               AND met.name = 'Onboarded'
             ORDER BY em.event_date ASC LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['employee_id' => $employeeId, 'company_id' => $companyId]
        );

        $tenureMonths = 0;
        if ($joinDate && !empty($joinDate['event_date'])) {
            $diff = (strtotime(date('Y-m-d')) - strtotime($joinDate['event_date']));
            $tenureMonths = (int) floor($diff / (30 * 24 * 60 * 60));
            if ($tenureMonths < 0) {
                $tenureMonths = 0;
            }
        }

        // Evaluate rules in priority order — first match wins
        foreach ($rules as $rule) {
            // Check position match
            if ($rule['position_id'] !== null) {
                if ($employeePositionId !== (int)$rule['position_id']) {
                    continue;
                }
            }

            // Check job level match
            if ($rule['job_level'] !== null && $rule['job_level'] !== '') {
                if ($employeeJobLevel === null || strcasecmp($employeeJobLevel, trim($rule['job_level'])) !== 0) {
                    continue;
                }
            }

            // Check min tenure
            if ($rule['min_tenure_months'] !== null) {
                if ($tenureMonths < (int)$rule['min_tenure_months']) {
                    continue;
                }
            }

            // Check max tenure
            if ($rule['max_tenure_months'] !== null) {
                if ($tenureMonths > (int)$rule['max_tenure_months']) {
                    continue;
                }
            }

            // All conditions matched — use this rule's allowance
            return (int)$rule['allowance_minutes'];
        }

        // No rule matched — use default
        return $defaultAllowanceMinutes;
    }

    private function seedLeaveBalancesForPolicy(int $leaveTypeId, int $companyId, int $defaultAllowanceMinutes): void
    {
        $year = date('Y');
        $employees = $this->db->fetchAll(
            "SELECT id FROM employees WHERE company_id = :company_id",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $companyId]
        );

        foreach ($employees as $employee) {
            $employeeId = (int)$employee['id'];

            // Use rule engine to calculate per-employee allowance
            $allowanceMinutes = $this->calculateAllowanceForEmployee(
                $leaveTypeId,
                $employeeId,
                $companyId,
                $defaultAllowanceMinutes
            );

            $exists = $this->db->fetchOne(
                "SELECT id FROM leave_balances WHERE employee_id = :employee_id AND leave_type_id = :leave_type_id AND year = :year AND company_id = :company_id LIMIT 1",
                Phalcon\Db\Enum::FETCH_ASSOC,
                ['employee_id' => $employeeId, 'leave_type_id' => $leaveTypeId, 'year' => $year, 'company_id' => $companyId]
            );

            if (!$exists) {
                $this->db->execute(
                    "INSERT INTO leave_balances (public_id, company_id, employee_id, leave_type_id, year, allowance_minutes, used_minutes) VALUES (:public_id, :company_id, :employee_id, :leave_type_id, :year, :allowance_minutes, 0)",
                    [
                        'public_id' => LeaveBalances::generateUuid(),
                        'company_id' => $companyId,
                        'employee_id' => $employeeId,
                        'leave_type_id' => $leaveTypeId,
                        'year' => $year,
                        'allowance_minutes' => $allowanceMinutes
                    ]
                );
            }
        }
    }

}
