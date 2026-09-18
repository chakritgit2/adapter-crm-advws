<?php

declare(strict_types=1);

use Phalcon\Db\Enum as DbEnum;

class AdapterApiController extends \Phalcon\Mvc\Controller
{
    public function fetchAction()
    {
        $apiName = (string)$this->dispatcher->getParam('api_name', 'string', '');
        $endpoint = $this->db->fetchOne(
            "SELECT e.*, c.engine, c.host, c.port, c.db_name, c.username, c.password_ciphertext,
                    t.slug AS tenant_public_id, co.slug AS companies_public_id
             FROM adapter_endpoints e
             JOIN adapter_connections c ON c.id = e.connection_id AND c.status = 'active'
             JOIN tenants t ON t.id = e.tenant_id AND t.status = 'active'
             LEFT JOIN companies co ON co.id = e.company_id
             WHERE e.api_name = :api_name AND e.enabled = 1 LIMIT 1",
            DbEnum::FETCH_ASSOC,
            ['api_name' => $apiName]
        );

        if (!$endpoint) {
            return $this->respond(['status' => 'error', 'error' => ['code' => 'NOT_FOUND', 'message' => 'Endpoint not found.']], 404);
        }

        $apiKey = trim((string)$this->request->getHeader('X-API-Key'));
        if ($apiKey === '') {
            $apiKey = trim((string)$this->request->getQuery('apikey', 'string', ''));
        }
        if ($apiKey === '' || !password_verify($apiKey, (string)$endpoint['api_key_hash'])) {
            return $this->respond(['status' => 'error', 'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Invalid API credentials.']], 401);
        }

        try {
            $queryParams = $this->request->getQuery();
            unset($queryParams['apikey']);
            $connection = $endpoint;
            $engine = strtolower((string)$endpoint['engine']);
            if ($engine === 'mongodb') {
                $definition = json_decode((string)$endpoint['query_template'], true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($definition) || empty($definition['_collection'])) {
                    throw new InvalidArgumentException('MongoDB endpoint definition is invalid.');
                }
                if (!empty($queryParams)) {
                    throw new InvalidArgumentException('MongoDB dynamic filters are not enabled for this endpoint.');
                }
                $rows = $this->getDI()->get('adapterConnections')->executeMongo($connection, $definition);
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
            return $this->respond($payload, 200);
        } catch (InvalidArgumentException $e) {
            return $this->respond(['status' => 'error', 'error' => ['code' => 'BAD_REQUEST', 'message' => $e->getMessage()]], 400);
        } catch (Throwable $e) {
            return $this->respond(['status' => 'error', 'error' => ['code' => 'SOURCE_UNAVAILABLE', 'message' => 'The configured source is temporarily unavailable.']], 502);
        }
    }

    private function respond(array $payload, int $status)
    {
        return $this->response->setStatusCode($status)->setContentType('application/json')->setJsonContent($payload);
    }
}
