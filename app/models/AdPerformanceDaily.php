<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

/**
 * Domain 3: Performance & Analytics (Time-Series Data)
 *
 * Daily performance metrics at the ad asset level: impressions,
 * clicks, cost, and conversions.
 *
 * Note: This table uses a composite primary key (ad_asset_id, perf_date).
 */
class AdPerformanceDaily extends Model
{
    public const TABLE_NAME = 'ad_performance_daily';

    public int $ad_asset_id;
    public string $perf_date;
    public int $impressions = 0;
    public int $clicks = 0;
    public float $cost = 0.0;
    public float $conversions = 0.0;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo('ad_asset_id', AdsAssets::class, 'id', [
            'alias' => 'adsAsset',
            'foreignKey' => [
                'message' => 'The ad asset does not exist',
            ],
        ]);
    }
}
