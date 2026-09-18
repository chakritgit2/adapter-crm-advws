<?php

declare(strict_types=1);

use Phalcon\Cli\Task;
use Phalcon\Db\Enum as DbEnum;

use App\Helpers\AuditQueryBuilder;
use Google\Ads\GoogleAds\Lib\V24\GoogleAdsClientBuilder;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Ads\GoogleAds\V24\Services\SearchGoogleAdsRequest;


class ApiWorkerTask extends Task
{

    public function initialize()
    {
        $this->db = $this->di->get('db');
    }

    public function testAction()
    {
        echo "Test action\n";
        $message = "⚠️ URGENT: Google Ads API Execution Failed.\n";
        $message .= "Table: table_name | Alert ID: alert-id-123\n";
        $message .= "Reason: Some Reason\n";
        $message .= "Action Required: Please review the dashboard to resolve this issue manually.";

        //Send post request to n8n using CurlService
        $curlService = new CurlService();
        $response = $curlService->execute(
            $this->config->get('n8n-webhook')->get('url'),
            $this->config->get('n8n-webhook')->get('method'),
            $this->config->get('n8n-webhook')->get('headers')->toArray(),
            json_encode(['message' => $message]),
            true
        );
        var_dump($response);
        die;
    }

    /**
     * The target tables strictly split by entity to ensure referential integrity.
     */
    private array $alertTables = [
        'campaign' => 'campaign_audit_alerts',
        'ad_group' => 'ad_group_audit_alerts',
        'keyword' => 'keyword_audit_alerts',
        'ad' => 'ad_audit_alerts'
        // Note: account_audit_alerts usually handles notifications, not API execution, 
        // but can be added here if needed.
    ];

    /**
     * Evaluates audit rules and triggers actions for failing entities.
     */
    public function evaluateRulesAction()
    {
        // 1. Fetch active rules from Domain 4
        $rules = $this->db->fetchAll(
            "SELECT id, rule_name, target_entity, condition_logic FROM audit_rules WHERE is_active = 1",
            \Phalcon\Db\Enum::FETCH_ASSOC
        );

        foreach ($rules as $rule) {
            try {
                echo "\n--------------------------------------------------------------------------------\n";
                echo "Rule #{$rule['id']} : " . $rule['rule_name'] . "\n";
                // var_dump($queryData);ad_id
                echo "--------------------------------------------------------------------------------\n\n";

                // 2. Instantiate the builder with the JSON logic
                $builder = new AuditQueryBuilder($rule['target_entity'], $rule['condition_logic']);
                $queryData = $builder->build();

                // 3. Execute the generated raw SQL against your Database
                $failingEntities = $this->db->fetchAll(
                    $queryData['sql'], 
                    \Phalcon\Db\Enum::FETCH_ASSOC, 
                    $queryData['bind']
                );

                // 4. Insert alerts for any IDs returned
                if (!empty($failingEntities)) {
                    $alertTable = $this->alertTables[$rule['target_entity']];

                    // Explicitly map the column name to match the schema
                    $entityIdColumn = match ($rule['target_entity']) {
                        'account' => 'account_id',
                        'ad'      => 'ad_asset_id',
                        default   => $rule['target_entity'] . '_id'
                    };

                    foreach ($failingEntities as $entity) {
                        $this->db->insertAsDict(
                            $alertTable,
                            [
                                'rule_id' => $rule['id'],
                                $entityIdColumn => $entity['id'],
                                'alert_message' => "Rule trigger: {$rule['rule_name']}",
                                'execution_status' => 'manual_review'
                            ]
                        );
                    }
                }
            } catch (\Exception $e) {
                echo "Failed to evaluate rule {$rule['id']}: " . $e->getMessage();
            }
        }
    }

    /**
     * Main execution method to be triggered via Cron.
     */
    public function processQueueAction()
    {
        echo "Starting Google Ads API Worker...\n";

        foreach ($this->alertTables as $table) {
            $this->processTableQueue($table);
        }

        echo "Worker completed successfully.\n";
    }

