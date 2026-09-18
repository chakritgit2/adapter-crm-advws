<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 2: PPC Account Structure (Entity Mapping)
 *
 * Stores keyword text, match types, and Quality Score sub-components
 * (expected CTR, ad relevance, landing page experience).
 */
class Keywords extends Model
{
    public const TABLE_NAME = 'keywords';

    public const MATCH_TYPE_EXACT = 'EXACT';
    public const MATCH_TYPE_PHRASE = 'PHRASE';
    public const MATCH_TYPE_BROAD = 'BROAD';

    public const STATUS_ENABLED = 'ENABLED';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_REMOVED = 'REMOVED';

    public const EXPECTED_CTR_BELOW_AVERAGE = 'BELOW_AVERAGE';
    public const EXPECTED_CTR_AVERAGE = 'AVERAGE';
    public const EXPECTED_CTR_ABOVE_AVERAGE = 'ABOVE_AVERAGE';
    public const EXPECTED_CTR_UNKNOWN = 'UNKNOWN';

    public const AD_RELEVANCE_BELOW_AVERAGE = 'BELOW_AVERAGE';
    public const AD_RELEVANCE_AVERAGE = 'AVERAGE';
    public const AD_RELEVANCE_ABOVE_AVERAGE = 'ABOVE_AVERAGE';
    public const AD_RELEVANCE_UNKNOWN = 'UNKNOWN';

    public const LANDING_PAGE_EXP_BELOW_AVERAGE = 'BELOW_AVERAGE';
    public const LANDING_PAGE_EXP_AVERAGE = 'AVERAGE';
    public const LANDING_PAGE_EXP_ABOVE_AVERAGE = 'ABOVE_AVERAGE';
    public const LANDING_PAGE_EXP_UNKNOWN = 'UNKNOWN';

    public ?int $id = null;
    public int $ad_group_id;
    public string $keyword_text;
    public string $match_type;
    public ?int $google_criterion_id = null;
    public ?int $quality_score = null;
    public ?string $expected_ctr = null;
    public ?string $ad_relevance = null;
    public ?string $landing_page_exp = null;
    public string $status;
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

        $this->hasMany('id', KeywordPerformanceDaily::class, 'keyword_id', [
            'alias' => 'keywordPerformanceDaily',
        ]);

        $this->hasMany('id', KeywordAuditAlerts::class, 'keyword_id', [
            'alias' => 'keywordAuditAlerts',
        ]);
    }
}
