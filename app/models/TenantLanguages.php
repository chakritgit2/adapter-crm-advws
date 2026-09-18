<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class TenantLanguages extends Model
{
    public const TABLE_NAME = 'tenant_languages';

    public ?int $id = null;
    public int $company_id;
    public string $language_code;
    public string $language_name;
    public bool $is_active = true;
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
    }
}
