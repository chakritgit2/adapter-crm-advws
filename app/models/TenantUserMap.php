<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class TenantUserMap extends Model
{
    public const TABLE_NAME = 'tenant_user_map';

    public const ROLE_TENANT_ADMIN = 'tenant_admin';
    public const ROLE_TENANT_MEMBER = 'tenant_member';

    public int $admin_user_id;
    public int $tenant_id;
    public string $role = self::ROLE_TENANT_MEMBER;
    public ?string $created_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo(
            'admin_user_id',
            AdminUsers::class,
            'id',
            ['alias' => 'AdminUser']
        );

        $this->belongsTo(
            'tenant_id',
            Tenants::class,
            'id',
            ['alias' => 'Tenant']
        );
    }

    public static function userHasTenantAccess(int $adminUserId, int $tenantId): bool
    {
        return (bool) self::findFirst([
            'conditions' => 'admin_user_id = :admin_user_id: AND tenant_id = :tenant_id:',
            'bind' => [
                'admin_user_id' => $adminUserId,
                'tenant_id' => $tenantId,
            ],
        ]);
    }

    public static function userIsTenantAdmin(int $adminUserId, int $tenantId): bool
    {
        return (bool) self::findFirst([
            'conditions' => 'admin_user_id = :admin_user_id: AND tenant_id = :tenant_id: AND role = :role:',
            'bind' => [
                'admin_user_id' => $adminUserId,
                'tenant_id' => $tenantId,
                'role' => self::ROLE_TENANT_ADMIN,
            ],
        ]);
    }
}
