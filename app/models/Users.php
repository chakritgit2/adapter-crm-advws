<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 1: System Administration & Authentication
 *
 * Stores system users with roles (admin, agency_marketer, client),
 * notification preferences, and authentication credentials.
 */
class Users extends Model
{
    public const TABLE_NAME = 'users';

    public const ROLE_ADMIN = 'admin';
    public const ROLE_AGENCY_MARKETER = 'agency_marketer';
    public const ROLE_CLIENT = 'client';

    public ?int $id = null;
    public string $name;
    public string $email;
    public string $password_hash;
    public string $role = self::ROLE_AGENCY_MARKETER;
    public ?string $created_at = null;
    public ?string $deleted_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->addBehavior(new SoftDelete([
            'field' => 'deleted_at',
            'value' => date('Y-m-d H:i:s'),
        ]));
    }
}
