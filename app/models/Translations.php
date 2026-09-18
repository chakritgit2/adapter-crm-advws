<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class Translations extends Model
{
    public const TABLE_NAME = 'translations';

    public ?int $id = null;
    public int $company_id;
    public string $language_code;
    public string $target_table;
    public string $target_column;
    public int $target_id;
    public string $translation_value;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo(
            'company_id',
            Companies::class,
            'id',
            ['alias' => 'Company']
        );
    }
}
