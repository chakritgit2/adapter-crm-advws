<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 2: PPC Account Structure (Entity Mapping)
 *
 * Stores ad copy components (headlines, descriptions) and final URLs
 * for Responsive Search Ads (RSAs) and other ad types.
 */
class AdsAssets extends Model
{
    public const TABLE_NAME = 'ads_assets';

    public const STATUS_ENABLED = 'ENABLED';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_REMOVED = 'REMOVED';

    public ?int $id = null;
    public int $ad_group_id;
    public string $ad_type;
    public ?int $google_ad_id = null;
    public ?string $headlines = null;
    public ?string $descriptions = null;
    public string $final_url;
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

        $this->hasMany('id', AdAuditAlerts::class, 'ad_asset_id', [
            'alias' => 'adAuditAlerts',
        ]);

        $this->hasMany('id', AdPerformanceDaily::class, 'ad_asset_id', [
            'alias' => 'adPerformanceDaily',
        ]);

        $this->hasMany('id', AdTestVariations::class, 'ad_asset_id', [
            'alias' => 'adTestVariations',
        ]);

        $this->hasMany('id', UrlCrawlLogs::class, 'ad_asset_id', [
            'alias' => 'urlCrawlLogs',
        ]);
    }
}
