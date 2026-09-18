<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class AdminUsers extends Model
{
    public const TABLE_NAME = 'admin_users';

    public const ROLE_SUPER_ADMIN = 'Super Admin';
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_MEMBER = 'Member';

    public ?int $id = null;
    public string $name;
    public string $email;
    public string $password_hash;
    public string $role = self::ROLE_ADMIN;
    public bool $must_change_password = false;
    public ?string $created_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);
    }
}
