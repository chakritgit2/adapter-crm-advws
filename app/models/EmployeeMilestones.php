<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class EmployeeMilestones extends Model
{
    public const TABLE_NAME = 'employee_milestones';

    public ?int $id = null;
    public int $company_id;
    public int $employee_id;
    public int $event_type_id;
    public string $event_date;
    public ?string $description = null;
    public ?string $metadata = null;
    public string $created_at;

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
            'event_type_id',
            MilestoneEventTypes::class,
            'id',
            ['alias' => 'EventType']
        );
    }
}
