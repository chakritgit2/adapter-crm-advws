<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

/**
 * Domain 3: Performance & Analytics (Time-Series Data)
 *
 * Daily performance metrics at the keyword level: impressions,
 * clicks, cost, and conversions.
 *
 * Note: This table uses a composite primary key (keyword_id, perf_date).
 */
class KeywordPerformanceDaily extends Model
{
    public const TABLE_NAME = 'keyword_performance_daily';

    public int $keyword_id;
    public string $perf_date;
    public int $impressions = 0;
    public int $clicks = 0;
    public float $cost = 0.0;
    public float $conversions = 0.0;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo('keyword_id', Keywords::class, 'id', [
            'alias' => 'keyword',
            'foreignKey' => [
                'message' => 'The keyword does not exist',
            ],
        ]);
    }
}
