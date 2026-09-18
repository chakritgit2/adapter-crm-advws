<?php

declare(strict_types=1);

use Phalcon\Mvc\Controller;
use Phalcon\Db\Enum as DbEnum;

class IndexController extends Controller
{
    use HasLocale;

    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var ViewManager
     */
    protected $view;
    // Authentication is handled by the active admin_users session flow.

    /**
     * @var ErrorService
     */
    protected $errorService;

    const VERIFY_URL = "https://www.google.com/recaptcha/api/siteverify";
    //This key can be used publicly
    const SITE_KEY = "6Lel73IUAAAAAIhAeKtuzsB6rUL6Ecm2pwNgXRXn";
    //This key must be kept secret
    const SECRET_KEY = "6Lel73IUAAAAAHojmxLfRquLOca0VmuCKNl6dCKU";

    protected $userID;

    public function initialize(): void
    {
        $this->session->start();

        $this->response = $this->di->get('response');
        $this->request = $this->di->get('request');
        $this->view = $this->di->get('view');

        // $this->auth = $this->di->get('auth');
        $this->errorService = $this->di->get('errorService');

        $this->view->setVar('clarityOn', $this->config->clarityOn);

        $user = $this->session->get('auth');

        if (!$user) {
            if ($this->dispatcher->getActionName() !== 'index') {
                $this->response->redirect($this->config['loginPath']);
                return;
            }
        }
        $this->view->setVar('user', (object) $user);

        // Force password change: if the admin user still has must_change_password
        // set, only allow the change-password actions (and logout). Redirect
        // everything else to the forced change-password page.
        $mustChange = false;
        if ($user && !empty($user['id'])) {
            $row = $this->db->fetchOne(
                "SELECT must_change_password FROM admin_users WHERE id = :id LIMIT 1",
                DbEnum::FETCH_ASSOC,
                ['id' => (int) $user['id']]
            );
            $mustChange = $row && (int)$row['must_change_password'] === 1;
        }
        $this->view->setVar('mustChange', $mustChange);

        if ($mustChange) {
            $action = strtolower($this->dispatcher->getActionName());
            if (!in_array($action, ['changepassword', 'changepasswordsave', 'index'], true)) {
                $this->response->redirect('/dashboard/change-password')->send();
                exit;
            }
        }

        $this->shareLocaleToView();
    }

    public function indexAction()
    {
        if ($this->dispatcher->getControllerName() !== 'index' && $this->dispatcher->getActionName() !== 'index') {
            return $this->redirectToDefaultTenant();
        }
    }

    public function legacyRedirectAction()
    {
        $params = $this->dispatcher->getParam('params') ?? '';
        $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        // Preserve the original prefix (/dashboard, /api, /reporting) from the request
        $path = $uriPath;

        if (strpos($path, '/dashboard/companies') === 0) {
            $subPath = substr($path, strlen('/dashboard/companies'));

            if ($subPath === '' || $subPath === '/') {
                return $this->dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'companies'
                ]);
            }

