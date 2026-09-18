<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class CompanyUserMap extends Model
{
    public const TABLE_NAME = 'company_user_map';

    public int $admin_user_id;
    public int $company_id;
    public string $role;

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
            'company_id',
            Companies::class,
            'id',
            ['alias' => 'Company']
        );
    }

    public static function userHasAccess(int $adminUserId, int $companyId): bool
    {
        return (bool) self::findFirst([
            'conditions' => 'admin_user_id = :admin_user_id: AND company_id = :company_id:',
            'bind' => [
                'admin_user_id' => $adminUserId,
                'company_id' => $companyId,
            ],
        ]);
    }

    public static function findCompaniesForUser(int $adminUserId): array
    {
        $maps = self::find([
            'conditions' => 'admin_user_id = :admin_user_id:',
            'bind' => ['admin_user_id' => $adminUserId],
        ]);

        $companyIds = [];
        foreach ($maps as $map) {
            $companyIds[] = (int) $map->company_id;
        }

        if (empty($companyIds)) {
            return [];
        }

        return Companies::find([
            'conditions' => 'id IN ({companyIds:array})',
            'bind' => ['companyIds' => $companyIds],
        ])->toArray();
    }
}
