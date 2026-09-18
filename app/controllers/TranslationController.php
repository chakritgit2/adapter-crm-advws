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
