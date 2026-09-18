<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class OvertimePolicies extends Model
{
    use TranslateUuidTrait;

    public const TABLE_NAME = 'overtime_policies';

    public ?int $id = null;
    public ?string $public_id = null;
    public int $company_id;
    public string $name;
    public float $multiplier = 1.00;
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
