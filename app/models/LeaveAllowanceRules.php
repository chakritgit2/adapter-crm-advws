<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class LeaveAllowanceRules extends Model
{
    use TranslateUuidTrait;

    public const TABLE_NAME = 'leave_allowance_rules';

    public ?int $id = null;
    public ?string $public_id = null;
    public int $company_id;
    public int $leave_type_id;
    public ?int $position_id = null;
    public ?string $job_level = null;
    public ?int $min_tenure_months = null;
    public ?int $max_tenure_months = null;
    public int $allowance_minutes = 0;
    public int $priority = 0;
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

        $this->belongsTo(
            'leave_type_id',
            LeaveTypes::class,
            'id',
            ['alias' => 'LeaveType']
        );

        $this->belongsTo(
            'position_id',
            Positions::class,
            'id',
            ['alias' => 'Position']
        );
    }
}
