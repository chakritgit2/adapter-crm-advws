<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 2: PPC Account Structure (Entity Mapping)
 *
 * Represents a top-level ad account linked to a client and API token.
 * Stores timezone and currency metadata pulled from the ad platform API.
 */
class AdAccounts extends Model
{
    
    public const TABLE_NAME = 'ad_accounts';

    public ?int $id = null;
    public int $client_id;
    public int $api_token_id = null;
    public string $name;
    public string $timezone;
    public string $currency_code;
    public ?string $google_customer_id = null;
    public ?string $created_at = null;
    public ?string $deleted_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->addBehavior(new SoftDelete([
            'field' => 'deleted_at',
            'value' => date('Y-m-d H:i:s'),
        ]));

        $this->belongsTo('client_id', Clients::class, 'id', [
            'alias' => 'client',
            'foreignKey' => [
                'message' => 'The client does not exist',
            ],
        ]);

        $this->belongsTo('api_token_id', ApiTokens::class, 'id', [
            'alias' => 'apiToken',
            'foreignKey' => [
                'message' => 'The API token does not exist',
            ],
        ]);

        $this->hasMany('id', Campaigns::class, 'ad_account_id', [
            'alias' => 'campaigns',
        ]);

        $this->hasMany('id', AccountAuditAlerts::class, 'account_id', [
            'alias' => 'accountAuditAlerts',
        ]);
    }

}
