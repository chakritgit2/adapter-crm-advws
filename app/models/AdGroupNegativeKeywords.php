<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 2: PPC Account Structure (Entity Mapping)
 *
 * Ad-group-level negative keywords used to refine targeting
 * and prevent conflicting impressions.
 */
class AdGroupNegativeKeywords extends Model
{
    public const TABLE_NAME = 'ad_group_negative_keywords';

    public const MATCH_TYPE_EXACT = 'EXACT';
    public const MATCH_TYPE_PHRASE = 'PHRASE';
    public const MATCH_TYPE_BROAD = 'BROAD';

    public ?int $id = null;
    public int $ad_group_id;
    public string $keyword_text;
    public string $match_type;
    public ?string $created_at = null;
    public ?string $deleted_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->addBehavior(new SoftDelete([
            'field' => 'deleted_at',
            'value' => date('Y-m-d H:i:s'),
        ]));

        $this->belongsTo('ad_group_id', AdGroups::class, 'id', [
            'alias' => 'adGroup',
            'foreignKey' => [
                'message' => 'The ad group does not exist',
            ],
        ]);
    }
}