    /**
     * Scans a specific table for approved jobs and processes them.
     */
    private function processTableQueue(string $table)
    {
        // 1. Find all approved tasks
        $sql = "SELECT id, action_payload FROM {$table} WHERE execution_status = 'approved_for_queue'";
        $results = $this->db->fetchAll($sql, \Phalcon\Db\Enum::FETCH_ASSOC);

        if (empty($results)) {
            // Nothing to process for this table
            return;
        }

        foreach ($results as $row) {
            $alertId = $row['id'];
            $payload = json_decode($row['action_payload'], true);

            // 2. State Locking: Mark as 'processing' immediately
            $this->db->execute(
                "UPDATE {$table} SET execution_status = 'processing' WHERE id = ?",
                [$alertId]
            );

            try {
                // 3. Execute the Google Ads API Call
                $this->executeGoogleAdsMutation($payload, $table, $alertId);

                // 4. Success: Mark as api_success and resolve the alert
                $this->db->execute(
                    "UPDATE {$table} SET 
                        execution_status = 'api_success', 
                        status = 'resolved', 
                        resolved_at = CURRENT_TIMESTAMP 
                    WHERE id = ?",
                    [$alertId]
                );

                echo "Success: Task {$alertId} on {$table} executed.\n";

            } catch (\Exception $e) {
                // 5. Failure: Implement your strict intervention architecture
                $this->handleApiFailure($table, $alertId, $e->getMessage());
            }
        }
    }

    /**
     * Builds a Google Ads API client using a GCP Service Account.
     * Service account JSON is loaded from config.google.service_account_json.
     * Developer token and MCC account ID are loaded from the encrypted api_tokens record.
     */
    private function getGoogleAdsClient(): \Google\Ads\GoogleAds\Lib\V24\GoogleAdsClient
    {
        $config = $this->getDI()->get('config');

        $googleConfig = $config->get('google');
        $serviceAccountJson = $googleConfig['service_account_json'] ?? null;
        if (empty($serviceAccountJson)) {
            throw new \Exception('Google service_account_json is not configured in config.php.');
        }

        // Get decrypted agency-level credentials from api_tokens
        $credentials = ApiTokens::getDecryptedCredentials(null, ApiTokens::PLATFORM_GOOGLE_ADS);
        if (empty($credentials)) {
            throw new \Exception('No Google Ads agency credentials found in api_tokens table.');
        }

        $developerToken = $credentials['developer_token'] ?? null;
        $mccAccountId   = $credentials['mcc_account_id'] ?? null;

        if (empty($developerToken)) {
            throw new \Exception('developer_token is missing from Google Ads credentials.');
        }
        if (empty($mccAccountId)) {
            throw new \Exception('mcc_account_id is missing from Google Ads credentials.');
        }

        $loginCustomerId = str_replace('-', '', $mccAccountId);

        echo "  [DIAG] loginCustomerId (MCC): {$loginCustomerId}\n";
        echo "  [DIAG] developerToken: " . substr($developerToken, 0, 4) . str_repeat('*', strlen($developerToken) - 4) . "\n";

        // Create OAuth2 credentials using the service account
        $oAuth2Credential = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/adwords',
            $serviceAccountJson
        );

