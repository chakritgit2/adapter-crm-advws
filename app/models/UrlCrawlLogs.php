<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

/**
 * Domain 6: External Health Logs
 *
 * Logs the results of crawling final landing page URLs from ads_assets.
 * Tracks HTTP status codes and page load speeds to catch broken links
 * and server errors that ad networks might miss.
 */
class UrlCrawlLogs extends Model
{
    public const TABLE_NAME = 'url_crawl_logs';

    public ?int $id = null;
    public int $ad_asset_id;
    public string $url;
    public ?int $http_status_code = null;
    public ?int $page_load_speed_ms = null;
    public ?string $checked_at = null;

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
