<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

/**
 * Domain 3: Performance & Analytics (Time-Series Data)
 *
 * Daily performance metrics at the campaign level: impressions,
 * clicks, cost, conversions, and conversion value.
 *
 * Note: This table uses a composite primary key (campaign_id, perf_date).
 * Phalcon models work best with a single primary key; for updates/finds
 * use query builders or bind both columns explicitly.
 */
class CampaignPerformanceDaily extends Model
{
    public const TABLE_NAME = 'campaign_performance_daily';

    public int $campaign_id;
    public string $perf_date;
    public int $impressions = 0;
    public int $clicks = 0;
    public float $cost = 0.0;
    public float $conversions = 0.0;
    public float $conversion_value = 0.0;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo('campaign_id', Campaigns::class, 'id', [
            'alias' => 'campaign',
            'foreignKey' => [
                'message' => 'The campaign does not exist',
            ],
        ]);
    }
}
