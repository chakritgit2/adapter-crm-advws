<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class MilestoneEventTypes extends Model
{
    public const TABLE_NAME = 'milestone_event_types';

    public ?int $id = null;
    public ?int $company_id = null;
    public string $name;
    public ?string $name_th = null;
    public ?string $color_tag = null;
    public bool $is_active = true;
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

        $this->hasMany(
            'id',
            EmployeeMilestones::class,
            'event_type_id',
            ['alias' => 'Milestones']
        );
    }
}
