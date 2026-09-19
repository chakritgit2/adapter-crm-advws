<?php

declare(strict_types=1);

use Phalcon\Db\Enum as DbEnum;

class AdapterController extends TenantBaseController
{
    private AdapterConnectionService $adapterConnections;
    private AdapterLogService $adapterLogs;

    public function initialize(): void
    {
        parent::initialize();
        $user = $this->session->get('auth');
        if (($user['role'] ?? '') !== 'Super Admin') {
            $this->sendFatalError(403, 'Adapter administration requires Super Admin access.');
        }
        $this->adapterConnections = $this->getDI()->get('adapterConnections');
        $this->adapterLogs = $this->getDI()->get('adapterLogs');
        $this->view->setVar('adapterCsrf', AdapterCsrf::token($this->session));
        $this->view->setVar('adapterBaseUrl', $this->tenantUrl('/adapter'));
    }

    public function indexAction(): void
    {
        $this->view->setVar('title', 'Universal Data Adapter');
        $connections = $this->scopeConnections();
        $endpoints = $this->scopeEndpoints();
        $endpointsByConnection = [];
        foreach ($endpoints as $endpoint) {
            $endpointsByConnection[(int)$endpoint['connection_id']][] = $endpoint;
        }

        $activeConnections = 0;
        $failedConnections = 0;
        foreach ($connections as $connection) {
            if ($connection['status'] === 'active') {
                $activeConnections++;
            } elseif ($connection['status'] === 'failed') {
                $failedConnections++;
            }
        }
        $enabledEndpoints = count(array_filter($endpoints, static fn($endpoint) => (int)$endpoint['enabled'] === 1));

        $this->view->setVar('connections', $connections);
        $this->view->setVar('endpoints', $endpoints);
        $this->view->setVar('endpointsByConnection', $endpointsByConnection);
        $this->view->setVar('adapterStats', [
            'connections' => count($connections),
            'activeConnections' => $activeConnections,
            'failedConnections' => $failedConnections,
            'endpoints' => count($endpoints),
            'enabledEndpoints' => $enabledEndpoints,
        ]);
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
        $startedAt = microtime(true);
        try {
            $this->adapterConnections->test($connection);
            $this->db->execute(
                "UPDATE adapter_connections SET status = 'active', last_tested_at = NOW(), last_error = NULL WHERE id = :id",
                ['id' => (int)$connection['id']]
            );
            $this->adapterLogs->connection([
                'tenant_id' => $this->currentTenantId,
                'company_id' => $this->currentCompanyId,
                'connection_id' => (int)$connection['id'],
                'event_type' => 'connection_test',
                'status' => 'success',
                'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000),
                'message' => 'Connection test succeeded.',
            ]);
            return $this->json([
                'status' => 'success',
                'message' => 'Connection test succeeded.',
                'connectionStatus' => 'active',
                'lastTestedAt' => $this->connectionLastTestedAt((int)$connection['id']),
            ]);
        } catch (Throwable $e) {
            $this->db->execute(
                "UPDATE adapter_connections SET status = 'failed', last_tested_at = NOW(), last_error = :error WHERE id = :id",
                ['id' => (int)$connection['id'], 'error' => 'External connection test failed.']
            );
            $this->adapterLogs->connection([
                'tenant_id' => $this->currentTenantId,
                'company_id' => $this->currentCompanyId,
                'connection_id' => (int)$connection['id'],
                'event_type' => 'connection_test',
                'status' => 'failure',
                'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000),
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ]);
            return $this->json([
                'status' => 'error',
                'message' => 'Connection test failed.',
                'connectionStatus' => 'failed',
                'lastTestedAt' => $this->connectionLastTestedAt((int)$connection['id']),
            ], 502);
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

    public function logsAction(): void
    {
        $this->view->setVar('title', 'Adapter Logs');
        $this->view->setVar('connectionLogs', $this->connectionLogs());
        $this->view->setVar('endpointLogs', $this->endpointLogs());
        $this->view->pick('adapter/logs');
    }

    public function endpointCreateAction(): void
    {
        $this->view->setVar('title', 'Add Adapter Endpoint');
        $this->view->setVar('endpoint', null);
        $this->view->setVar('selectedConnectionId', (int)$this->request->getQuery('connection_id', 'int', 0));
        $this->view->setVar('connections', $this->scopeConnections());
        $this->view->pick('adapter/endpoint-form');
    }

    public function endpointStoreAction()
    {
        $this->requireCsrf();
        $input = $this->endpointInput();
        if ($input['error']) {
            return $this->adapterError($input['error'], $input['path']);
        }

        $plainKey = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->db->execute(
            "INSERT INTO adapter_endpoints (tenant_id, company_id, connection_id, api_name, query_template, api_key_hash, enabled, sync_enabled, sync_cursor_column, webhook_url, webhook_api_key_ciphertext)
             VALUES (:tenant_id, :company_id, :connection_id, :api_name, :query_template, :api_key_hash, :enabled, :sync_enabled, :sync_cursor_column, :webhook_url, :webhook_api_key_ciphertext)",
            $input['values'] + [
                'tenant_id' => $this->currentTenantId,
                'company_id' => $this->currentCompanyId,
                'api_key_hash' => password_hash($plainKey, PASSWORD_DEFAULT),
            ]
        );
        $this->view->setVar('newApiKey', $plainKey);
        $this->view->setVar('connections', $this->scopeConnections());
        $this->view->setVar('title', 'Adapter Endpoint Created');
        $this->view->pick('adapter/endpoint-created');
    }

    public function endpointEditAction()
    {
        $endpoint = $this->findEndpoint((int)$this->dispatcher->getParam('id'));
        if (!$endpoint) {
            $this->flashSession->error('Endpoint not found.');
            return $this->response->redirect($this->tenantUrl('/adapter'));
        }
        $this->view->setVar('title', 'Edit Adapter Endpoint');
        $this->view->setVar('endpoint', $endpoint);
        $this->view->setVar('selectedConnectionId', (int)$endpoint['connection_id']);
        $this->view->setVar('connections', $this->scopeConnections());
        $this->view->pick('adapter/endpoint-form');
    }

    public function endpointUpdateAction()
    {
        $this->requireCsrf();
        $endpoint = $this->findEndpoint((int)$this->dispatcher->getParam('id'));
        if (!$endpoint) {
            return $this->adapterError('Endpoint not found.', '/adapter');
        }
        $input = $this->endpointInput($endpoint);
        if ($input['error']) {
            return $this->adapterError($input['error'], $input['path']);
        }

        $this->db->execute(
            "UPDATE adapter_endpoints SET connection_id = :connection_id, api_name = :api_name, query_template = :query_template,
             enabled = :enabled, sync_enabled = :sync_enabled, sync_cursor_column = :sync_cursor_column,
             webhook_url = :webhook_url, webhook_api_key_ciphertext = :webhook_api_key_ciphertext
             WHERE id = :id AND tenant_id = :tenant_id AND company_id = :company_id",
            $input['values'] + [
                'id' => (int)$endpoint['id'],
                'tenant_id' => $this->currentTenantId,
                'company_id' => $this->currentCompanyId,
            ]
        );
        $this->flashSession->success('Adapter endpoint updated.');
        return $this->response->redirect($this->tenantUrl('/adapter'));
    }

    public function endpointToggleAction()
    {
        $this->requireCsrf();
        $endpoint = $this->findEndpoint((int)$this->dispatcher->getParam('id'));
        if (!$endpoint) {
            return $this->adapterError('Endpoint not found.', '/adapter');
        }
        $this->db->execute(
            "UPDATE adapter_endpoints SET enabled = :enabled WHERE id = :id AND tenant_id = :tenant_id AND company_id = :company_id",
            [
                'enabled' => (int)$endpoint['enabled'] === 1 ? 0 : 1,
                'id' => (int)$endpoint['id'],
                'tenant_id' => $this->currentTenantId,
                'company_id' => $this->currentCompanyId,
            ]
        );
        $this->flashSession->success('Adapter endpoint updated.');
        return $this->response->redirect($this->tenantUrl('/adapter'));
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

    private function endpointInput(?array $existing = null): array
    {
        $apiName = trim((string)$this->request->getPost('api_name', 'string'));
        $query = trim((string)$this->request->getPost('query_template', 'string', ''));
        $connectionId = (int)$this->request->getPost('connection_id', 'int', 0);
        $connection = $this->findConnection($connectionId);
        $redirectPath = $existing
            ? '/adapter/endpoints/edit/' . (int)$existing['id']
            : '/adapter/endpoints/create' . ($connectionId > 0 ? '?connection_id=' . $connectionId : '');

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{1,99}$/', $apiName)) {
            return ['error' => 'API name must contain 2-100 letters, numbers, underscores, or hyphens.', 'path' => $redirectPath, 'values' => []];
        }
        if (!$connection) {
            return ['error' => 'Select a valid connection.', 'path' => $redirectPath, 'values' => []];
        }
        $queryWithoutVariables = preg_replace('/\{\{\s*[A-Za-z_][A-Za-z0-9_]*\s*\}\}/', '', $query);
        if (str_contains((string)$queryWithoutVariables, '{{') || str_contains((string)$queryWithoutVariables, '}}')) {
            return ['error' => 'Query variables must use the {{variable_name}} format.', 'path' => $redirectPath, 'values' => []];
        }
        $templateParameters = [];
        if (strtolower((string)$connection['engine']) === 'mongodb') {
            if ($query === '') {
                return ['error' => 'MongoDB query template is required.', 'path' => $redirectPath, 'values' => []];
            }
            try {
                $definition = json_decode($query, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                return ['error' => 'MongoDB query template must be a JSON object. Shell syntax such as db.collection.aggregate(...) is not supported.', 'path' => $redirectPath, 'values' => []];
            }
            $collection = is_array($definition) ? ($definition['_collection'] ?? null) : null;
            if (!is_string($collection) || trim($collection) === '') {
                return ['error' => 'MongoDB endpoints require a static _collection string.', 'path' => $redirectPath, 'values' => []];
            }
            if (preg_match('/\{\{|\}\}/', $collection)) {
                return ['error' => 'MongoDB _collection cannot use request variables.', 'path' => $redirectPath, 'values' => []];
            }
            $invalidReservedKeys = array_values(array_filter(
                array_keys($definition),
                static fn($key) => str_starts_with((string)$key, '_') && !in_array($key, ['_collection', '_pipeline'], true)
            ));
            if ($invalidReservedKeys) {
                return ['error' => 'Unsupported MongoDB option keys: ' . implode(', ', array_map('strval', $invalidReservedKeys)), 'path' => $redirectPath, 'values' => []];
            }
            try {
                if (array_key_exists('_pipeline', $definition)) {
                    if (count($definition) !== 2 || !is_array($definition['_pipeline'])) {
                        throw new InvalidArgumentException('MongoDB aggregation templates require only _collection and a _pipeline array.');
                    }
                    $this->adapterConnections->validateMongoPipeline($definition['_pipeline']);
                }
                $templateParameters = $this->adapterConnections->mongoPlaceholders($definition);
            } catch (InvalidArgumentException $e) {
                return ['error' => $e->getMessage(), 'path' => $redirectPath, 'values' => []];
            }
        } elseif ($query === '' || substr_count($query, ';') > 0 || !preg_match('/^\s*(SELECT|WITH)\b/i', $query)) {
            return ['error' => 'Only one read-only SELECT/WITH query is allowed.', 'path' => $redirectPath, 'values' => []];
        } else {
            $templateParameters = $this->adapterConnections->placeholders($query);
        }
        $reservedParameters = array_intersect(['apikey', '_url'], $templateParameters);
        if ($reservedParameters) {
            return ['error' => 'These query variable names are reserved: ' . implode(', ', $reservedParameters), 'path' => $redirectPath, 'values' => []];
        }

        $duplicateSql = 'SELECT id FROM adapter_endpoints WHERE api_name = :api_name';
        $duplicateParams = ['api_name' => $apiName];
        if ($existing) {
            $duplicateSql .= ' AND id <> :id';
            $duplicateParams['id'] = (int)$existing['id'];
        }
        if ($this->db->fetchOne($duplicateSql, DbEnum::FETCH_ASSOC, $duplicateParams)) {
            return ['error' => 'That API name is already in use.', 'path' => $redirectPath, 'values' => []];
        }

        $webhookApiKey = (string)$this->request->getPost('webhook_api_key', 'string');
        $webhookCiphertext = $existing['webhook_api_key_ciphertext'] ?? null;
        if ($webhookApiKey !== '') {
            $webhookCiphertext = $this->getDI()->get('adapterEncryption')->encrypt($webhookApiKey);
        }

        return ['error' => null, 'path' => $redirectPath, 'values' => [
            'connection_id' => $connectionId,
            'api_name' => $apiName,
            'query_template' => $query,
            'enabled' => $this->request->getPost('enabled', 'int', 0) === 1 ? 1 : 0,
            'sync_enabled' => $this->request->getPost('sync_enabled', 'int', 0) === 1 ? 1 : 0,
            'sync_cursor_column' => trim((string)$this->request->getPost('sync_cursor_column', 'string')) ?: null,
            'webhook_url' => trim((string)$this->request->getPost('webhook_url', 'string')) ?: null,
            'webhook_api_key_ciphertext' => $webhookCiphertext,
        ]];
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
        $optionsJson = trim((string)$this->request->getPost('options_json', 'string'));
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
        if ($optionsJson === '') {
            $optionsJson = $existing['options_json'] ?? null;
        } else {
            $options = json_decode($optionsJson, true);
            if (!is_array($options)) {
                return ['error' => 'Driver options must be valid JSON.', 'values' => []];
            }
            $optionsJson = json_encode($options, JSON_UNESCAPED_SLASHES);
        }
        return ['error' => null, 'values' => [
            'name' => $name, 'engine' => $engine, 'host' => $host, 'port' => $port,
            'db_name' => $dbName, 'username' => $username, 'password_ciphertext' => $ciphertext,
            'options_json' => $optionsJson,
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
            "SELECT e.id, e.connection_id, e.api_name, e.enabled, e.sync_enabled, e.created_at, c.name AS connection_name
             FROM adapter_endpoints e JOIN adapter_connections c ON c.id = e.connection_id
             WHERE e.tenant_id = :tenant_id AND e.company_id = :company_id ORDER BY e.api_name",
            DbEnum::FETCH_ASSOC,
            ['tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId]
        );
    }

    private function connectionLogs(): array
    {
        return $this->db->fetchAll(
            "SELECT l.id, l.created_at, l.connection_id, l.event_type, l.status, l.duration_ms, l.message,
                    c.name AS connection_name, c.engine, c.host, c.port, c.db_name
             FROM adapter_connection_logs l
             LEFT JOIN adapter_connections c ON c.id = l.connection_id
             WHERE l.tenant_id = :tenant_id AND l.company_id = :company_id
             ORDER BY l.created_at DESC, l.id DESC LIMIT 100",
            DbEnum::FETCH_ASSOC,
            ['tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId]
        );
    }

    private function endpointLogs(): array
    {
        return $this->db->fetchAll(
            "SELECT l.id, l.created_at, l.endpoint_id, l.api_name, l.status_code, l.auth_result, l.row_count,
                    l.duration_ms, l.request_id, l.error_code, l.client_ip,
                    e.connection_id, c.name AS connection_name, c.engine, c.host, c.port, c.db_name
             FROM adapter_endpoint_logs l
             LEFT JOIN adapter_endpoints e ON e.id = l.endpoint_id
             LEFT JOIN adapter_connections c ON c.id = e.connection_id
             WHERE l.tenant_id = :tenant_id AND l.company_id = :company_id
             ORDER BY l.created_at DESC, l.id DESC LIMIT 100",
            DbEnum::FETCH_ASSOC,
            ['tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId]
        );
    }

    private function connectionLastTestedAt(int $id): ?string
    {
        $row = $this->db->fetchOne(
            'SELECT last_tested_at FROM adapter_connections WHERE id = :id AND tenant_id = :tenant_id AND company_id = :company_id LIMIT 1',
            DbEnum::FETCH_ASSOC,
            ['id' => $id, 'tenant_id' => $this->currentTenantId, 'company_id' => $this->currentCompanyId]
        );
        return $row['last_tested_at'] ?? null;
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

    private function findEndpoint(int $id): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM adapter_endpoints WHERE id = :id AND tenant_id = :tenant_id AND company_id = :company_id LIMIT 1',
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
