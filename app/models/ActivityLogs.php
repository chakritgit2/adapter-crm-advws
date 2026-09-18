<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class ActivityLogs extends Model
{
    public const TABLE_NAME = 'activity_logs';

    public ?int $id = null;
    public int $company_id;
    public int $admin_user_id;
    public string $action;
    public string $target_table;
    public int $target_id;
    public ?string $old_values = null;
    public ?string $new_values = null;
    public ?string $created_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo(
            'company_id',
            Companies::class,
            'id',
            ['alias' => 'Company']
        );

        $this->belongsTo(
            'admin_user_id',
            AdminUsers::class,
            'id',
            ['alias' => 'AdminUser']
        );
    }
}
