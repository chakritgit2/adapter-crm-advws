<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

/**
 * Domain 5: Statistical Testing Engine
 *
 * Junction table linking ad tests to their ad asset variations.
 * Flags the winning variation once statistical significance is reached.
 *
 * Note: Composite primary key (test_id, ad_asset_id).
 */
class AdTestVariations extends Model
{
    public const TABLE_NAME = 'ad_test_variations';

    public int $test_id;
    public int $ad_asset_id;
    public bool $is_winner = false;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo('test_id', AdTests::class, 'id', [
            'alias' => 'adTest',
            'foreignKey' => [
                'message' => 'The ad test does not exist',
            ],
        ]);

        $this->belongsTo('ad_asset_id', AdsAssets::class, 'id', [
            'alias' => 'adsAsset',
            'foreignKey' => [
                'message' => 'The ad asset does not exist',
            ],
        ]);
    }
}
