<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class PositionApprovals extends Model
{
    public const TABLE_NAME = 'position_approvals';

    public ?int $id = null;
    public int $position_id;
    public int $admin_user_id;
    public string $approved_at;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);
    }
}
