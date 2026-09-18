<?php

declare(strict_types=1);

use Phalcon\Mvc\Controller;

class LegacyRedirectController extends Controller
{
    public function initialize(): void
    {
  
    }

    public function dashboardAction()
    {
        return $this->redirectToDefaultTenant('dashboard');
    }

    public function apiAction()
    {
        return $this->redirectToDefaultTenant('api');
    }

    public function reportingAction()
    {
        return $this->redirectToDefaultTenant('reporting');
    }

    private function redirectToDefaultTenant(string $prefix)
    {
        $user = $this->session->get('auth');
        if (!$user) {
            $this->response->redirect($this->config['loginPath']);
            return;
        }

        $resolver = new TenantResolver($this->db);
        $company = $resolver->findDefaultCompanyForUser((int) $user['id']);

        if (!$company) {
            $this->response->setStatusCode(403, 'Forbidden');
            $this->response->setContent('No assigned company found.');
            $this->response->send();
            return;
        }

        $tenantSlug = $company['tenant_slug'] ?? '';
        $slug = $company['slug'];
        $params = $this->dispatcher->getParam('params') ?? '';
        $params = ltrim($params, '/');
        $target = $tenantSlug !== ''
            ? "/{$tenantSlug}/{$slug}/{$prefix}" . ($params ? "/{$params}" : '')
            : "/{$slug}/{$prefix}" . ($params ? "/{$params}" : '');

        $this->response->redirect($target);
    }
}
