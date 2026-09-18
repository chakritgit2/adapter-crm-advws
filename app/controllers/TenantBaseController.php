<?php

declare(strict_types=1);

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class TenantBaseController extends Controller
{
    use HasLocale;

    // Tenant-level (the parent entity)
    protected ?int $currentTenantId = null;
    protected ?string $currentTenantSlug = null;
    protected ?array $currentTenant = null;

    // Company-level (the child entity, used for data isolation)
    protected ?int $currentCompanyId = null;
    protected ?string $currentCompanySlug = null;
    protected ?array $currentCompany = null;

    /**
     * Set up tenant and company context from URL slugs.
     *
     * This runs in initialize() because Phalcon 5 does NOT automatically
     * call beforeExecuteRoute on controllers — it only fires the event
     * to explicitly registered listeners. initialize() is always called
     * directly by the dispatcher after controller construction.
     *
     * On error we send the HTTP response and exit() to prevent the action
     * from running with null context. This is intentional — there is no
     * other reliable way in Phalcon 5 to stop an action from initialize().
     */
    public function initialize(): void
    {
        // Defense-in-depth: if a login-path URI ever reaches this controller
        // (e.g. a tenant slug collides with the login path), redirect to the
        // login controller instead of running the tenant action with null
        // context. The routing in router.php already sends login paths to
        // LoginController, but this guards against misconfiguration.
        $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        if (in_array($uriPath, [$this->config['loginPath'], $this->config['loginPath'].'/authenticate', $this->config['loginPath'].'/logout'], true)) {
            $this->response->redirect($this->config['loginPath'])->send();
            exit;
        }

        $user = $this->session->get('auth');
        if (!$user) {
            $this->response->redirect('/')->send();
            exit;
        }
        $this->view->setVar('user', (object) $user);

        // Force password change: if the admin user still has must_change_password
        // set, redirect every tenant-scoped page to the forced change-password page.
        // The change-password page itself lives on IndexController (global route)
        // so it won't hit this redirect loop.
        $mustChange = false;
        $mcpRow = $this->db->fetchOne(
            "SELECT must_change_password FROM admin_users WHERE id = :id LIMIT 1",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => (int) $user['id']]
        );
        if ($mcpRow && (int)$mcpRow['must_change_password'] === 1) {
            $mustChange = true;
        }
        $this->view->setVar('mustChange', $mustChange);

        if ($mustChange) {
            $this->response->redirect('/dashboard/change-password')->send();
            exit;
        }

        $tenantSlug = $this->dispatcher->getParam('tenant_slug');

        if (empty($tenantSlug)) {
            $this->sendFatalError(404, 'Tenant not specified.');
            return;
        }

        $resolver = new TenantResolver($this->db);

        // 1. Resolve the tenant
        $tenant = $resolver->resolveTenantBySlug($tenantSlug);

        if (!$tenant || $tenant['status'] === 'suspended') {
            $this->sendFatalError(404, 'Tenant not found.');
            return;
        }

        $tenantId = (int) $tenant['id'];

        // 2. Check tenant-level access
        if (!$resolver->userHasTenantAccess((int) $user['id'], $tenantId)) {
            $this->sendFatalError(403, 'You do not have permission to view this tenant.');
            return;
        }

        // Set tenant-level vars
        $this->currentTenantId = $tenantId;
        $this->currentTenantSlug = $tenantSlug;
        $this->currentTenant = $tenant;

        $this->view->setVar('currentTenantId', $tenantId);
        $this->view->setVar('currentTenantSlug', $tenantSlug);
        $this->view->setVar('currentTenant', (object) $tenant);

        // 3. Resolve company if company_slug is present (company-scoped routes)
        $companySlug = $this->dispatcher->getParam('company_slug');
        $companyId = null;

        if (!empty($companySlug)) {
            $company = $resolver->resolveCompanyBySlug($companySlug, $tenantId);

            if (!$company || $company['status'] === 'suspended') {
                $this->sendFatalError(404, 'Company not found under this tenant.');
                return;
            }

            $companyId = (int) $company['id'];

            // Two-tier access check: tenant access OR direct company access
            if (!$resolver->userHasCompanyAccess((int) $user['id'], $companyId)) {
                $this->sendFatalError(403, 'You do not have permission to view this company.');
                return;
            }

            $this->currentCompanyId = $companyId;
            $this->currentCompanySlug = $companySlug;
            $this->currentCompany = $company;

            $this->view->setVar('currentCompanyId', $companyId);
            $this->view->setVar('currentCompanySlug', $companySlug);
            $this->view->setVar('currentCompany', (object) $company);
        } else {
            // Tenant-level page: no company context
            $this->view->setVar('currentCompanyId', null);
            $this->view->setVar('currentCompanySlug', null);
            $this->view->setVar('currentCompany', null);
        }

        // Build path suffix (for nav links) — strip tenant slug (and company slug if present)
        $pathParts = explode('/', trim($uriPath, '/'));
        array_shift($pathParts); // drop tenant slug
        if (!empty($companySlug)) {
            array_shift($pathParts); // drop company slug
        }
        $currentPathSuffix = '/' . implode('/', $pathParts);
        $this->view->setVar('currentPathSuffix', $currentPathSuffix);

        // Companies for the company switcher (within this tenant)
        $userCompanies = $resolver->findCompaniesForUserInTenant((int) $user['id'], $tenantId, false);
        $this->view->setVar('userCompanies', $userCompanies);
        $this->view->setVar('isCompaniesPage', false);
        $this->view->setVar('isProfilePage', false);

        // Resolve the user's default company within this tenant (if any).
        // This is used both for the nav fallback and to highlight the
        // default company in the company selector dropdown.
        $defaultCompany = $resolver->findDefaultCompanyForUserInTenant((int) $user['id'], $tenantId);
        $defaultCompanyId = $defaultCompany ? (int)$defaultCompany['id'] : null;
        $this->view->setVar('defaultCompanyId', $defaultCompanyId);

        // Fallback company slug for sidebar nav links on tenant-level pages.
        // When currentCompanySlug is null (tenant-level page), sidebar links
        // to company-scoped pages (settings, reports, etc.) would generate
        // broken URLs like /{tenant}/dashboard/settings. Using the user's
        // default company (or first accessible company) as a fallback keeps
        // those links functional.
        $navCompanySlug = $this->currentCompanySlug;
        if ($navCompanySlug === null) {
            $navCompanySlug = $defaultCompany ? $defaultCompany['slug'] : null;
            if ($navCompanySlug === null && !empty($userCompanies)) {
                $navCompanySlug = $userCompanies[0]['slug'];
            }
        }
        $this->view->setVar('navCompanySlug', $navCompanySlug ?? '');

        // Tenant languages (only relevant for company-scoped pages)
        if ($companyId !== null) {
            $tenantLanguages = $this->db->fetchAll(
                "SELECT id, language_code, language_name, is_active FROM tenant_languages WHERE company_id = :company_id AND is_active = 1 ORDER BY language_name ASC",
                \Phalcon\Db\Enum::FETCH_ASSOC,
                ['company_id' => $companyId]
            );
            $this->view->setVar('tenantLanguages', $tenantLanguages);
        } else {
            $this->view->setVar('tenantLanguages', []);
        }

        // Share the resolved UI locale (Layer A) with every view
        $this->shareLocaleToView();
    }

    /**
     * Send an HTTP error response and stop execution.
     * exit() is used because Phalcon 5 has no way to prevent an action
     * from running once initialize() has been called.
     */
    protected function sendFatalError(int $statusCode, string $message): void
    {
        $this->response->setStatusCode($statusCode);
        $this->response->setContent($message);
        $this->response->send();
        exit;
    }

    protected function redirectToTenant(string $path): Response
    {
        $tenantSlug = $this->currentTenantSlug ?? 'unknown';
        $companySlug = $this->currentCompanySlug;
        $path = ltrim($path, '/');
        if ($companySlug !== null) {
            return $this->response->redirect("/{$tenantSlug}/{$companySlug}/{$path}");
        }
        return $this->response->redirect("/{$tenantSlug}/{$path}");
    }

    protected function tenantUrl(string $path): string
    {
        $tenantSlug = $this->currentTenantSlug ?? 'unknown';
        $companySlug = $this->currentCompanySlug;
        $path = ltrim($path, '/');
        if ($companySlug !== null) {
            return "/{$tenantSlug}/{$companySlug}/{$path}";
        }
        return "/{$tenantSlug}/{$path}";
    }

    /**
     * Resolve the entity-content language (Layer B) for translation joins.
     *
     * Returns the session active_language if set, otherwise the configured
     * default language. This is needed because setLanguageAction clears the
     * session key when the default language is selected (absence-of-session =>
     * default). Without this, translated queries are skipped for the default
     * language since $this->session->get('active_language') returns null.
     */
    protected function getEntityLanguage(): string
    {
        $lang = $this->session->get('active_language');
        if ($lang) {
            return $lang;
        }
        return $this->config->path('languages.default', 'th');
    }
}
