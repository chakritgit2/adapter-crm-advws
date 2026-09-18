<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 2: PPC Account Structure (Entity Mapping)
 *
 * Represents an ad group, the structural container that maps keywords to ads.
 */
class AdGroups extends Model
{
    public const TABLE_NAME = 'ad_groups';

    public const STATUS_ENABLED = 'ENABLED';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_REMOVED = 'REMOVED';

    public ?int $id = null;
    public int $campaign_id;
    public string $name;
    public string $status;
    public ?int $google_ad_group_id = null;
    public ?float $lead_close_rate = null;
    public ?float $avg_deal_value = null;
    public ?string $created_at = null;
    public ?string $deleted_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->addBehavior(new SoftDelete([
            'field' => 'deleted_at',
            'value' => date('Y-m-d H:i:s'),
        ]));

        $this->belongsTo('campaign_id', Campaigns::class, 'id', [
            'alias' => 'campaign',
            'foreignKey' => [
                'message' => 'The campaign does not exist',
            ],
        ]);

        $this->hasMany('id', Keywords::class, 'ad_group_id', [
            'alias' => 'keywords',
        ]);

        $this->hasMany('id', AdsAssets::class, 'ad_group_id', [
            'alias' => 'adsAssets',
        ]);

        $this->hasMany('id', AdGroupNegativeKeywords::class, 'ad_group_id', [
            'alias' => 'adGroupNegativeKeywords',
        ]);

        $this->hasMany('id', AdGroupPerformanceDaily::class, 'ad_group_id', [
            'alias' => 'adGroupPerformanceDaily',
        ]);

        $this->hasMany('id', AdGroupAuditAlerts::class, 'ad_group_id', [
            'alias' => 'adGroupAuditAlerts',
        ]);
    }
}
