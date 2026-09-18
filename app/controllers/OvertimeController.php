<?php

use Phalcon\Db\Enum as DbEnum;

class OvertimeController extends TenantBaseController
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
     * Overtime Policies - CRUD
     */
    public function policiesAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.overtime_policies.title'));

        $policies = $this->db->fetchAll(
            "SELECT public_id, name, multiplier, is_active FROM overtime_policies WHERE company_id = :company_id ORDER BY name ASC",
            DbEnum::FETCH_ASSOC,
            ['company_id' => $this->currentCompanyId]
        );

        $this->view->setVar('policies', $policies);
        $this->view->pick('dashboard/settings/overtime-policies');
    }

    public function policiesCreateAction()
    {
        $translationService = new TranslationService($this->db);
        $installedLanguages = $translationService->getInstalledLanguages($this->currentCompanyId);

        $this->view->setVar('title', $this->locale->t('settings.overtime_policies.new.title'));
        $this->view->setVar('mode', 'create');
        $this->view->setVar('installedLanguages', $installedLanguages);
        $this->view->pick('dashboard/settings/overtime-policies-action');
    }

    public function policiesStoreAction()
    {
        $name = trim($this->request->getPost('name', 'string', ''));
        $multiplier = (float)$this->request->getPost('multiplier', 'float', 1.00);
        $isActive = (int)$this->request->getPost('is_active', 'int', 0);

        if (empty($name)) {
            $this->flashSession->error($this->locale->t('flash.overtime_policy_name_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/overtime-policies/create'));
            return;
        }

        if ($multiplier <= 0) {
            $this->flashSession->error($this->locale->t('flash.overtime_multiplier_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/overtime-policies/create'));
            return;
        }

        $this->db->execute(
            "INSERT INTO overtime_policies (public_id, company_id, name, multiplier, is_active) VALUES (:public_id, :company_id, :name, :multiplier, :is_active)",
            [
                'public_id' => OvertimePolicies::generateUuid(),
                'company_id' => $this->currentCompanyId,
                'name' => $name,
                'multiplier' => $multiplier,
                'is_active' => $isActive ? 1 : 0
            ]
        );

        $this->savePostedTranslations('overtime_policies', (int)$this->db->lastInsertId());

        $this->flashSession->success($this->locale->t('flash.overtime_policy_created'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/overtime-policies'));
    }

    public function policiesEditAction()
    {
        $publicId = $this->dispatcher->getParam('id');
        $policy = $this->db->fetchOne(
            "SELECT id, public_id, name, multiplier, is_active FROM overtime_policies WHERE public_id = :public_id AND company_id = :company_id LIMIT 1",
            DbEnum::FETCH_ASSOC,
            ['public_id' => $publicId, 'company_id' => $this->currentCompanyId]
        );

        if (!$policy) {
            $this->flashSession->error($this->locale->t('flash.overtime_policy_not_found'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/overtime-policies'));
            return;
        }

        $internalId = (int)$policy['id'];

        $translationService = new TranslationService($this->db);
        $installedLanguages = $translationService->getInstalledLanguages($this->currentCompanyId);
        $existingTranslations = [];
        if (!empty($installedLanguages) && $internalId) {
            $existingTranslations = $translationService->getTranslationsForRecord(
                $this->currentCompanyId,
                'overtime_policies',
                $internalId
            );
        }

        $this->view->setVar('title', $this->locale->t('settings.overtime_policies.edit.title'));
        $this->view->setVar('mode', 'edit');
        $this->view->setVar('policy', $policy);
        $this->view->setVar('installedLanguages', $installedLanguages);
        $this->view->setVar('existingTranslations', $existingTranslations);
        $this->view->setVar('overtimePolicyInternalId', $internalId);
        $this->view->pick('dashboard/settings/overtime-policies-action');
    }

    public function policiesUpdateAction()
    {
        $publicId = $this->dispatcher->getParam('id');
        $name = trim($this->request->getPost('name', 'string', ''));
        $multiplier = (float)$this->request->getPost('multiplier', 'float', 1.00);
        $isActive = (int)$this->request->getPost('is_active', 'int', 0);

        if (empty($name)) {
            $this->flashSession->error($this->locale->t('flash.overtime_policy_name_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/overtime-policies/edit/' . $publicId));
            return;
        }

        if ($multiplier <= 0) {
            $this->flashSession->error($this->locale->t('flash.overtime_multiplier_required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/overtime-policies/edit/' . $publicId));
            return;
        }

        $this->db->execute(
            "UPDATE overtime_policies SET name = :name, multiplier = :multiplier, is_active = :is_active WHERE public_id = :public_id AND company_id = :company_id",
            [
                'public_id' => $publicId,
                'company_id' => $this->currentCompanyId,
                'name' => $name,
                'multiplier' => $multiplier,
                'is_active' => $isActive ? 1 : 0
            ]
        );

        $this->flashSession->success($this->locale->t('flash.overtime_policy_updated'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/overtime-policies'));
    }

    public function policiesDeleteAction()
    {
        $publicId = $this->dispatcher->getParam('id');

        $this->db->execute(
            "UPDATE overtime_policies SET is_active = 0 WHERE public_id = :public_id AND company_id = :company_id",
            ['public_id' => $publicId, 'company_id' => $this->currentCompanyId]
        );

        $this->flashSession->success($this->locale->t('flash.overtime_policy_deactivated'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/overtime-policies'));
    }

}
