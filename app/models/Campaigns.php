<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 2: PPC Account Structure (Entity Mapping)
 *
 * Represents a campaign within an ad account. Stores structural metadata
 * such as status, budget type, bidding strategy, and target CPA/ROAS.
 */
class Campaigns extends Model
{
    public const TABLE_NAME = 'campaigns';

    public const STATUS_ENABLED = 'ENABLED';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_REMOVED = 'REMOVED';

    public ?int $id = null;
    public int $ad_account_id;
    public string $name;
    public string $status;
    public ?int $google_campaign_id = null;
    public ?string $budget_type = null;
    public ?string $bidding_strategy = null;
    public ?float $target_cpa = null;
    public ?float $target_roas = null;
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

        $this->belongsTo('ad_account_id', AdAccounts::class, 'id', [
            'alias' => 'adAccount',
            'foreignKey' => [
                'message' => 'The ad account does not exist',
            ],
        ]);

        $this->hasMany('id', AdGroups::class, 'campaign_id', [
            'alias' => 'adGroups',
        ]);

        $this->hasMany('id', CampaignNegativeKeywords::class, 'campaign_id', [
            'alias' => 'campaignNegativeKeywords',
        ]);

        $this->hasMany('id', CampaignPerformanceDaily::class, 'campaign_id', [
            'alias' => 'campaignPerformanceDaily',
        ]);

        $this->hasMany('id', CampaignAuditAlerts::class, 'campaign_id', [
            'alias' => 'campaignAuditAlerts',
        ]);
    }
}
