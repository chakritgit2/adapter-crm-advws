<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class Companies extends Model
{
    public const TABLE_NAME = 'companies';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    public ?int $id = null;
    public ?int $tenant_id = null;
    public string $name;
    public string $slug;
    public string $status = self::STATUS_ACTIVE;
    public ?string $created_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo(
            'tenant_id',
            Tenants::class,
            'id',
            ['alias' => 'Tenant']
        );
    }

    public static function findFirstBySlug(string $slug): ?self
    {
        return self::findFirst([
            'conditions' => 'slug = :slug:',
            'bind' => ['slug' => $slug],
        ]) ?: null;
    }
}
