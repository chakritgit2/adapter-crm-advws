<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 5: Statistical Testing Engine
 *
 * Defines A/B ad tests with target metrics, significance thresholds,
 * and lifecycle status (running, completed, paused).
 */
class AdTests extends Model
{
    public const TABLE_NAME = 'ad_tests';

    public const TARGET_METRIC_CTR = 'CTR';
    public const TARGET_METRIC_CONVERSION_RATE = 'CONVERSION_RATE';
    public const TARGET_METRIC_ROAS = 'ROAS';

    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PAUSED = 'paused';

    public ?int $id = null;
    public string $name;
    public string $target_metric;
    public float $significance_threshold = 95.00;
    public string $status = self::STATUS_RUNNING;
    public ?string $started_at = null;
    public ?string $completed_at = null;
    public ?string $deleted_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->addBehavior(new SoftDelete([
            'field' => 'deleted_at',
            'value' => date('Y-m-d H:i:s'),
        ]));

        $this->hasMany('id', AdTestVariations::class, 'test_id', [
            'alias' => 'adTestVariations',
        ]);
    }
}
