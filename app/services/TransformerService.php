<?php

declare(strict_types=1);

class TransformerService
{
    public function transform(array $rows, array $meta): array
    {
        $data = [];
        foreach ($rows as $row) {
            $record = is_object($row) ? json_decode(json_encode($row), true) : $row;
            if (!is_array($record)) {
                throw new InvalidArgumentException('External record must be an array or object.');
            }
            $identifier = $record['external_record_id'] ?? $record['id'] ?? $record['_id'] ?? $record['uuid'] ?? null;
            if (is_object($identifier)) {
                $identifier = (string)$identifier;
            }
            if ($identifier === null || $identifier === '') {
                throw new InvalidArgumentException('External record is missing an identifier.');
            }
            $record['external_record_id'] = (string)$identifier;
            $data[] = $record;
        }

        return [
            'status' => 'success',
            'meta' => [
                'tenant_public_id' => (string)($meta['tenant_public_id'] ?? ''),
                'companies_public_id' => (string)($meta['companies_public_id'] ?? ''),
                'source' => (string)($meta['source'] ?? 'adapter'),
                'fetched_at' => gmdate('c'),
            ],
            'data' => $data,
        ];
    }
}
