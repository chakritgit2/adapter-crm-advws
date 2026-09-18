<?php

use Phalcon\Db\Enum as DbEnum;

class TranslationController extends TenantBaseController
{
    protected TranslationService $translationService;

    public function initialize(): void
    {
        parent::initialize();
        $this->db = $this->getDI()->get('db');
        $this->translationService = new TranslationService($this->db);
    }

    // =========================================================
    // Language Management
    // =========================================================

    public function languagesAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.languages.title'));

        $languages = $this->translationService->getAllLanguages($this->currentCompanyId);
        $this->view->setVar('languages', $languages);
        $this->view->pick('dashboard/settings/languages');
    }

    public function languagesCreateAction()
    {
        $this->view->setVar('title', $this->locale->t('settings.languages.add'));
        $this->view->setVar('mode', 'create');
        $this->view->pick('dashboard/settings/languages-action');
    }

    public function languagesStoreAction()
    {
        $code = trim($this->request->getPost('language_code', 'string', ''));
        $name = trim($this->request->getPost('language_name', 'string', ''));

        if (empty($code) || empty($name)) {
            $this->flashSession->error($this->locale->t('settings.languages.required'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/languages/create'));
            return;
        }

        if (strlen($code) > 10) {
            $this->flashSession->error($this->locale->t('settings.languages.code_too_long'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/languages/create'));
            return;
        }

        $ok = $this->translationService->installLanguage($this->currentCompanyId, $code, $name);

        if (!$ok) {
            $this->flashSession->error($this->locale->t('settings.languages.duplicate'));
            $this->response->redirect($this->tenantUrl('/dashboard/settings/languages/create'));
            return;
        }

        $this->flashSession->success($this->locale->t('flash.language_installed'));
        $this->response->redirect($this->tenantUrl('/dashboard/settings/languages'));
    }

    public function languagesDeleteAction()
    {
        $languageId = (int)$this->dispatcher->getParam('id');

        $ok = $this->translationService->uninstallLanguage($this->currentCompanyId, $languageId);

        if (!$ok) {
            $this->flashSession->error($this->locale->t('settings.languages.not_found'));
        } else {
            $this->flashSession->success($this->locale->t('flash.language_removed'));
        }

        $this->response->redirect($this->tenantUrl('/dashboard/settings/languages'));
    }

    public function storeAction()
    {
        $this->view->disable();

        $data = json_decode($this->request->getRawBody(), true);

        if (!$data) {
            $data = $this->request->getPost();
        }

        $lang = trim($data['language_code'] ?? '');
        $table = trim($data['target_table'] ?? '');
        $column = trim($data['target_column'] ?? '');
        $targetId = (int)($data['target_id'] ?? 0);
        $value = trim($data['translation_value'] ?? '');

        if (empty($lang) || empty($table) || empty($column) || $targetId <= 0) {
            $this->response->setJsonContent(['success' => false, 'message' => $this->locale->t('translations.missing_fields')]);
            $this->response->send();
            return;
        }

        if ($value === '') {
            $this->response->setJsonContent(['success' => false, 'message' => $this->locale->t('translations.value_empty')]);
            $this->response->send();
            return;
        }

        $ok = $this->translationService->upsertTranslation(
            $this->currentCompanyId,
            $lang,
            $table,
            $column,
            $targetId,
            $value
        );

        if (!$ok) {
            $this->response->setJsonContent(['success' => false, 'message' => $this->locale->t('translations.save_failed')]);
            $this->response->send();
            return;
        }

        $this->response->setJsonContent(['success' => true, 'message' => $this->locale->t('translations.saved')]);
        $this->response->send();
    }

    public function deleteAction()
    {
        $this->view->disable();

        $translationId = (int)$this->dispatcher->getParam('id');

        $ok = $this->translationService->deleteTranslation($this->currentCompanyId, $translationId);

        if (!$ok) {
            $this->response->setJsonContent(['success' => false, 'message' => $this->locale->t('translations.not_found')]);
            $this->response->send();
            return;
        }

        $this->response->setJsonContent(['success' => true, 'message' => $this->locale->t('translations.deleted')]);
        $this->response->send();
    }

}
