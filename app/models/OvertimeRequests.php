<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class OvertimeRequests extends Model
{
    use TranslateUuidTrait;

    public const TABLE_NAME = 'overtime_requests';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const COMPENSATION_PAYOUT = 'payout';
    public const COMPENSATION_TOIL = 'toil';

    public ?int $id = null;
    public ?string $public_id = null;
    public int $company_id;
    public int $employee_id;
    public string $start_time;
    public string $end_time;
    public int $overtime_policy_id;
    public int $worked_minutes = 0;
    public string $compensation_type = self::COMPENSATION_PAYOUT;
    public string $status = self::STATUS_PENDING;
    public ?string $reason = null;
    public ?int $approved_by = null;
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
            'employee_id',
            Employees::class,
            'id',
            ['alias' => 'Employee']
        );

        $this->belongsTo(
            'overtime_policy_id',
            OvertimePolicies::class,
            'id',
            ['alias' => 'OvertimePolicy']
        );

        $this->belongsTo(
            'approved_by',
            AdminUsers::class,
            'id',
            ['alias' => 'Approver']
        );
    }
}
