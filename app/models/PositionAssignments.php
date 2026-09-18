<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class PositionAssignments extends Model
{
    public const TABLE_NAME = 'position_assignments';

    public ?int $id = null;
    public int $employee_id;
    public int $position_id;
    public string $start_date;
    public ?string $end_date = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);
    }
}
