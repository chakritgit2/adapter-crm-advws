<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class JobLevelsModel extends Model
{
    public const TABLE_NAME = 'job_levels';

    public ?int $id = null;
    public ?int $tenant_id = null;
    public ?int $company_id = null;
    public string $code;
    public string $category;
    public string $name;
    public int $sort_order = 0;
    public bool $is_active = true;
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

        $this->belongsTo(
            'company_id',
            Companies::class,
            'id',
            ['alias' => 'Company']
        );
    }
}
