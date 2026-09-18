<?php

declare(strict_types=1);

class AdapterLogService
{
    public function __construct(private $db)
    {
    }

    public function connection(array $data): void
    {
        $this->safeInsert(
            "INSERT INTO adapter_connection_logs
             (tenant_id, company_id, connection_id, event_type, status, duration_ms, message, created_at)
             VALUES (:tenant_id, :company_id, :connection_id, :event_type, :status, :duration_ms, :message, NOW())",
            [
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'connection_id' => $data['connection_id'] ?? null,
                'event_type' => $data['event_type'] ?? 'test',
                'status' => $data['status'] ?? 'failure',
                'duration_ms' => $data['duration_ms'] ?? 0,
                'message' => $this->message($data['message'] ?? null),
            ]
        );
    }

    public function endpoint(array $data): void
    {
        $this->safeInsert(
            "INSERT INTO adapter_endpoint_logs
             (tenant_id, company_id, endpoint_id, api_name, request_id, status_code, auth_result, row_count, duration_ms, error_code, client_ip, created_at)
             VALUES (:tenant_id, :company_id, :endpoint_id, :api_name, :request_id, :status_code, :auth_result, :row_count, :duration_ms, :error_code, :client_ip, NOW())",
            [
                'tenant_id' => $data['tenant_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'endpoint_id' => $data['endpoint_id'] ?? null,
                'api_name' => substr((string)($data['api_name'] ?? ''), 0, 100),
                'request_id' => substr((string)($data['request_id'] ?? ''), 0, 64),
                'status_code' => (int)($data['status_code'] ?? 500),
                'auth_result' => $data['auth_result'] ?? 'unknown',
                'row_count' => (int)($data['row_count'] ?? 0),
                'duration_ms' => (int)($data['duration_ms'] ?? 0),
                'error_code' => $data['error_code'] ? substr((string)$data['error_code'], 0, 80) : null,
                'client_ip' => $this->clientIp(),
            ]
        );
    }

    private function safeInsert(string $sql, array $bind): void
    {
        try {
            $this->db->execute($sql, $bind);
        } catch (Throwable $e) {
            // Logging must never replace the original adapter response.
        }
    }

    private function message(?string $message): ?string
    {
        return $message ? substr($message, 0, 500) : null;
    }

    private function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return $ip ? substr((string)$ip, 0, 45) : null;
    }
}
