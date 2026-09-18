<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

/**
 * Domain 1: System Administration & Authentication
 *
 * Junction table linking users to clients. A user may belong
 * to multiple clients and a client may have multiple users.
 */
class ClientUsers extends Model
{
    public const TABLE_NAME = 'client_users';

    public int $user_id;
    public int $client_id;
    public ?string $created_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo('user_id', Users::class, 'id', [
            'alias' => 'user',
            'foreignKey' => [
                'message' => 'The user does not exist',
            ],
        ]);

        $this->belongsTo('client_id', Clients::class, 'id', [
            'alias' => 'client',
            'foreignKey' => [
                'message' => 'The client does not exist',
            ],
        ]);
    }
}
