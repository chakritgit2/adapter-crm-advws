<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\SoftDelete;

/**
 * Domain 1: System Administration & Authentication
 *
 * Stores OAuth and developer tokens for Google Ads and Microsoft Ads API access.
 */
class ApiTokens extends Model
{
    public const TABLE_NAME = 'api_tokens';

    public const PLATFORM_GOOGLE_ADS = 'google_ads';
    public const PLATFORM_MICROSOFT_ADS = 'microsoft_ads';
    public const PLATFORM_META_ADS = 'meta_ads';
    public const PLATFORM_TIKTOK_ADS = 'tiktok_ads';
    public const PLATFORM_LINKEDIN_ADS = 'linkedin_ads';

    public ?int $id = null;
    public ?int $client_id = null;
    public string $platform;
    public string $credentials;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo('client_id', Clients::class, 'id', [
            'alias' => 'client',
            'foreignKey' => [
                'message' => 'The client does not exist',
            ],
        ]);

        $this->addBehavior(new SoftDelete([
            'field' => 'deleted_at',
            'value' => date('Y-m-d H:i:s'),
        ]));

        $this->hasMany('id', AdAccounts::class, 'api_token_id', [
            'alias' => 'adAccounts',
        ]);
    }

    public static function getDecryptedCredentials(?int $clientId, string $platform): array
    {
        $conditions = 'platform = :platform: AND deleted_at IS NULL';
        $bind = ['platform' => $platform];

        if ($clientId === null) {
            $conditions .= ' AND client_id IS NULL';
        } else {
            $conditions .= ' AND client_id = :client_id:';
            $bind['client_id'] = $clientId;
        }

        $token = self::findFirst([
            'conditions' => $conditions,
            'bind'       => $bind,
        ]);

        if (!$token) {
            return [];
        }

        $di = \Phalcon\Di\Di::getDefault();
        $config = $di->get('config');
        $encryption = new CredentialEncryption($config->encryption->credentialsKey);

        $stored = $token->credentials;
        $encrypted = json_decode($stored);
        if (!is_string($encrypted)) {
            $encrypted = $stored;
        }

        return json_decode($encryption->decrypt($encrypted), true) ?? [];
    }

}
