<?php

declare(strict_types=1);

use Phalcon\Db\Enum as DbEnum;

class AdapterController extends TenantBaseController
{
    private AdapterConnectionService $adapterConnections;

    public function initialize(): void
    {
        parent::initialize();
        $user = $this->session->get('auth');
        if (($user['role'] ?? '') !== 'Super Admin') {
            $this->sendFatalError(403, 'Adapter administration requires Super Admin access.');
        }
        $this->adapterConnections = $this->getDI()->get('adapterConnections');
        $this->view->setVar('adapterCsrf', AdapterCsrf::token($this->session));
        $this->view->setVar('adapterBaseUrl', $this->tenantUrl('/adapter'));
    }

    public function indexAction(): void
    {
        $this->view->setVar('title', 'Universal Data Adapter');
        $this->view->setVar('connections', $this->scopeConnections());
        $this->view->setVar('endpoints', $this->scopeEndpoints());
        $this->view->pick('adapter/index');
    }

    public function connectionCreateAction(): void
    {
        $this->view->setVar('title', 'Add Adapter Connection');
        $this->view->setVar('connection', null);
        $this->view->pick('adapter/connection-form');
    }

    public function connectionEditAction()
    {
        $connection = $this->findConnection((int)$this->dispatcher->getParam('id'));
        if (!$connection) {
            $this->flashSession->error('Connection not found.');
            return $this->response->redirect($this->tenantUrl('/adapter'));
        }
        $this->view->setVar('title', 'Edit Adapter Connection');
        $this->view->setVar('connection', $connection);
        $this->view->pick('adapter/connection-form');
    }

