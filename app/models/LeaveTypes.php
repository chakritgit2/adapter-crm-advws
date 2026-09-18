<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class LeaveTypes extends Model
{
    use TranslateUuidTrait;

    public const TABLE_NAME = 'leave_types';

    public ?int $id = null;
    public ?string $public_id = null;
    public int $company_id;
    public string $name;
    public int $default_allowance_minutes = 0;
    public float $workday_hours = 8.00;
    public bool $is_active = true;
    public bool $is_toil = false;
    public string $year_end_mode = 'reset';
    public bool $allow_hourly = true;
    public bool $allow_whole_day = true;
    public bool $allow_multi_day = true;

    public const YEAR_END_RESET = 'reset';
    public const YEAR_END_CARRY_FORWARD = 'carry_forward';

    public const MODE_HOURLY = 'hourly';
    public const MODE_WHOLE_DAY = 'whole_day';
    public const MODE_MULTI_DAY = 'multi_day';

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo(
            'company_id',
            Companies::class,
            'id',
            ['alias' => 'Company']
        );

        $this->hasMany(
            'id',
            LeaveBalances::class,
            'leave_type_id',
            ['alias' => 'Balances']
        );
    }
}