            if ($subPath === '/create') {
                return $this->dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'companiesCreate'
                ]);
            }

            if (preg_match('#^/edit/(\d+)$#', $subPath, $matches)) {
                return $this->dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'companiesEdit',
                    'params' => ['id' => (int) $matches[1]]
                ]);
            }
        }

        if (strpos($path, '/dashboard/profile') === 0) {
            $subPath = substr($path, strlen('/dashboard/profile'));

            if ($subPath === '' || $subPath === '/') {
                return $this->dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'profile'
                ]);
            }

            if ($subPath === '/save') {
                return $this->dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'profileSave'
                ]);
            }
        }

        return $this->redirectToDefaultTenant();
    }

    private function redirectToDefaultTenant()
    {
        $user = $this->session->get('auth');
        if (!$user) {
            return $this->response->redirect($this->config['loginPath']);
        }

        $basePath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/');
        if (strpos($basePath, 'dashboard/companies') === 0) {
            $subPath = substr($basePath, strlen('dashboard/companies'));

            if ($subPath === '' || $subPath === '/') {
                return $this->dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'companies'
                ]);
            }

            if ($subPath === '/create') {
                return $this->dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'companiesCreate'
                ]);
            }

            if (preg_match('#^/edit/(\d+)$#', $subPath, $matches)) {
                return $this->dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'companiesEdit',
                    'params' => ['id' => (int) $matches[1]]
                ]);
            }

            return;
        }

        if (strpos($basePath, 'dashboard/profile') === 0) {
            $subPath = substr($basePath, strlen('dashboard/profile'));

            if ($subPath === '' || $subPath === '/') {
                return $this->dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'profile'
                ]);
            }

            if ($subPath === '/save') {
                return $this->dispatcher->forward([
                    'controller' => 'index',
                    'action' => 'profileSave'
                ]);
            }

            return;
        }

        $resolver = new TenantResolver($this->db);
        $company = $resolver->findDefaultCompanyForUser((int) $user['id']);

        if (!$company) {
            $this->response->setStatusCode(403, 'Forbidden');
            $this->response->setContent('No assigned company found.');
            return $this->response->send();
        }

        $tenantSlug = $company['tenant_slug'] ?? 'default';
        $target = "/{$tenantSlug}/{$company['slug']}/{$basePath}";

        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        if ($queryString) {
            $target .= '?' . $queryString;
        }

        return $this->response->redirect($target);
    }

    private function setSidebarContext(): void
    {
        $user = $this->session->get('auth');
        if (!$user) {
            return;
        }

        $resolver = new TenantResolver($this->db);
        $userCompanies = $resolver->findCompaniesForUser((int) $user['id']);
        $this->view->setVar('userCompanies', $userCompanies);

        $defaultCompany = $userCompanies[0] ?? null;
        if ($defaultCompany) {
            $this->view->setVar('currentTenantSlug', $defaultCompany['tenant_slug'] ?? 'default');
            $this->view->setVar('currentTenant', (object) ['name' => $defaultCompany['tenant_name'] ?? 'Default', 'slug' => $defaultCompany['tenant_slug'] ?? 'default']);
            $this->view->setVar('currentCompanySlug', $defaultCompany['slug']);
            $this->view->setVar('currentCompany', (object) $defaultCompany);
            $this->view->setVar('navCompanySlug', $defaultCompany['slug']);
        } else {
            $this->view->setVar('currentTenantSlug', '');
            $this->view->setVar('currentTenant', (object) ['name' => '', 'slug' => '', 'status' => '']);
            $this->view->setVar('currentCompanySlug', '');
            $this->view->setVar('currentCompany', (object) ['name' => '', 'slug' => '', 'status' => '']);
            $this->view->setVar('navCompanySlug', '');
        }

        $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        $pathParts = explode('/', trim($uriPath, '/'));
        $this->view->setVar('currentPathSuffix', '/' . implode('/', $pathParts));

        $isCompaniesPage = strpos($uriPath, '/dashboard/companies') !== false;
        $this->view->setVar('isCompaniesPage', $isCompaniesPage);

        $isProfilePage = strpos($uriPath, '/dashboard/profile') !== false;
        $this->view->setVar('isProfilePage', $isProfilePage);
    }

    private function isSuperAdmin(): bool
    {
        $user = $this->session->get('auth');
        return ($user['role'] ?? '') === 'Super Admin';
    }

    public function companiesAction()
    {
        $this->setSidebarContext();
        $this->view->setVar('title', $this->locale->t('company.list.title'));

        $user = $this->session->get('auth');
        $resolver = new TenantResolver($this->db);
        $companies = $resolver->findCompaniesForUser((int) $user['id'],false);

        $companyIds = array_column($companies, 'id');
        $customAttributes = [];
        $companyAttributes = [];

        if (!empty($companyIds)) {
            $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
            $customAttributes = $this->db->fetchAll(
                "SELECT id, company_id, name, field_type, is_required, show_in_list, dropdown_choice " .
                "FROM custom_attributes " .
                "WHERE attribute_entity = 'companies' AND company_id IN (" . $placeholders . ") AND show_in_list = 1 " .
                "ORDER BY name ASC",
                Phalcon\Db\Enum::FETCH_ASSOC,
                $companyIds
            );

            foreach ($customAttributes as &$attr) {
                $decoded = json_decode($attr['dropdown_choice'], true);
                $attr['dropdown_choice'] = is_array($decoded) ? $decoded : [];
            }
            unset($attr);

            $attrIds = array_column($customAttributes, 'id');
            if (!empty($attrIds)) {
                $attrPlaceholders = implode(',', array_fill(0, count($attrIds), '?'));
                $allParams = array_merge($companyIds, $attrIds);
                $compPlaceholders = implode(',', array_fill(0, count($companyIds), '?'));
                $values = $this->db->fetchAll(
                    "SELECT company_id, attribute_id, value FROM company_attribute_values " .
                    "WHERE company_id IN (" . $compPlaceholders . ") AND attribute_id IN (" . $attrPlaceholders . ")",
                    Phalcon\Db\Enum::FETCH_ASSOC,
                    $allParams
                );
                foreach ($values as $v) {
                    $companyAttributes[(int)$v['company_id']][(int)$v['attribute_id']] = $v['value'];
                }
            }
        }

        $employeeCounts = [];
        if (!empty($companyIds)) {
            $compPlaceholders = implode(',', array_fill(0, count($companyIds), '?'));
            $rows = $this->db->fetchAll(
                "SELECT company_id, COUNT(*) as cnt FROM employees WHERE company_id IN (" . $compPlaceholders . ") GROUP BY company_id",
                Phalcon\Db\Enum::FETCH_ASSOC,
                $companyIds
            );
            foreach ($rows as $row) {
                $employeeCounts[(int)$row['company_id']] = (int)$row['cnt'];
            }
        }

        $this->view->setVar('companies', $companies);
        $this->view->setVar('customAttributes', $customAttributes);
        $this->view->setVar('companyAttributes', $companyAttributes);
        $this->view->setVar('employeeCounts', $employeeCounts);
        $this->view->setVar('isSuperAdmin', $this->isSuperAdmin());
        $this->view->pick('dashboard/companies');
    }

    public function companiesCreateAction()
    {
        $this->setSidebarContext();

        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_create'));
            return $this->response->redirect('/dashboard/companies');
        }

        $this->view->setVar('title', $this->locale->t('company.new.title'));
        $this->view->setVar('mode', 'create');
        $this->view->setVar('companiesBase', '/dashboard/companies');
        $this->view->pick('dashboard/companies-action');
    }

    public function companiesEditAction()
    {
        $this->setSidebarContext();

        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_edit'));
            return $this->response->redirect('/dashboard/companies');
        }

        $id = (int) $this->dispatcher->getParam('id');

        $company = $this->db->fetchOne(
            "SELECT id, name, slug, status FROM companies WHERE id = :id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => $id]
        );

        if (!$company) {
            $this->flashSession->error($this->locale->t('flash.company_not_found'));
            return $this->response->redirect('/dashboard/companies');
        }

        $customAttributes = $this->db->fetchAll(
            "SELECT id, name, field_type, is_required, dropdown_choice FROM custom_attributes " .
            "WHERE company_id = :company_id AND attribute_entity = 'companies' " .
            "ORDER BY name ASC",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $id]
        );

        foreach ($customAttributes as &$attr) {
            $decoded = json_decode($attr['dropdown_choice'], true);
            $attr['dropdown_choice'] = is_array($decoded) ? $decoded : [];
        }
        unset($attr);

        $values = $this->db->fetchAll(
            "SELECT attribute_id, value FROM company_attribute_values WHERE company_id = :company_id",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $id]
        );
        $companyAttributes = [];
        foreach ($values as $v) {
            $companyAttributes[(int)$v['attribute_id']] = $v['value'];
        }

        $this->view->setVar('title', $this->locale->t('company.edit.title'));
        $this->view->setVar('mode', 'edit');
        $this->view->setVar('companies', $company);
        $this->view->setVar('customAttributes', $customAttributes);
        $this->view->setVar('companyAttributes', $companyAttributes);
        $this->view->setVar('companiesBase', '/dashboard/companies');
        $this->view->pick('dashboard/companies-action');
    }

    public function companiesStoreAction()
    {
        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_create'));
            return $this->response->redirect('/dashboard/companies');
        }

        $name = trim($this->request->getPost('name', 'string', ''));
        $status = $this->request->getPost('status', 'string', 'active');
        $slug = trim($this->request->getPost('slug', 'string', ''));

        if (empty($name)) {
            $this->flashSession->error($this->locale->t('flash.company_name_required'));
            return $this->response->redirect('/dashboard/companies/create');
        }

        if (empty($slug)) {
            $slug = $this->slugify($name);
        }

        if (!in_array($status, ['active', 'suspended'], true)) {
            $status = 'active';
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM companies WHERE slug = :slug LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['slug' => $slug]
        );
        if ($existing) {
            $this->flashSession->error($this->locale->t('flash.company_slug_duplicate'));
            return $this->response->redirect('/dashboard/companies/create');
        }

        $this->db->execute(
            "INSERT INTO companies (name, slug, status, created_at) VALUES (:name, :slug, :status, NOW())",
            ['name' => $name, 'slug' => $slug, 'status' => $status]
        );

        $newCompanyId = (int)$this->db->lastInsertId();
        $auth = $this->session->get('auth');

        $this->db->execute(
            "INSERT INTO company_user_map (admin_user_id, company_id, role) VALUES (:admin_user_id, :company_id, :role)",
            [
                'admin_user_id' => (int)$auth['id'],
                'company_id' => $newCompanyId,
                'role' => 'Super Admin'
            ]
        );

        $this->flashSession->success($this->locale->t('flash.company_created'));
        return $this->response->redirect('/dashboard/companies');
    }

    public function companiesUpdateAction()
    {
        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_update'));
            return $this->response->redirect('/dashboard/companies');
        }

        $id = (int) $this->dispatcher->getParam('id');
        $name = trim($this->request->getPost('name', 'string', ''));
        $status = $this->request->getPost('status', 'string', 'active');
        $slug = trim($this->request->getPost('slug', 'string', ''));

        if (empty($name)) {
            $this->flashSession->error($this->locale->t('flash.company_name_required'));
            return $this->response->redirect('/dashboard/companies/edit/' . $id);
        }

        if (empty($slug)) {
            $slug = $this->slugify($name);
        }

        if (!in_array($status, ['active', 'suspended'], true)) {
            $status = 'active';
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM companies WHERE slug = :slug AND id != :id LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['slug' => $slug, 'id' => $id]
        );
        if ($existing) {
            $this->flashSession->error($this->locale->t('flash.company_slug_duplicate'));
            return $this->response->redirect('/dashboard/companies/edit/' . $id);
        }

        $this->db->execute(
            "UPDATE companies SET name = :name, slug = :slug, status = :status WHERE id = :id",
            ['id' => $id, 'name' => $name, 'slug' => $slug, 'status' => $status]
        );

        $customAttributes = $this->db->fetchAll(
            "SELECT id, name, is_required FROM custom_attributes " .
            "WHERE company_id = :company_id AND attribute_entity = 'companies'",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['company_id' => $id]
        );

        $customAttrValues = $this->request->getPost('custom_attributes', null, []);
        $errors = [];
        foreach ($customAttributes as $attr) {
            $val = $customAttrValues[$attr['id']] ?? null;
            if ($attr['is_required'] && ($val === '' || $val === null)) {
                $errors[] = $attr['name'] . ' is required.';
            }
        }
        if (!empty($errors)) {
            $this->flashSession->error(implode(' ', $errors));
            return $this->response->redirect('/dashboard/companies/edit/' . $id);
        }

        foreach ($customAttrValues as $attrId => $value) {
            $this->db->execute(
                "INSERT INTO company_attribute_values (company_id, attribute_id, value) VALUES (:company_id, :attribute_id, :value) " .
                "ON DUPLICATE KEY UPDATE value = VALUES(value)",
                [
                    'company_id' => $id,
                    'attribute_id' => (int) $attrId,
                    'value' => trim($value)
                ]
            );
        }

        $this->flashSession->success($this->locale->t('flash.company_updated'));
        return $this->response->redirect('/dashboard/companies');
    }

    public function companiesDeleteAction()
    {
        if (!$this->isSuperAdmin()) {
            $this->flashSession->error($this->locale->t('flash.super_admin_only_delete'));
            return $this->response->redirect('/dashboard/companies');
        }

        $id = (int) $this->dispatcher->getParam('id');
        $this->db->execute(
            "DELETE FROM companies WHERE id = :id",
            ['id' => $id]
        );

        $this->flashSession->success($this->locale->t('flash.company_deleted'));
        return $this->response->redirect('/dashboard/companies');
    }

    private function slugify(string $text): string
    {
        $text = preg_replace('/[^\pL\d]+/u', '-', $text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = preg_replace('/[^\w]+/', '-', $text);
        $text = strtolower(trim($text, '-'));
        $text = preg_replace('/-+/', '-', $text);
        return $text;
    }

    public function profileAction()
    {
        $this->setSidebarContext();
        $this->view->setVar('title', $this->locale->t('profile.title'));
        $this->view->pick('dashboard/profile');
    }

    /**
     * Global language switcher (works on every page, no tenant/company
     * context required). Sets/clears the session active_language and
     * persists the choice to admin_users.preferred_language so it can be
     * restored on subsequent logins.
     */
    public function setLanguageAction()
    {
        $code = trim($this->request->getPost('language_code', 'string', ''));
        $config = $this->getDI()->get('config');
        $default = $config->path('languages.default', 'th');
        $supported = $config->path('languages.supported');

        // 1. UI locale (Layer A): only supported UI languages (th/en)
        //    set/clear the session. Thai (default) clears the key so the
        //    absence-of-session => Thai rule holds.
        if ($supported && $supported->offsetExists($code)) {
            if ($code === $default) {
                $this->session->remove('active_language');
            } else {
                $this->session->set('active_language', $code);
            }
        } elseif ($code !== '') {
            // 2. Entity-content locale (Layer B): a tenant-installed
            //    language that is not a UI language still sets
            //    active_language so TranslationService resolves translated
            //    record values. Install validation is skipped here because
            //    this endpoint has no company context; content languages
            //    are only surfaced in the selector on company-scoped pages
            //    where they are already filtered to active installs.
            $this->session->set('active_language', $code);
        }

        // 3. Persist the choice to the user's record.
        $auth = $this->session->get('auth');
        if ($auth && !empty($auth['id'])) {
            $storeCode = ($code === '' || $code === $default) ? null : $code;
            $this->db->execute(
                "UPDATE admin_users SET preferred_language = :lang WHERE id = :id",
                ['lang' => $storeCode, 'id' => (int) $auth['id']]
            );
        }

        $this->response->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard/profile');
    }

    public function profileSaveAction()
    {
        if (!$this->request->isPost()) {
            $this->response->redirect('/dashboard/profile');
            return;
        }

        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');
        $auth = $this->session->get('auth');

        // Validate current password
        $user = $this->db->fetchOne(
            "SELECT * FROM admin_users WHERE id = :id",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['id' => $auth['id']]
        );

        $encryptedCurrent = LoginController::encryptPass($currentPassword);

        if ($encryptedCurrent !== $user['password_hash']) {
            $this->flashSession->error($this->locale->t('flash.password_current_incorrect'));
            $this->response->redirect('/dashboard/profile');
            return;
        }

        // Validate new password
        if (strlen($newPassword) < 8) {
            $this->flashSession->error($this->locale->t('flash.password_too_short'));
            $this->response->redirect('/dashboard/profile');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->flashSession->error($this->locale->t('flash.password_mismatch'));
            $this->response->redirect('/dashboard/profile');
            return;
        }

        // Update password
        $encryptedNew = LoginController::encryptPass($newPassword);
        $success = $this->db->execute(
            "UPDATE admin_users SET password_hash = :password WHERE id = :id",
            [
                'password' => $encryptedNew,
                'id' => $auth['id']
            ]
        );

        if ($success) {
            $this->flashSession->success($this->locale->t('flash.password_updated'));
        } else {
            $this->flashSession->error($this->locale->t('flash.password_update_failed'));
        }

        $this->response->redirect('/dashboard/profile');
    }

    /**
     * Forced password change page — shown when admin_users.must_change_password = 1.
     * The sidebar is hidden by the layout when mustChange is true.
     */
    public function changePasswordAction()
    {
        $this->setSidebarContext();
        $this->view->setVar('title', $this->locale->t('auth.change_password'));
        $this->view->pick('dashboard/change-password');
    }

    public function changePasswordSaveAction()
    {
        if (!$this->request->isPost()) {
            $this->response->redirect('/dashboard/change-password');
            return;
        }

        $newPassword = $this->request->getPost('new_password', 'string', '');
        $confirmPassword = $this->request->getPost('confirm_password', 'string', '');
        $auth = $this->session->get('auth');

        if (empty($newPassword) || empty($confirmPassword)) {
            $this->flashSession->error($this->locale->t('auth.password_required'));
            $this->response->redirect('/dashboard/change-password');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->flashSession->error($this->locale->t('auth.password_mismatch'));
            $this->response->redirect('/dashboard/change-password');
            return;
        }

        if (strlen($newPassword) < 8 ||
            !preg_match('/[A-Z]/', $newPassword) ||
            !preg_match('/[a-z]/', $newPassword) ||
            !preg_match('/[0-9]/', $newPassword) ||
            !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            $this->flashSession->error($this->locale->t('auth.password_weak'));
            $this->response->redirect('/dashboard/change-password');
            return;
        }

        $passwordHash = LoginController::encryptPass($newPassword);

        $success = $this->db->execute(
            "UPDATE admin_users SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id",
            [
                'password_hash' => $passwordHash,
                'id' => (int) $auth['id'],
            ]
        );

        if ($success) {
            $this->flashSession->success($this->locale->t('auth.password_set'));
            // After changing the password, send the user to their default tenant dashboard.
            $resolver = new TenantResolver($this->db);
            $tenant = $resolver->findDefaultTenantForUser((int) $auth['id']);
            if ($tenant && !empty($tenant['slug'])) {
                $this->response->redirect('/' . $tenant['slug']);
            } else {
                $this->response->redirect($this->config['loginPath']);
            }
        } else {
            $this->flashSession->error($this->locale->t('flash.password_update_failed'));
            $this->response->redirect('/dashboard/change-password');
        }
    }

}
