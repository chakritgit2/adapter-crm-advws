<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class LoginController extends Controller
{
    use HasLocale;

    public function initialize()
    {
        $this->view->setVar('clarityOn', $this->config->clarityOn);
        $this->view->setVar('user', []);
        $this->shareLocaleToView();
    }

    public function indexAction()
    {
        // Generate CSRF token
        // $tokenKey = $this->security->getTokenKey();
        // $tokenValue = $this->security->getToken();

        // Store in session
        // $this->session->set('csrfKey', $tokenKey);
        // $this->session->set('csrfValue', $tokenValue);

        $returnUrl = $this->request->getQuery('returnUrl', 'string');
        $this->session->set('returnUrl', $returnUrl);
        $this->view->setVar('returnUrl', $returnUrl);

        // Pass to view
        // $this->view->setVars([
        //     'csrfKey' => $tokenKey,
        //     'csrfValue' => $tokenValue,
        // ]);
    }

    public function authenticateAction()
    {
        // vd($_SERVER);

        // var_dump($this->request->getPost());
        // die();

        // Validate CSRF token
        // $csrfKey = $this->session->get('csrfKey');
        // $csrfValue = $this->session->get('csrfValue');

        // vd($this->request->getPost());
        // vd($csrfKey);
        // vd($csrfValue);
        // die();

        // if (
        //     $csrfKey !== $this->request->getPost('csrfKey') ||
        //     $csrfValue !== $this->request->getPost('csrfValue')
        // ) {
        //     $this->flashSession->error('Invalid CSRF token');
        //     $this->response->redirect($this->config['loginPath']);
        //     return;
        // }

        // Process login as before
        $email = $this->request->getPost('email', 'string');
        $password = $this->encryptPass($this->request->getPost('password', 'string'));

        $user = $this->db->fetchOne(
            "SELECT * FROM admin_users WHERE email = :email AND password_hash = :password",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['email' => $email, 'password' => $password]
        );

        if ($user) {
            $this->session->set('auth', [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
            ]);

            //Update last_login_at for user
            $this->db->execute("UPDATE admin_users SET last_login_at = NOW() WHERE id = :id", ['id' => $user['id']]);

            // Restore the user's preferred language from their profile, but
            // only if they haven't already chosen one on the login page
            // (which would have set active_language in the session already).
            $prefLang = $user['preferred_language'] ?? null;
            $defaultLang = $this->config->path('languages.default', 'th');
            if ($prefLang && $prefLang !== $defaultLang && !$this->session->has('active_language')) {
                $this->session->set('active_language', $prefLang);
            }

            // Force password change on first login (or when flagged by admin).
            if ((int)($user['must_change_password'] ?? 0) === 1) {
                $this->flashSession->warning($this->locale->t('auth.must_change_password_notice'));
                return $this->response->redirect('/dashboard/change-password');
            }

            return $this->response->redirect('/dashboard');
        }

        $this->flashSession->error($this->locale->t('auth.invalid_credentials'));
        return $this->response->redirect($this->config['loginPath']);
    }

    public function setPasswordAction()
    {
        $token = $this->dispatcher->getParam('token', 'string', '');

        if (empty($token)) {
            $this->flashSession->error($this->locale->t('auth.token_missing'));
            $this->response->redirect($this->config['loginPath']);
            return;
        }

        $user = $this->db->fetchOne(
            "SELECT id FROM admin_users WHERE set_password_token = :token LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['token' => $token]
        );

        if (!$user) {
            $this->flashSession->error($this->locale->t('auth.token_invalid'));
            $this->response->redirect($this->config['loginPath']);
            return;
        }

        $this->view->setVar('title', $this->locale->t('auth.set_password'));
        $this->view->setVar('token', $token);
        $this->view->pick('login/set-password');
    }

    public function setPasswordSaveAction()
    {
        $token = $this->dispatcher->getParam('token', 'string', '');
        $password = $this->request->getPost('password', 'string', '');
        $confirmPassword = $this->request->getPost('confirm_password', 'string', '');

        if (empty($token)) {
            $this->flashSession->error($this->locale->t('auth.token_missing'));
            $this->response->redirect($this->config['loginPath']);
            return;
        }

        $user = $this->db->fetchOne(
            "SELECT id, name, email, role FROM admin_users WHERE set_password_token = :token LIMIT 1",
            Phalcon\Db\Enum::FETCH_ASSOC,
            ['token' => $token]
        );

        if (!$user) {
            $this->flashSession->error($this->locale->t('auth.token_invalid'));
            $this->response->redirect($this->config['loginPath']);
            return;
        }

        if (empty($password) || empty($confirmPassword)) {
            $this->flashSession->error($this->locale->t('auth.password_required'));
            $this->response->redirect('/set-password/' . $token);
            return;
        }

        if ($password !== $confirmPassword) {
            $this->flashSession->error($this->locale->t('auth.password_mismatch'));
            $this->response->redirect('/set-password/' . $token);
            return;
        }

        if (strlen($password) < 8 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[^A-Za-z0-9]/', $password)) {
            $this->flashSession->error($this->locale->t('auth.password_weak'));
            $this->response->redirect('/set-password/' . $token);
            return;
        }

        $passwordHash = self::encryptPass($password);

        $this->db->execute(
            "UPDATE admin_users SET password_hash = :password_hash, set_password_token = NULL WHERE id = :id",
            ['password_hash' => $passwordHash, 'id' => $user['id']]
        );

        $this->session->set('auth', [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
        ]);

        $this->db->execute("UPDATE admin_users SET last_login_at = NOW() WHERE id = :id", ['id' => $user['id']]);

        $this->flashSession->success($this->locale->t('auth.password_set'));
        $this->response->redirect('/dashboard/companies');
    }

    // public function selectTenantAction()
    // {
    //     $resolver = new TenantResolver($this->db);
    //     $companies = $resolver->findCompaniesForUser((int) $user['id']);

    //     if (count($companies) === 1) {
    //         return $this->response->redirect("/{$companies[0]['slug']}/dashboard");
    //     }

    //     $this->view->setVar('companies', $companies);
    // }

    // public function chooseTenantAction()
    // {
    //     $slug = $this->request->getPost('slug', 'string');
    //     $resolver = new TenantResolver($this->db);
    //     $company = $resolver->resolveBySlug($slug);

    //     if (!$company || !$resolver->userHasAccess((int) $user['id'], (int) $company['id'])) {
    //         $this->flashSession->error('Invalid tenant selection.');
    //         return $this->response->redirect('/select-tenant');
    //     }

    //     return $this->response->redirect("/{$company['slug']}/dashboard");
    // }

    static public function encryptPass($password)
    {
        $encpass = base64_encode($password);
        $encpass = hash("sha256", $encpass);
        return $encpass;
    }

    public function logoutAction()
    {
        $this->session->destroy();
        $this->response->redirect($this->config['loginPath']);
    }


}
