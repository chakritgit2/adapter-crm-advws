<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class LeaveBalances extends Model
{
    use TranslateUuidTrait;

    public const TABLE_NAME = 'leave_balances';

    public ?int $id = null;
    public ?string $public_id = null;
    public int $company_id;
    public int $employee_id;
    public int $leave_type_id;
    public string $year;
    public int $allowance_minutes = 0;
    public int $used_minutes = 0;

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
            'employee_id',
            Employees::class,
            'id',
            ['alias' => 'Employee']
        );

        $this->belongsTo(
            'leave_type_id',
            LeaveTypes::class,
            'id',
            ['alias' => 'LeaveType']
        );
    }
}
