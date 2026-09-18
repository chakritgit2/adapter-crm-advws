<?php

declare(strict_types=1);

trait TranslateUuidTrait
{
    /**
     * Automatically generate a UUIDv4 before creating a new record.
     */
    public function beforeValidationOnCreate(): void
    {
        if (empty($this->public_id)) {
            $this->public_id = self::generateUuid();
        }
    }

    /**
     * Find a record by its public UUID, strictly enforcing multi-tenant isolation.
     *
     * @param string $uuid
     * @param int|null $companyId
     * @return \Phalcon\Mvc\ModelInterface|null
     */
    public static function findByUuid(string $uuid, ?int $companyId = null): ?\Phalcon\Mvc\ModelInterface
    {
        $conditions = 'public_id = :uuid:';
        $bind = ['uuid' => $uuid];

        if ($companyId !== null) {
            $conditions .= ' AND company_id = :company_id:';
            $bind['company_id'] = $companyId;
        }

        $record = self::findFirst([
            'conditions' => $conditions,
            'bind' => $bind,
        ]);

        return $record ?: null;
    }

    /**
     * Translate a public UUID to an internal integer ID.
     *
     * @param string $uuid
     * @param int|null $companyId
     * @return int|null
     */
    public static function getIdByUuid(string $uuid, ?int $companyId = null): ?int
    {
        $record = self::findByUuid($uuid, $companyId);
        return $record ? (int) $record->id : null;
    }

    /**
     * Generate a UUIDv4 string using native PHP (no external dependencies).
     *
     * @return string
     */
    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
