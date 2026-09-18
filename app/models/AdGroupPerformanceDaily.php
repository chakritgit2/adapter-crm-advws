<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

/**
 * Domain 3: Performance & Analytics (Time-Series Data)
 *
 * Daily performance metrics at the ad group level: impressions,
 * clicks, cost, conversions, and conversion value.
 *
 * Note: This table uses a composite primary key (ad_group_id, perf_date).
 */
class AdGroupPerformanceDaily extends Model
{
    public const TABLE_NAME = 'ad_group_performance_daily';

    public int $ad_group_id;
    public string $perf_date;
    public int $impressions = 0;
    public int $clicks = 0;
    public float $cost = 0.0;
    public float $conversions = 0.0;
    public float $conversion_value = 0.0;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo('ad_group_id', AdGroups::class, 'id', [
            'alias' => 'adGroup',
            'foreignKey' => [
                'message' => 'The ad group does not exist',
            ],
        ]);
    }
}
