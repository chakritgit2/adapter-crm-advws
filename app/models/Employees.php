<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class Employees extends Model
{
    use TranslateUuidTrait;

    public const TABLE_NAME = 'employees';

    public ?int $id = null;
    public ?string $public_id = null;
    public int $company_id;
    public string $code;
    public string $first_name;
    public string $last_name;
    public ?string $email = null;
    public ?string $username = null;
    public ?string $password_hash = null;
    public ?string $set_password_token = null;
    public ?string $last_login_at = null;
    public bool $must_change_password = false;
    public ?string $phone = null;
    public ?string $gender = null;
    public ?string $date_of_birth = null;
    public ?string $employment_type = null;
    public int $salary = 0;

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
            PositionAssignments::class,
            'employee_id',
            ['alias' => 'Assignments']
        );
    }
}
