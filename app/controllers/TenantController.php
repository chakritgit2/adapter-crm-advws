<?php

declare(strict_types=1);

use Phalcon\Db\Enum as DbEnum;
use Phalcon\Http\Response;

class TenantController extends TenantBaseController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->db = $this->getDI()->get('db');
    }

    /**
     * Handle company-scoped paths (e.g. /dashboard/settings) accessed at
     * the tenant level without a company_slug. Redirects to the user's
     * default company in this tenant, preserving the requested sub-path.
     */
    public function dashboardRedirectAction()
    {
        $user = $this->session->get('auth');
        $resolver = new TenantResolver($this->db);
        $companies = $resolver->findCompaniesForUserInTenant(
            (int) $user['id'],
            $this->currentTenantId,
            true
        );

        // Reconstruct the full sub-path (prefix + params) from the request URI
        // so the redirect preserves /dashboard, /api, or /reporting and
        // everything after it. The route pattern only captures the part after
        // the prefix in `params`, so we parse the URI to get the complete path.
        $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        $pathParts = explode('/', trim($uriPath, '/'));
        array_shift($pathParts); // drop tenant slug
        $subPath = implode('/', $pathParts); // e.g. "dashboard/settings"

        if (!empty($companies)) {
            $defaultCompany = $resolver->findDefaultCompanyForUserInTenant(
                (int) $user['id'],
                $this->currentTenantId
            );
            if (!$defaultCompany) {
                $defaultCompany = $companies[0];
            }
            $target = "/{$this->currentTenantSlug}/{$defaultCompany['slug']}";
            if ($subPath !== '') {
                $target .= "/{$subPath}";
            }
            return $this->response->redirect($target);
        }

        // No accessible company — fall back to the global companies page
        return $this->response->redirect('/dashboard/companies');
    }

    /**
     * Set the user's default company within the current tenant.
     *
     * Accepts a company_id POST parameter. Validates that the company
     * belongs to the current tenant and that the user has access to it.
     * Responds with JSON for AJAX requests, or redirects back for
     * non-JS fallback.
     */
    public function setDefaultCompanyAction()
    {
        $user = $this->session->get('auth');
        $companyId = (int) $this->request->getPost('company_id', 'int', 0);
        $isAjax = $this->request->isAjax() || $this->request->getPost('ajax', 'int', 0) === 1;

        if ($companyId <= 0) {
            if ($isAjax) {
                return $this->jsonResponse(false, $this->locale->t('flash.company_id_required'));
            }
            $this->flashSession->error($this->locale->t('flash.company_id_required'));
            return $this->response->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard/companies');
        }

        $resolver = new TenantResolver($this->db);

        // Verify the company belongs to this tenant and the user has access
        if (!$resolver->userHasCompanyAccess((int) $user['id'], $companyId)) {
            if ($isAjax) {
                return $this->jsonResponse(false, $this->locale->t('flash.company_no_access'));
            }
            $this->flashSession->error($this->locale->t('flash.company_no_access'));
            return $this->response->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard/companies');
        }

        $company = $this->db->fetchOne(
            "SELECT id, tenant_id, name, slug, status FROM companies WHERE id = :id LIMIT 1",
            DbEnum::FETCH_ASSOC,
            ['id' => $companyId]
        );

        if (!$company || (int)$company['tenant_id'] !== $this->currentTenantId) {
            if ($isAjax) {
                return $this->jsonResponse(false, $this->locale->t('flash.company_not_found_tenant'));
            }
            $this->flashSession->error($this->locale->t('flash.company_not_found_tenant'));
            return $this->response->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard/companies');
        }

        if (($company['status'] ?? '') !== 'active') {
            if ($isAjax) {
                return $this->jsonResponse(false, $this->locale->t('flash.company_suspended_default'));
            }
            $this->flashSession->error($this->locale->t('flash.company_suspended_default'));
            return $this->response->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard/companies');
        }

        $this->db->execute(
            "UPDATE admin_users SET default_company_id = :company_id WHERE id = :id",
            ['company_id' => $companyId, 'id' => (int) $user['id']]
        );

        if ($isAjax) {
            return $this->jsonResponse(true, $this->locale->t('flash.default_company_set', ['name' => $company['name']]), [
                'company_id' => $companyId,
                'company_name' => $company['name'],
            ]);
        }

        $this->flashSession->success($this->locale->t('flash.default_company_set', ['name' => $company['name']]));
        return $this->response->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard/companies');
    }

    /**
     * Return a JSON response. Disables the view and sets JSON content type.
     */
    protected function jsonResponse(bool $success, string $message, array $data = []): \Phalcon\Http\Response
    {
        $this->view->disable();
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setJsonContent([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ]);
        return $this->response;
    }
}