        return (new GoogleAdsClientBuilder())
            ->withDeveloperToken($developerToken)
            ->withLoginCustomerId((int) $loginCustomerId)
            ->withOAuth2Credential($oAuth2Credential)
            ->build();
    }

    /**
     * Helper to build a SearchGoogleAdsRequest for the v33.4+ SDK signature.
     */
    private function buildSearchRequest(string $customerId, string $query): SearchGoogleAdsRequest
    {
        $request = new SearchGoogleAdsRequest();
        $request->setCustomerId($customerId);
        $request->setQuery($query);
        return $request;
    }

    /**
     * Translates the JSON payload into actual Google Ads API commands.
     */
    private function executeGoogleAdsMutation(array $payload, string $table, int $alertId)
    {
        // Example integration point for the Google Ads PHP SDK
        $action = $payload['action'] ?? null;

        // Setup Google Ads Client using the Composer Vendor
        $googleAdsClient = $this->getGoogleAdsClient();

        switch ($action) {
            case 'PAUSE':
                // e.g., $googleAdsClient->pauseEntity($entityId);
                // Simulate an API delay or potential failure
                // throw new \Exception("RATE_LIMIT_EXCEEDED");
                break;

            case 'ADJUST_BUDGET':
                $newBudget = $payload['suggested_value'] ?? null;
                // e.g., $googleAdsClient->updateCampaignBudget($entityId, $newBudget);
                break;

            case 'UPDATE_TARGET_CPA':
                $newCpa = $payload['suggested_value'] ?? null;
                // e.g., $googleAdsClient->updateTargetCpa($entityId, $newCpa);
                break;

            default:
                throw new \Exception("Unknown action payload requested: {$action}");
        }
    }

    /**
     * Handles API failures by halting retries and notifying human operators immediately.
     */
    private function handleApiFailure(string $table, int $alertId, string $errorMessage)
    {
        // 1. Update the database to 'api_failed' to prevent automatic retries
        $this->db->execute(
            "UPDATE {$table} SET execution_status = 'api_failed' WHERE id = ?",
            [$alertId]
        );

        // 2. Fetch the notification preferences of Admins/Agency Marketers
        $sql = "SELECT email, notify_slack_webhook FROM users WHERE notify_email = 1 OR notify_slack_webhook IS NOT NULL";
        $usersToNotify = $this->db->fetchAll($sql, \Phalcon\Db\Enum::FETCH_ASSOC);

        // 3. Immediately notify the team for manual intervention
        $message = "⚠️ URGENT: Google Ads API Execution Failed.\n";
        $message .= "Table: {$table} | Alert ID: {$alertId}\n";
        $message .= "Reason: {$errorMessage}\n";
        $message .= "Action Required: Please review the dashboard to resolve this issue manually.";


        //Send post request to n8n using CurlService
        $curlService = new CurlService();
        $response = $curlService->execute(
            $this->config->get('n8n-webhook')->get('url'),
            $this->config->get('n8n-webhook')->get('method'),
            $this->config->get('n8n-webhook')->get('headers')->toArray(),
            json_encode(['message' => $message]),
            true
        );

        echo "Failed: Task {$alertId} on {$table} resulted in error. Users notified.\n";
    }

    // ==================================================================
    // GOOGLE ADS DATA INGESTION (Track A: Historical Engine)
    // ==================================================================

    /**
     * Syncs structural data (campaigns, ad groups, keywords, ads) from Google Ads API.
     */
    public function syncStructureAction()
    {
        echo "=== Starting Google Ads Structure Sync ===\n";

        $googleAdsClient = $this->getGoogleAdsClient();

        $accounts = $this->db->fetchAll(
            "SELECT id, google_customer_id FROM ad_accounts
             WHERE deleted_at IS NULL AND google_customer_id IS NOT NULL",
            \Phalcon\Db\Enum::FETCH_ASSOC
        );

        if (empty($accounts)) {
            echo "No linked ad accounts found. Aborting.\n";
            return;
        }

        foreach ($accounts as $account) {
            $dbAccountId = (int) $account['id'];
            $customerId  = str_replace('-', '', $account['google_customer_id']);

            echo "\n-- Syncing account #{$dbAccountId} (customer: {$account['google_customer_id']}) --\n";

            try {
                $this->syncCampaigns($googleAdsClient, $customerId, $dbAccountId);
                $this->syncAdGroups($googleAdsClient, $customerId, $dbAccountId);
                $this->syncKeywords($googleAdsClient, $customerId, $dbAccountId);
                $this->syncAds($googleAdsClient, $customerId, $dbAccountId);
            } catch (\Exception $e) {
                echo "  ERROR: " . $e->getMessage() . "\n";
            }
        }

        echo "\n=== Structure Sync Complete ===\n";
    }

    private function syncCampaigns($googleAdsClient, string $customerId, int $dbAccountId): void
    {
        echo "  [DIAG] syncCampaigns customerId (target): {$customerId}\n";

        $service = $googleAdsClient->getGoogleAdsServiceClient();
        $query   = '
            SELECT
                campaign.id,
                campaign.name,
                campaign.status,
                campaign.bidding_strategy_type,
                campaign.target_cpa.target_cpa_micros,
                campaign.target_roas.target_roas
            FROM campaign
            WHERE campaign.status != "REMOVED"
        ';

        $response   = $service->search($this->buildSearchRequest($customerId, $query));
        $inserted   = 0;
        $updated    = 0;
        $googleIds  = [];

        foreach ($response->iterateAllElements() as $row) {
            $c = $row->getCampaign();
            $googleCampaignId = $c->getId();
            $googleIds[] = $googleCampaignId;

            $targetCpa = null;
            $targetRoas = null;
            if ($c->getTargetCpa() && $c->getTargetCpa()->getTargetCpaMicros()) {
                $targetCpa = $c->getTargetCpa()->getTargetCpaMicros() / 1_000_000;
            }
            if ($c->getTargetRoas() && $c->getTargetRoas()->getTargetRoas()) {
                $targetRoas = $c->getTargetRoas()->getTargetRoas();
            }

            $existing = Campaigns::findFirst([
                'conditions' => 'google_campaign_id = :gid: AND ad_account_id = :aid:',
                'bind'       => ['gid' => $googleCampaignId, 'aid' => $dbAccountId]
            ]);

            if ($existing) {
                $existing->name             = $c->getName();
                $existing->status           = $c->getStatus();
                $existing->bidding_strategy = $c->getBiddingStrategyType();
                $existing->target_cpa       = $targetCpa;
                $existing->target_roas      = $targetRoas;
                $existing->deleted_at       = null;
                $existing->save();
                $updated++;
            } else {
                $newCampaign = new Campaigns();
                $newCampaign->ad_account_id      = $dbAccountId;
                $newCampaign->name               = $c->getName();
                $newCampaign->status             = $c->getStatus();
                $newCampaign->google_campaign_id = $googleCampaignId;
                $newCampaign->bidding_strategy     = $c->getBiddingStrategyType();
                $newCampaign->target_cpa         = $targetCpa;
                $newCampaign->target_roas        = $targetRoas;
                $newCampaign->save();
                $inserted++;
            }
        }

        // Soft delete campaigns that disappeared (REMOVED or unlinked)
        if (!empty($googleIds)) {
            $placeholders = implode(',', array_fill(0, count($googleIds), '?'));
            $this->db->execute(
                "UPDATE campaigns SET deleted_at = CURRENT_TIMESTAMP
                 WHERE ad_account_id = ? AND google_campaign_id NOT IN ({$placeholders})
                   AND deleted_at IS NULL",
                array_merge([$dbAccountId], $googleIds)
            );
        }

        echo "  Campaigns: {$inserted} inserted, {$updated} updated\n";
    }

    private function syncAdGroups($googleAdsClient, string $customerId, int $dbAccountId): void
    {
        $service = $googleAdsClient->getGoogleAdsServiceClient();
        $query   = '
            SELECT
                ad_group.id,
                ad_group.name,
                ad_group.status,
                ad_group.campaign
            FROM ad_group
            WHERE ad_group.status != "REMOVED"
        ';

        $response  = $service->search($this->buildSearchRequest($customerId, $query));
        $inserted  = 0;
        $updated   = 0;
        $googleIds = [];

        foreach ($response->iterateAllElements() as $row) {
            $ag = $row->getAdGroup();
            $googleAdGroupId = $ag->getId();
            $googleIds[] = $googleAdGroupId;

            $googleCampaignId = basename($ag->getCampaign());
            $campaign = Campaigns::findFirst([
                'conditions' => 'google_campaign_id = :gid: AND ad_account_id = :aid: AND deleted_at IS NULL',
                'bind'       => ['gid' => $googleCampaignId, 'aid' => $dbAccountId]
            ]);

            if (!$campaign) {
                continue;
            }

            $existing = AdGroups::findFirst([
                'conditions' => 'google_ad_group_id = :gid:',
                'bind'       => ['gid' => $googleAdGroupId]
            ]);

            if ($existing) {
                $existing->name              = $ag->getName();
                $existing->status            = $ag->getStatus();
                $existing->deleted_at        = null;
                $existing->save();
                $updated++;
            } else {
                $newAg = new AdGroups();
                $newAg->campaign_id         = $campaign->id;
                $newAg->name                = $ag->getName();
                $newAg->status              = $ag->getStatus();
                $newAg->google_ad_group_id  = $googleAdGroupId;
                $newAg->save();
                $inserted++;
            }
        }

        if (!empty($googleIds)) {
            $placeholders = implode(',', array_fill(0, count($googleIds), '?'));
            $this->db->execute(
                "UPDATE ad_groups SET deleted_at = CURRENT_TIMESTAMP
                 WHERE google_ad_group_id NOT IN ({$placeholders}) AND deleted_at IS NULL",
                $googleIds
            );
        }

        echo "  Ad Groups: {$inserted} inserted, {$updated} updated\n";
    }

    private function syncKeywords($googleAdsClient, string $customerId, int $dbAccountId): void
    {
        $service = $googleAdsClient->getGoogleAdsServiceClient();
        $query   = '
            SELECT
                ad_group_criterion.criterion_id,
                ad_group_criterion.type,
                ad_group_criterion.keyword.text,
                ad_group_criterion.keyword.match_type,
                ad_group_criterion.quality_info.quality_score,
                ad_group_criterion.quality_info.creative_quality_score,
                ad_group_criterion.quality_info.post_click_quality_score,
                ad_group_criterion.quality_info.search_predicted_ctr,
                ad_group_criterion.status
            FROM ad_group_criterion
            WHERE ad_group_criterion.type = KEYWORD
              AND ad_group_criterion.status != "REMOVED"
        ';

        $response  = $service->search($this->buildSearchRequest($customerId, $query));
        $inserted  = 0;
        $updated   = 0;
        $googleIds = [];

        foreach ($response->iterateAllElements() as $row) {
            $crit = $row->getAdGroupCriterion();
            $googleCriterionId = $crit->getCriterionId();
            $googleIds[] = $googleCriterionId;

            $keyword = $crit->getKeyword();
            $qualityInfo = $crit->getQualityInfo();

            $existing = Keywords::findFirst([
                'conditions' => 'google_criterion_id = :gid:',
                'bind'       => ['gid' => $googleCriterionId]
            ]);

            $qualityScore = $qualityInfo ? $qualityInfo->getQualityScore() : null;
            $expectedCtr = null;
            $adRelevance = null;
            $landingPageExp = null;

            if ($qualityInfo) {
                $expectedCtr    = $qualityInfo->getSearchPredictedCtr();
                $adRelevance    = $qualityInfo->getCreativeQualityScore();
                $landingPageExp = $qualityInfo->getPostClickQualityScore();
            }

            if ($existing) {
                $existing->keyword_text     = $keyword->getText();
                $existing->match_type         = $keyword->getMatchType();
                $existing->status           = $crit->getStatus();
                $existing->quality_score    = $qualityScore;
                $existing->expected_ctr     = $expectedCtr;
                $existing->ad_relevance     = $adRelevance;
                $existing->landing_page_exp = $landingPageExp;
                $existing->deleted_at       = null;
                $existing->save();
                $updated++;
            } else {
                // Find local ad_group by ad_group id from criterion resource name
                $adGroupResource = $crit->getAdGroup();
                $googleAdGroupId = basename($adGroupResource);
                $adGroup = AdGroups::findFirst([
                    'conditions' => 'google_ad_group_id = :gid: AND deleted_at IS NULL',
                    'bind'       => ['gid' => $googleAdGroupId]
                ]);

                if (!$adGroup) {
                    continue;
                }

                $kw = new Keywords();
                $kw->ad_group_id         = $adGroup->id;
                $kw->keyword_text        = $keyword->getText();
                $kw->match_type          = $keyword->getMatchType();
                $kw->status              = $crit->getStatus();
                $kw->google_criterion_id = $googleCriterionId;
                $kw->quality_score       = $qualityScore;
                $kw->expected_ctr        = $expectedCtr;
                $kw->ad_relevance        = $adRelevance;
                $kw->landing_page_exp    = $landingPageExp;
                $kw->save();
                $inserted++;
            }
        }

        if (!empty($googleIds)) {
            $placeholders = implode(',', array_fill(0, count($googleIds), '?'));
            $this->db->execute(
                "UPDATE keywords SET deleted_at = CURRENT_TIMESTAMP
                 WHERE google_criterion_id NOT IN ({$placeholders}) AND deleted_at IS NULL",
                $googleIds
            );
        }

        echo "  Keywords: {$inserted} inserted, {$updated} updated\n";
    }

    private function syncAds($googleAdsClient, string $customerId, int $dbAccountId): void
    {
        $service = $googleAdsClient->getGoogleAdsServiceClient();
        $query   = '
            SELECT
                ad_group_ad.ad.id,
                ad_group_ad.status,
                ad_group_ad.ad.type,
                ad_group_ad.ad.responsive_search_ad.headlines,
                ad_group_ad.ad.responsive_search_ad.descriptions,
                ad_group_ad.ad.final_urls
            FROM ad_group_ad
            WHERE ad_group_ad.status != "REMOVED"
        ';

        $response  = $service->search($this->buildSearchRequest($customerId, $query));
        $inserted  = 0;
        $updated   = 0;
        $googleIds = [];

        foreach ($response->iterateAllElements() as $row) {
            $aga = $row->getAdGroupAd();
            $ad  = $aga->getAd();
            $googleAdId = $ad->getId();
            $googleIds[] = $googleAdId;

            $headlines = null;
            $descriptions = null;
            $finalUrl = null;

            if ($ad->getResponsiveSearchAd()) {
                $rsa = $ad->getResponsiveSearchAd();
                $headlinesArr = [];
                foreach ($rsa->getHeadlines() as $h) {
                    $headlinesArr[] = $h->getText();
                }
                $descArr = [];
                foreach ($rsa->getDescriptions() as $d) {
                    $descArr[] = $d->getText();
                }
                $headlines    = json_encode($headlinesArr);
                $descriptions = json_encode($descArr);
            }

            $finalUrls = $ad->getFinalUrls();
            if ($finalUrls && count($finalUrls) > 0) {
                $finalUrl = $finalUrls[0];
            }

            $existing = AdsAssets::findFirst([
                'conditions' => 'google_ad_id = :gid:',
                'bind'       => ['gid' => $googleAdId]
            ]);

            if ($existing) {
                $existing->ad_type       = $ad->getType();
                $existing->headlines       = $headlines;
                $existing->descriptions    = $descriptions;
                $existing->final_url       = $finalUrl;
                $existing->status          = $aga->getStatus();
                $existing->deleted_at      = null;
                $existing->save();
                $updated++;
            } else {
                $adGroupResource = $aga->getAdGroup();
                $googleAdGroupId = basename($adGroupResource);
                $adGroup = AdGroups::findFirst([
                    'conditions' => 'google_ad_group_id = :gid: AND deleted_at IS NULL',
                    'bind'       => ['gid' => $googleAdGroupId]
                ]);

                if (!$adGroup) {
                    continue;
                }

                $asset = new AdsAssets();
                $asset->ad_group_id   = $adGroup->id;
                $asset->ad_type       = $ad->getType();
                $asset->google_ad_id  = $googleAdId;
                $asset->headlines     = $headlines;
                $asset->descriptions  = $descriptions;
                $asset->final_url     = $finalUrl;
                $asset->status        = $aga->getStatus();
                $asset->save();
                $inserted++;
            }
        }

        if (!empty($googleIds)) {
            $placeholders = implode(',', array_fill(0, count($googleIds), '?'));
            $this->db->execute(
                "UPDATE ads_assets SET deleted_at = CURRENT_TIMESTAMP
                 WHERE google_ad_id NOT IN ({$placeholders}) AND deleted_at IS NULL",
                $googleIds
            );
        }

        echo "  Ads: {$inserted} inserted, {$updated} updated\n";
    }

    /**
     * Syncs daily performance metrics from Google Ads API.
     * CLI params: [startDate] [endDate]  (default: yesterday only)
     */
    public function syncPerformanceAction()
    {
        echo "=== Starting Google Ads Performance Sync ===\n";

        $params = $this->dispatcher->getParams();
        $startDate = $params[0] ?? date('Y-m-d', strtotime('-1 day'));
        $endDate   = $params[1] ?? $startDate;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            echo "Invalid date format. Use YYYY-MM-DD.\n";
            return;
        }

        echo "Date range: {$startDate} to {$endDate}\n";

        $googleAdsClient = $this->getGoogleAdsClient();

        $accounts = $this->db->fetchAll(
            "SELECT id, google_customer_id FROM ad_accounts
             WHERE deleted_at IS NULL AND google_customer_id IS NOT NULL",
            \Phalcon\Db\Enum::FETCH_ASSOC
        );

        foreach ($accounts as $account) {
            $dbAccountId = (int) $account['id'];
            $customerId  = str_replace('-', '', $account['google_customer_id']);

            echo "\n-- Performance for account #{$dbAccountId} ({$account['google_customer_id']}) --\n";

            try {
                $this->syncCampaignPerformance($googleAdsClient, $customerId, $dbAccountId, $startDate, $endDate);
                $this->syncAdGroupPerformance($googleAdsClient, $customerId, $dbAccountId, $startDate, $endDate);
                $this->syncKeywordPerformance($googleAdsClient, $customerId, $dbAccountId, $startDate, $endDate);
                $this->syncAdPerformance($googleAdsClient, $customerId, $dbAccountId, $startDate, $endDate);
            } catch (\Exception $e) {
                echo "  ERROR: " . $e->getMessage() . "\n";
            }
        }

        echo "\n=== Performance Sync Complete ===\n";
    }

    private function syncCampaignPerformance($googleAdsClient, string $customerId, int $dbAccountId, string $startDate, string $endDate): void
    {
        $service = $googleAdsClient->getGoogleAdsServiceClient();
        $query   = "
            SELECT
                campaign.id,
                segments.date,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions,
                metrics.conversions_value
            FROM campaign
            WHERE segments.date BETWEEN '{$startDate}' AND '{$endDate}'
        ";

        $response = $service->search($this->buildSearchRequest($customerId, $query));
        $count    = 0;

        foreach ($response->iterateAllElements() as $row) {
            $campaign = $row->getCampaign();
            $metrics  = $row->getMetrics();
            $segments = $row->getSegments();

            $localCampaign = Campaigns::findFirst([
                'conditions' => 'google_campaign_id = :gid: AND ad_account_id = :aid: AND deleted_at IS NULL',
                'bind'       => ['gid' => $campaign->getId(), 'aid' => $dbAccountId]
            ]);

            if (!$localCampaign) {
                continue;
            }

            $this->db->execute(
                "INSERT INTO campaign_performance_daily
                    (campaign_id, perf_date, impressions, clicks, cost, conversions, conversion_value)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    impressions = VALUES(impressions),
                    clicks = VALUES(clicks),
                    cost = VALUES(cost),
                    conversions = VALUES(conversions),
                    conversion_value = VALUES(conversion_value)",
                [
                    $localCampaign->id,
                    $segments->getDate(),
                    $metrics->getImpressions(),
                    $metrics->getClicks(),
                    $metrics->getCostMicros() / 1_000_000,
                    $metrics->getConversions(),
                    $metrics->getConversionsValue()
                ]
            );
            $count++;
        }

        echo "  Campaign performance: {$count} rows\n";
    }

    private function syncAdGroupPerformance($googleAdsClient, string $customerId, int $dbAccountId, string $startDate, string $endDate): void
    {
        $service = $googleAdsClient->getGoogleAdsServiceClient();
        $query   = "
            SELECT
                ad_group.id,
                segments.date,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions,
                metrics.conversions_value
            FROM ad_group
            WHERE segments.date BETWEEN '{$startDate}' AND '{$endDate}'
        ";

        $response = $service->search($this->buildSearchRequest($customerId, $query));
        $count    = 0;

        foreach ($response->iterateAllElements() as $row) {
            $adGroup = $row->getAdGroup();
            $metrics = $row->getMetrics();
            $segments = $row->getSegments();

            $localAdGroup = AdGroups::findFirst([
                'conditions' => 'google_ad_group_id = :gid: AND deleted_at IS NULL',
                'bind'       => ['gid' => $adGroup->getId()]
            ]);

            if (!$localAdGroup) {
                continue;
            }

            $this->db->execute(
                "INSERT INTO ad_group_performance_daily
                    (ad_group_id, perf_date, impressions, clicks, cost, conversions, conversion_value)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    impressions = VALUES(impressions),
                    clicks = VALUES(clicks),
                    cost = VALUES(cost),
                    conversions = VALUES(conversions),
                    conversion_value = VALUES(conversion_value)",
                [
                    $localAdGroup->id,
                    $segments->getDate(),
                    $metrics->getImpressions(),
                    $metrics->getClicks(),
                    $metrics->getCostMicros() / 1_000_000,
                    $metrics->getConversions(),
                    $metrics->getConversionsValue()
                ]
            );
            $count++;
        }

        echo "  Ad group performance: {$count} rows\n";
    }

    private function syncKeywordPerformance($googleAdsClient, string $customerId, int $dbAccountId, string $startDate, string $endDate): void
    {
        $service = $googleAdsClient->getGoogleAdsServiceClient();
        $query   = "
            SELECT
                ad_group_criterion.criterion_id,
                segments.date,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions
            FROM keyword_view
            WHERE segments.date BETWEEN '{$startDate}' AND '{$endDate}'
        ";

        $response = $service->search($this->buildSearchRequest($customerId, $query));
        $count    = 0;

        foreach ($response->iterateAllElements() as $row) {
            $criterion = $row->getAdGroupCriterion();
            $metrics   = $row->getMetrics();
            $segments  = $row->getSegments();

            $localKeyword = Keywords::findFirst([
                'conditions' => 'google_criterion_id = :gid: AND deleted_at IS NULL',
                'bind'       => ['gid' => $criterion->getCriterionId()]
            ]);

            if (!$localKeyword) {
                continue;
            }

            $this->db->execute(
                "INSERT INTO keyword_performance_daily
                    (keyword_id, perf_date, impressions, clicks, cost, conversions)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    impressions = VALUES(impressions),
                    clicks = VALUES(clicks),
                    cost = VALUES(cost),
                    conversions = VALUES(conversions)",
                [
                    $localKeyword->id,
                    $segments->getDate(),
                    $metrics->getImpressions(),
                    $metrics->getClicks(),
                    $metrics->getCostMicros() / 1_000_000,
                    $metrics->getConversions()
                ]
            );
            $count++;
        }

        echo "  Keyword performance: {$count} rows\n";
    }

    private function syncAdPerformance($googleAdsClient, string $customerId, int $dbAccountId, string $startDate, string $endDate): void
    {
        $service = $googleAdsClient->getGoogleAdsServiceClient();
        $query   = "
            SELECT
                ad_group_ad.ad.id,
                segments.date,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions
            FROM ad_group_ad
            WHERE segments.date BETWEEN '{$startDate}' AND '{$endDate}'
        ";

        $response = $service->search($this->buildSearchRequest($customerId, $query));
        $count    = 0;

        foreach ($response->iterateAllElements() as $row) {
            $ad      = $row->getAdGroupAd()->getAd();
            $metrics = $row->getMetrics();
            $segments = $row->getSegments();

            $localAd = AdsAssets::findFirst([
                'conditions' => 'google_ad_id = :gid: AND deleted_at IS NULL',
                'bind'       => ['gid' => $ad->getId()]
            ]);

            if (!$localAd) {
                continue;
            }

            $this->db->execute(
                "INSERT INTO ad_performance_daily
                    (ad_asset_id, perf_date, impressions, clicks, cost, conversions)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    impressions = VALUES(impressions),
                    clicks = VALUES(clicks),
                    cost = VALUES(cost),
                    conversions = VALUES(conversions)",
                [
                    $localAd->id,
                    $segments->getDate(),
                    $metrics->getImpressions(),
                    $metrics->getClicks(),
                    $metrics->getCostMicros() / 1_000_000,
                    $metrics->getConversions()
                ]
            );
            $count++;
        }

        echo "  Ad performance: {$count} rows\n";
    }

    /**
     * Orchestrator: runs structure sync then performance sync.
     * Intended for nightly cron jobs.
     */
    public function runDailySyncAction()
    {
        echo "=== Daily Google Ads Sync ===\n";
        $this->syncStructureAction();
        $this->syncPerformanceAction();
        echo "=== Daily Sync Complete ===\n";
    }

}
