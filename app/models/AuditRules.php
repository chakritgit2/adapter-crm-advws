<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 4: Auditing & Automation Engine
 *
 * Defines user-configured audit rules with target entities and
 * condition logic (stored as JSON) for the watchdog system.
 */
class AuditRules extends Model
{
    public const TABLE_NAME = 'audit_rules';

    public const TARGET_ENTITY_ACCOUNT = 'account';
    public const TARGET_ENTITY_CAMPAIGN = 'campaign';
    public const TARGET_ENTITY_AD_GROUP = 'ad_group';
    public const TARGET_ENTITY_KEYWORD = 'keyword';
    public const TARGET_ENTITY_AD = 'ad';

    public ?int $id = null;
    public string $rule_name;
    public ?string $description = null;
    public string $target_entity;
    public string $condition_logic;
    public bool $is_active = true;
    public ?string $created_at = null;
    public ?string $deleted_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->addBehavior(new SoftDelete([
            'field' => 'deleted_at',
            'value' => date('Y-m-d H:i:s'),
        ]));

        $this->hasMany('id', AccountAuditAlerts::class, 'rule_id', [
            'alias' => 'accountAuditAlerts',
        ]);

        $this->hasMany('id', CampaignAuditAlerts::class, 'rule_id', [
            'alias' => 'campaignAuditAlerts',
        ]);

        $this->hasMany('id', AdGroupAuditAlerts::class, 'rule_id', [
            'alias' => 'adGroupAuditAlerts',
        ]);

        $this->hasMany('id', KeywordAuditAlerts::class, 'rule_id', [
            'alias' => 'keywordAuditAlerts',
        ]);

        $this->hasMany('id', AdAuditAlerts::class, 'rule_id', [
            'alias' => 'adAuditAlerts',
        ]);
    }
}
