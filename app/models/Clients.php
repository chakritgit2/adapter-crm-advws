<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 1: System Administration & Authentication
 *
 * Represents clients in the system. Linked to ad accounts
 * and used for organizational separation.
 */
class Clients extends Model
{
    public const TABLE_NAME = 'clients';

    public ?int $id = null;
    public string $name;
    public ?string $industry = null;
    public float $default_lead_close_rate = 10.00;
    public float $default_deal_value = 1000.00;
    public ?string $created_at = null;
    public ?string $deleted_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->addBehavior(new SoftDelete([
            'field' => 'deleted_at',
            'value' => date('Y-m-d H:i:s'),
        ]));

        $this->hasMany('id', AdAccounts::class, 'client_id', [
            'alias' => 'adAccounts',
        ]);
    }
}
