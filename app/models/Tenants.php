<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class Tenants extends Model
{
    public const TABLE_NAME = 'tenants';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    public ?int $id = null;
    public string $name;
    public string $slug;
    public string $status = self::STATUS_ACTIVE;
    public ?string $plan = null;
    public ?string $billing_email = null;
    public ?string $created_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->hasMany('id', Companies::class, 'tenant_id', [
            'alias' => 'companies',
        ]);

        $this->hasMany('id', TenantUserMap::class, 'tenant_id', [
            'alias' => 'userMaps',
        ]);
    }

    public static function findFirstBySlug(string $slug): ?self
    {
        return self::findFirst([
            'conditions' => 'slug = :slug:',
            'bind' => ['slug' => $slug],
        ]) ?: null;
    }
}
