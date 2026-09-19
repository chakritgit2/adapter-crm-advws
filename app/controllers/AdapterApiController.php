<?php

declare(strict_types=1);

use Phalcon\Db\Enum as DbEnum;

class AdapterApiController extends \Phalcon\Mvc\Controller
{
    public function fetchAction()
    {
        $apiName = (string)$this->dispatcher->getParam('api_name', 'string', '');
        $requestId = bin2hex(random_bytes(12));
        $startedAt = microtime(true);
        $endpoint = $this->db->fetchOne(
            "SELECT e.*, c.engine, c.host, c.port, c.db_name, c.username, c.password_ciphertext,
                    t.id AS tenant_id, e.company_id, t.slug AS tenant_public_id, co.slug AS companies_public_id
             FROM adapter_endpoints e
             JOIN adapter_connections c ON c.id = e.connection_id AND c.status = 'active'
             JOIN tenants t ON t.id = e.tenant_id AND t.status = 'active'
             LEFT JOIN companies co ON co.id = e.company_id
             WHERE e.api_name = :api_name AND e.enabled = 1 LIMIT 1",
            DbEnum::FETCH_ASSOC,
            ['api_name' => $apiName]
        );

        if (!$endpoint) {
            return $this->respond(
                ['status' => 'error', 'error' => ['code' => 'NOT_FOUND', 'message' => 'Endpoint not found.']],
                404,
                [
                    'api_name' => $apiName,
                    'request_id' => $requestId,
                    'auth_result' => 'not_found',
                    'error_code' => 'NOT_FOUND',
                    'started_at' => $startedAt,
                ]
            );
        }

        $apiKey = trim((string)$this->request->getHeader('X-API-Key'));
        if ($apiKey === '') {
            $apiKey = trim((string)$this->request->getQuery('apikey', 'string', ''));
        }
        if ($apiKey === '' || !password_verify($apiKey, (string)$endpoint['api_key_hash'])) {
            return $this->respond(
                ['status' => 'error', 'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Invalid API credentials.']],
                401,
                [
                    'tenant_id' => $endpoint['tenant_id'],
                    'company_id' => $endpoint['company_id'],
                    'endpoint_id' => $endpoint['id'],
                    'api_name' => $apiName,
                    'request_id' => $requestId,
                    'auth_result' => 'invalid',
                    'error_code' => 'UNAUTHORIZED',
                    'started_at' => $startedAt,
                ]
            );
        }

        try {
            $queryParams = $this->request->getQuery();
            unset($queryParams['apikey'], $queryParams['_url']);
            $connection = $endpoint;
            $engine = strtolower((string)$endpoint['engine']);
            if ($engine === 'mongodb') {
                $definition = json_decode((string)$endpoint['query_template'], true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($definition) || empty($definition['_collection'])) {
                    throw new InvalidArgumentException('MongoDB endpoint definition is invalid.');
                }
                $rows = $this->getDI()->get('adapterConnections')->executeMongo($connection, $definition, $queryParams);
            } else {
                $rows = $this->getDI()->get('adapterConnections')->execute($connection, (string)$endpoint['query_template'], $queryParams);
            }
            $maxRows = (int)$this->config->path('adapter.maxRows', 1000);
            if (count($rows) > $maxRows) {
                throw new RuntimeException('Result exceeds the configured row limit.');
            }
            $payload = $this->getDI()->get('transformer')->transform($rows, [
                'tenant_public_id' => $endpoint['tenant_public_id'],
                'companies_public_id' => $endpoint['companies_public_id'] ?? '',
                'source' => $endpoint['api_name'],
            ]);
            $this->getDI()->get('adapterLogs')->connection([
                'tenant_id' => $endpoint['tenant_id'],
                'company_id' => $endpoint['company_id'],
                'connection_id' => $endpoint['connection_id'],
                'event_type' => 'endpoint_query',
                'status' => 'success',
                'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000),
                'message' => 'External query succeeded.',
            ]);
            return $this->respond($payload, 200, [
                'tenant_id' => $endpoint['tenant_id'],
                'company_id' => $endpoint['company_id'],
                'endpoint_id' => $endpoint['id'],
                'api_name' => $apiName,
                'request_id' => $requestId,
                'auth_result' => 'accepted',
                'row_count' => count($rows),
                'started_at' => $startedAt,
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respond(
                ['status' => 'error', 'error' => ['code' => 'BAD_REQUEST', 'message' => $e->getMessage()]],
                400,
                [
                    'tenant_id' => $endpoint['tenant_id'],
                    'company_id' => $endpoint['company_id'],
                    'endpoint_id' => $endpoint['id'],
                    'api_name' => $apiName,
                    'request_id' => $requestId,
                    'auth_result' => 'accepted',
                    'error_code' => 'BAD_REQUEST',
                    'started_at' => $startedAt,
                ]
            );
        } catch (Throwable $e) {
            $this->getDI()->get('adapterLogs')->connection([
                'tenant_id' => $endpoint['tenant_id'],
                'company_id' => $endpoint['company_id'],
                'connection_id' => $endpoint['connection_id'],
                'event_type' => 'endpoint_query',
                'status' => 'failure',
                'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000),
                'message' => 'External query failed: ' . $e->getMessage(),
            ]);
            return $this->respond(
                ['status' => 'error', 'error' => ['code' => 'SOURCE_UNAVAILABLE', 'message' => 'The configured source is temporarily unavailable.']],
                502,
                [
                    'tenant_id' => $endpoint['tenant_id'],
                    'company_id' => $endpoint['company_id'],
                    'endpoint_id' => $endpoint['id'],
                    'api_name' => $apiName,
                    'request_id' => $requestId,
                    'auth_result' => 'accepted',
                    'error_code' => 'SOURCE_UNAVAILABLE',
                    'started_at' => $startedAt,
                ]
            );
        }
    }

    private function respond(array $payload, int $status, array $log = [])
    {
        if ($log) {
            $log['status_code'] = $status;
            $log['duration_ms'] = (int)round((microtime(true) - (float)$log['started_at']) * 1000);
            unset($log['started_at']);
            $this->getDI()->get('adapterLogs')->endpoint($log);
        }
        return $this->response
            ->setStatusCode($status)
            ->setHeader('X-Request-ID', (string)($log['request_id'] ?? ''))
            ->setContentType('application/json')
            ->setJsonContent($payload);
    }
}
