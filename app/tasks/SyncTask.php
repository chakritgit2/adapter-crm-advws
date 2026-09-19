<?php

declare(strict_types=1);

use Phalcon\Cli\Task;
use Phalcon\Db\Enum as DbEnum;

class SyncTask extends Task
{
    public function initialize(): void
    {
        $this->db = $this->di->get('db');
    }

    public function runAction(?string $endpointId = null): void
    {
        $where = 'e.sync_enabled = 1 AND e.enabled = 1 AND e.webhook_url IS NOT NULL';
        $bind = [];
        if ($endpointId !== null && ctype_digit($endpointId)) {
            $where .= ' AND e.id = :endpoint_id';
            $bind['endpoint_id'] = (int)$endpointId;
        }
        $endpoints = $this->db->fetchAll(
            "SELECT e.*, c.host, c.port, c.engine, c.db_name, c.username, c.password_ciphertext, c.options_json,
                    t.slug AS tenant_public_id, co.slug AS companies_public_id
             FROM adapter_endpoints e
             JOIN adapter_connections c ON c.id = e.connection_id AND c.status = 'active'
             JOIN tenants t ON t.id = e.tenant_id AND t.status = 'active'
             LEFT JOIN companies co ON co.id = e.company_id
             WHERE {$where}",
            DbEnum::FETCH_ASSOC,
            $bind
        );

        foreach ($endpoints as $endpoint) {
            try {
                $this->syncEndpoint($endpoint);
                echo "Synced endpoint {$endpoint['api_name']}\n";
            } catch (Throwable $e) {
                echo "Failed endpoint {$endpoint['api_name']}. Check the protected application logs.\n";
            }
        }
    }

    private function syncEndpoint(array $endpoint): void
    {
        $lease = bin2hex(random_bytes(16));
        $this->db->execute(
            "INSERT INTO adapter_sync_checkpoints (endpoint_id, status, lease_token, lease_expires_at, last_attempted_at)
             VALUES (:id, 'running', :lease, :expires, NOW())
             ON DUPLICATE KEY UPDATE
                status = IF(lease_expires_at IS NULL OR lease_expires_at < NOW(), 'running', status),
                lease_token = IF(lease_expires_at IS NULL OR lease_expires_at < NOW(), VALUES(lease_token), lease_token),
                lease_expires_at = IF(lease_expires_at IS NULL OR lease_expires_at < NOW(), VALUES(lease_expires_at), lease_expires_at),
                last_attempted_at = NOW()",
            ['id' => (int)$endpoint['id'], 'lease' => $lease, 'expires' => date('Y-m-d H:i:s', time() + 300)]
        );
        $checkpoint = $this->db->fetchOne('SELECT * FROM adapter_sync_checkpoints WHERE endpoint_id = :id', DbEnum::FETCH_ASSOC, ['id' => (int)$endpoint['id']]);
        if (!$checkpoint || $checkpoint['lease_token'] !== $lease) {
            return;
        }

        try {
            $parameters = [];
            $cursor = $checkpoint['cursor_value'];
            $query = (string)$endpoint['query_template'];
            $adapterConnections = $this->getDI()->get('adapterConnections');
            if (strtolower((string)$endpoint['engine']) === 'mongodb') {
                $definition = json_decode($query, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($definition) || empty($definition['_collection'])) {
                    throw new InvalidArgumentException('MongoDB endpoint definition is invalid.');
                }
                $placeholders = $adapterConnections->mongoPlaceholders($definition);
                if (in_array('last_cursor', $placeholders, true)) {
                    $parameters['last_cursor'] = $cursor ?: '0';
                } elseif (in_array('last_sync_timestamp', $placeholders, true)) {
                    $parameters['last_sync_timestamp'] = $cursor ?: '1970-01-01 00:00:00';
                }
                $rows = $adapterConnections->executeMongo($endpoint, $definition, $parameters);
            } else {
                $placeholders = $adapterConnections->placeholders($query);
                if (in_array('last_cursor', $placeholders, true)) {
                    $parameters['last_cursor'] = $cursor ?: '0';
                } elseif (in_array('last_sync_timestamp', $placeholders, true)) {
                    $parameters['last_sync_timestamp'] = $cursor ?: '1970-01-01 00:00:00';
                }
                $rows = $adapterConnections->execute($endpoint, $query, $parameters);
            }
            $payload = $this->getDI()->get('transformer')->transform($rows, [
                'tenant_public_id' => $endpoint['tenant_public_id'],
                'companies_public_id' => $endpoint['companies_public_id'] ?? '',
                'source' => $endpoint['api_name'],
            ]);
            $this->deliver($endpoint, $payload);

            $newCursor = $cursor;
            $cursorColumn = (string)($endpoint['sync_cursor_column'] ?? '');
            if ($cursorColumn !== '' && $rows) {
                $last = $rows[count($rows) - 1];
                $newCursor = isset($last[$cursorColumn]) ? (string)$last[$cursorColumn] : $cursor;
            }
            $this->db->execute(
                "UPDATE adapter_sync_checkpoints SET cursor_value = :cursor, status = 'idle', last_successful_at = NOW(), last_error = NULL, lease_token = NULL, lease_expires_at = NULL WHERE endpoint_id = :id AND lease_token = :lease",
                ['cursor' => $newCursor, 'id' => (int)$endpoint['id'], 'lease' => $lease]
            );
        } catch (Throwable $e) {
            $this->db->execute(
                "UPDATE adapter_sync_checkpoints SET status = 'failed', last_error = :error, lease_token = NULL, lease_expires_at = NULL WHERE endpoint_id = :id AND lease_token = :lease",
                ['error' => 'Adapter sync failed.', 'id' => (int)$endpoint['id'], 'lease' => $lease]
            );
            throw $e;
        }
    }

    private function deliver(array $endpoint, array $payload): void
    {
        $url = (string)$endpoint['webhook_url'];
        if (!str_starts_with(strtolower($url), 'https://')) {
            throw new RuntimeException('Sync webhooks must use HTTPS.');
        }
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if (!empty($endpoint['webhook_api_key_ciphertext'])) {
            $key = $this->getDI()->get('adapterEncryption')->decrypt($endpoint['webhook_api_key_ciphertext']);
            $headers[] = 'X-API-Key: ' . $key;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Webhook delivery failed' . ($error ? ': ' . $error : ' with HTTP ' . $status));
        }
    }
}