    public function connectionStoreAction()
    {
        $this->requireCsrf();
        $data = $this->connectionInput();
        if ($data['error']) {
            return $this->adapterError($data['error'], '/adapter/connections/create');
        }
        $this->db->execute(
            "INSERT INTO adapter_connections (tenant_id, company_id, name, engine, host, port, db_name, username, password_ciphertext, options_json)
             VALUES (:tenant_id, :company_id, :name, :engine, :host, :port, :db_name, :username, :password_ciphertext, :options_json)",
            $data['values'] + ['tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId]
        );
        $this->flashSession->success('Adapter connection created. Test it before enabling endpoints.');
        return $this->response->redirect($this->tenantUrl('/adapter'));
    }

    public function connectionUpdateAction()
    {
        $this->requireCsrf();
        $id = (int)$this->dispatcher->getParam('id');
        $existing = $this->findConnection($id);
        if (!$existing) {
            return $this->adapterError('Connection not found.', '/adapter');
        }
        $data = $this->connectionInput($existing);
        if ($data['error']) {
            return $this->adapterError($data['error'], '/adapter/connections/edit/' . $id);
        }
        $values = $data['values'] + ['id' => $id, 'tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId];
        $this->db->execute(
            "UPDATE adapter_connections SET name = :name, engine = :engine, host = :host, port = :port, db_name = :db_name,
             username = :username, password_ciphertext = :password_ciphertext, options_json = :options_json
             WHERE id = :id AND tenant_id = :tenant_id AND company_id = :company_id",
            $values
        );
        $this->flashSession->success('Adapter connection updated.');
        return $this->response->redirect($this->tenantUrl('/adapter'));
    }

    public function connectionTestAction()
    {
        $this->requireCsrf();
        $connection = $this->findConnection((int)$this->dispatcher->getParam('id'));
        if (!$connection) {
            return $this->json(['status' => 'error', 'message' => 'Connection not found.'], 404);
        }
        try {
            $this->adapterConnections->test($connection);
            $this->db->execute(
                "UPDATE adapter_connections SET status = 'active', last_tested_at = NOW(), last_error = NULL WHERE id = :id",
                ['id' => (int)$connection['id']]
            );
            return $this->json(['status' => 'success', 'message' => 'Connection test succeeded.']);
        } catch (Throwable $e) {
            $this->db->execute(
                "UPDATE adapter_connections SET status = 'failed', last_tested_at = NOW(), last_error = :error WHERE id = :id",
                ['id' => (int)$connection['id'], 'error' => 'External connection test failed.']
            );
            return $this->json(['status' => 'error', 'message' => 'Connection test failed.'], 502);
        }
    }

    public function connectionDeleteAction()
    {
        $this->requireCsrf();
        $this->db->execute(
            "DELETE FROM adapter_connections WHERE id = :id AND tenant_id = :tenant_id AND company_id = :company_id",
            ['id' => (int)$this->dispatcher->getParam('id'), 'tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId]
        );
        $this->flashSession->success('Adapter connection deleted.');
        return $this->response->redirect($this->tenantUrl('/adapter'));
    }

    public function endpointCreateAction(): void
    {
        $this->view->setVar('title', 'Add Adapter Endpoint');
        $this->view->setVar('connections', $this->scopeConnections());
        $this->view->pick('adapter/endpoint-form');
    }

    public function endpointStoreAction()
    {
        $this->requireCsrf();
        $apiName = trim((string)$this->request->getPost('api_name', 'string'));
        $query = trim((string)$this->request->getPost('query_template', 'string'));
        $connectionId = (int)$this->request->getPost('connection_id', 'int', 0);
        $connection = $this->findConnection($connectionId);
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{1,99}$/', $apiName)) {
            return $this->adapterError('API name must contain 2-100 letters, numbers, underscores, or hyphens.', '/adapter/endpoints/create');
        }
        if (!$connection) {
            return $this->adapterError('Select a valid connection.', '/adapter/endpoints/create');
        }
        if (strtolower((string)$connection['engine']) === 'mongodb') {
            $definition = json_decode($query, true);
            if ($query === '' || !is_array($definition) || empty($definition['_collection']) || count(array_filter(array_keys($definition), static fn($key) => str_starts_with((string)$key, '_') && $key !== '_collection')) > 0) {
                return $this->adapterError('MongoDB endpoints require a JSON filter with an _collection key.', '/adapter/endpoints/create');
            }
        } elseif ($query === '' || substr_count($query, ';') > 0 || !preg_match('/^\s*(SELECT|WITH)\b/i', $query)) {
            return $this->adapterError('Only one read-only SELECT/WITH query is allowed.', '/adapter/endpoints/create');
        }
        $existing = $this->db->fetchOne('SELECT id FROM adapter_endpoints WHERE api_name = :api_name', DbEnum::FETCH_ASSOC, ['api_name' => $apiName]);
        if ($existing) {
            return $this->adapterError('That API name is already in use.', '/adapter/endpoints/create');
        }
        $plainKey = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $webhookApiKey = (string)$this->request->getPost('webhook_api_key', 'string');
        $this->db->execute(
            "INSERT INTO adapter_endpoints (tenant_id, company_id, connection_id, api_name, query_template, api_key_hash, sync_enabled, sync_cursor_column, webhook_url, webhook_api_key_ciphertext)
             VALUES (:tenant_id, :company_id, :connection_id, :api_name, :query_template, :api_key_hash, :sync_enabled, :sync_cursor_column, :webhook_url, :webhook_api_key_ciphertext)",
            [
                'tenant_id' => $this->currentTenantId,
                'company_id' => $this->currentCompanyId,
                'connection_id' => $connectionId,
                'api_name' => $apiName,
                'query_template' => $query,
                'api_key_hash' => password_hash($plainKey, PASSWORD_DEFAULT),
                'sync_enabled' => $this->request->getPost('sync_enabled', 'int', 0) === 1 ? 1 : 0,
                'sync_cursor_column' => trim((string)$this->request->getPost('sync_cursor_column', 'string')) ?: null,
                'webhook_url' => trim((string)$this->request->getPost('webhook_url', 'string')) ?: null,
                'webhook_api_key_ciphertext' => $webhookApiKey !== '' ? $this->getDI()->get('adapterEncryption')->encrypt($webhookApiKey) : null,
            ]
        );
        $this->view->setVar('newApiKey', $plainKey);
        $this->view->setVar('connections', $this->scopeConnections());
        $this->view->setVar('title', 'Adapter Endpoint Created');
        $this->view->pick('adapter/endpoint-created');
    }

    public function endpointDeleteAction()
    {
        $this->requireCsrf();
        $this->db->execute(
            "DELETE FROM adapter_endpoints WHERE id = :id AND tenant_id = :tenant_id AND company_id = :company_id",
            ['id' => (int)$this->dispatcher->getParam('id'), 'tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId]
        );
        $this->flashSession->success('Adapter endpoint deleted.');
        return $this->response->redirect($this->tenantUrl('/adapter'));
    }

    private function connectionInput(?array $existing = null): array
    {
        $name = trim((string)$this->request->getPost('name', 'string'));
        $engine = strtolower(trim((string)$this->request->getPost('engine', 'string')));
        $host = trim((string)$this->request->getPost('host', 'string'));
        $port = (int)$this->request->getPost('port', 'int', 0);
        $dbName = trim((string)$this->request->getPost('db_name', 'string'));
        $username = trim((string)$this->request->getPost('username', 'string'));
        $password = (string)$this->request->getPost('password', 'string');
        if ($name === '' || $host === '' || $dbName === '' || !in_array($engine, ['mysql', 'mariadb', 'pgsql', 'mongodb'], true)) {
            return ['error' => 'Name, engine, host, and database are required.', 'values' => []];
        }
        if ($port < 1 || $port > 65535) {
            $port = $engine === 'mongodb' ? 27017 : ($engine === 'pgsql' ? 5432 : 3306);
        }
        $ciphertext = $existing['password_ciphertext'] ?? null;
        if ($password !== '') {
            $ciphertext = $this->getDI()->get('adapterEncryption')->encrypt($password);
        }
        return ['error' => null, 'values' => [
            'name' => $name, 'engine' => $engine, 'host' => $host, 'port' => $port,
            'db_name' => $dbName, 'username' => $username, 'password_ciphertext' => $ciphertext,
            'options_json' => null,
        ]];
    }

    private function scopeConnections(): array
    {
        return $this->db->fetchAll(
            "SELECT id, name, engine, host, port, db_name, username, status, last_tested_at, last_error
             FROM adapter_connections WHERE tenant_id = :tenant_id AND company_id = :company_id ORDER BY name",
            DbEnum::FETCH_ASSOC,
            ['tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId]
        );
    }

    private function scopeEndpoints(): array
    {
        return $this->db->fetchAll(
            "SELECT e.id, e.api_name, e.enabled, e.sync_enabled, e.created_at, c.name AS connection_name
             FROM adapter_endpoints e JOIN adapter_connections c ON c.id = e.connection_id
             WHERE e.tenant_id = :tenant_id AND e.company_id = :company_id ORDER BY e.api_name",
            DbEnum::FETCH_ASSOC,
            ['tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId]
        );
    }

    private function findConnection(int $id): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM adapter_connections WHERE id = :id AND tenant_id = :tenant_id AND company_id = :company_id LIMIT 1',
            DbEnum::FETCH_ASSOC,
            ['id' => $id, 'tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId]
        );
        return $row ?: null;
    }

    private function requireCsrf(): void
    {
        if (!AdapterCsrf::valid($this->session, $this->request->getPost('csrf_token', 'string'))) {
            $this->sendFatalError(419, 'Invalid form token.');
        }
    }

    private function adapterError(string $message, string $path)
    {
        $this->flashSession->error($message);
        return $this->response->redirect($this->tenantUrl($path));
    }

    private function json(array $payload, int $status = 200)
    {
        return $this->response->setStatusCode($status)->setContentType('application/json')->setJsonContent($payload);
    }
}
