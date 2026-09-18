<?php
declare(strict_types=1);

use Phalcon\Db\Enum;

class ReportingController extends TenantBaseController
{
    /**
     * USE CASE 1: Campaign Performance Aggregation (Domain 2 + Domain 3)
     * Calculates Total Spend, CPA, and ROAS for campaigns within a date range.
     */
    public function campaignPerformanceAction()
    {
        // In a real app, these would come from $this->request->getQuery()
        $adAccountId = 1000000000; // Using the first account generated
        $startDate   = '2024-05-01';
        $endDate     = '2024-05-07';

        $sql = "
            SELECT 
                c.id AS campaign_id,
                c.name AS campaign_name,
                c.bidding_strategy,
                SUM(cp.impressions) AS total_impressions,
                SUM(cp.clicks) AS total_clicks,
                SUM(cp.cost) AS total_cost,
                SUM(cp.conversions) AS total_conversions,
                -- Use NULLIF to prevent Division By Zero errors if conversions are 0
                (SUM(cp.cost) / NULLIF(SUM(cp.conversions), 0)) AS calculated_cpa,
                (SUM(cp.conversion_value) / NULLIF(SUM(cp.cost), 0)) AS calculated_roas
            FROM campaigns c
            JOIN campaign_performance_daily cp ON c.id = cp.campaign_id
            WHERE c.ad_account_id = :account_id
              AND cp.perf_date BETWEEN :start_date AND :end_date
            GROUP BY c.id, c.name, c.bidding_strategy
            ORDER BY total_cost DESC
            LIMIT 100
        ";

        $results = $this->db->fetchAll($sql, Enum::FETCH_ASSOC, [
            'account_id' => $adAccountId,
            'start_date' => $startDate,
            'end_date'   => $endDate
        ]);

        return $this->response->setJsonContent(['data' => $results]);
    }

    /**
     * USE CASE 2: The Audit Dashboard (Domain 4 + Domain 2)
     * Pulls active alerts for campaigns, joining to get the human-readable rule and campaign names.
     */
    public function activeAlertsAction()
    {
        $sql = "
            SELECT 
                ar.rule_name,
                caa.alert_message,
                c.name AS campaign_name,
                caa.detected_at,
                caa.status
            FROM campaign_audit_alerts caa
            JOIN audit_rules ar ON caa.rule_id = ar.id
            JOIN campaigns c ON caa.campaign_id = c.id
            WHERE caa.status = 'active'
            ORDER BY caa.detected_at DESC
            LIMIT 50
        ";

        $results = $this->db->fetchAll($sql, Enum::FETCH_ASSOC);

        return $this->response->setJsonContent(['data' => $results]);
    }

    /**
     * USE CASE 3: A/B Testing Engine Results (Domain 5 + 2 + 3)
     * Calculates the real-world Click-Through Rate (CTR) for Ad Variations currently in a test.
     */
    public function testResultsAction()
    {
        // Assuming test ID 1 from our generated data
        $testId = 1;

        $sql = "
            SELECT 
                t.name AS test_name,
                aa.id AS ad_asset_id,
                aa.headlines,
                SUM(ap.impressions) AS total_impressions,
                SUM(ap.clicks) AS total_clicks,
                -- Calculate CTR as a percentage
                (SUM(ap.clicks) / NULLIF(SUM(ap.impressions), 0)) * 100 AS ctr_percentage
            FROM ad_tests t
            JOIN ad_test_variations atv ON t.id = atv.test_id
            JOIN ads_assets aa ON atv.ad_asset_id = aa.id
            -- LEFT JOIN ensures we still see the ad even if it has 0 impressions so far
            LEFT JOIN ad_performance_daily ap ON aa.id = ap.ad_asset_id
            WHERE t.id = :test_id
            GROUP BY t.id, t.name, aa.id, aa.headlines
            ORDER BY ctr_percentage DESC
        ";

        $results = $this->db->fetchAll($sql, Enum::FETCH_ASSOC, [
            'test_id' => $testId
        ]);

        return $this->response->setJsonContent(['data' => $results]);
    }

    /**
     * USE CASE 4: Broken URL Report (Domain 6 + Domain 2)
     * Finds all ads that have a broken Final URL (404/500 errors) so the agency can pause them.
     */
    public function brokenUrlsAction()
    {
        $sql = "
            SELECT 
                aa.id AS ad_asset_id,
                aa.final_url,
                ucl.http_status_code,
                ucl.page_load_speed_ms,
                ag.name AS ad_group_name,
                c.name AS campaign_name
            FROM url_crawl_logs ucl
            JOIN ads_assets aa ON ucl.ad_asset_id = aa.id
            JOIN ad_groups ag ON aa.ad_group_id = ag.id
            JOIN campaigns c ON ag.campaign_id = c.id
            -- Filter for anything that isn't a 200 OK or 301 Redirect
            WHERE ucl.http_status_code NOT IN (200, 301)
            ORDER BY ucl.checked_at DESC
        ";

        $results = $this->db->fetchAll($sql, Enum::FETCH_ASSOC);

        return $this->response->setJsonContent(['data' => $results]);
    }

    
    /**
     * 1. BUDGET PACING & EFFICIENCY REPORT (Campaign Level)
     * Purpose: Identifies if a campaign is spending too much or too little, and if it is hitting its CPA/ROAS targets.
     * Action: Used to bulk increase/decrease daily budgets.
     */
    public function budgetPacingAction()
    {
        $adAccountId = 1000000000; // Mock Account ID
        $startDate   = '2024-05-01';
        $endDate     = '2024-05-31';

        // This report joins the campaigns table (stores target CPA/ROAS) with the campaign_performance_daily table[cite: 236].
        // It tracks historical daily conversions, cost, clicks, and impressions primarily for budget pacing analysis[cite: 436].
        $sql = "
            SELECT 
                c.id AS campaign_id,
                c.name AS campaign_name,
                c.target_cpa,
                c.target_roas,
                SUM(cp.cost) AS total_spend,
                SUM(cp.conversions) AS total_conversions,
                (SUM(cp.cost) / NULLIF(SUM(cp.conversions), 0)) AS current_cpa,
                (SUM(cp.conversion_value) / NULLIF(SUM(cp.cost), 0)) AS current_roas
            FROM campaigns c
            JOIN campaign_performance_daily cp ON c.id = cp.campaign_id
            WHERE c.ad_account_id = :account_id
              AND c.status = 'ENABLED'
              AND c.deleted_at IS NULL -- Enforcing soft delete cascade logic [cite: 326]
              AND cp.perf_date BETWEEN :start_date AND :end_date
            GROUP BY c.id, c.name, c.target_cpa, c.target_roas
            ORDER BY total_spend DESC
        ";

        $results = $this->db->fetchAll($sql, Enum::FETCH_ASSOC, [
            'account_id' => $adAccountId,
            'start_date' => $startDate,
            'end_date'   => $endDate
        ]);

        return $this->response->setJsonContent(['data' => $results]);
    }

    /**
     * 2A. ZERO-CONVERSION WASTE REPORT (Keyword Level)
     * Purpose: Highlights active keywords that have consumed significant cost but generated zero conversions.
     * Action: Used to bulk pause bleeding keywords.
     */
    public function zeroConversionWasteAction()
    {
        $adAccountId    = 1000000000;
        $spendThreshold = 50.00; // Flag keywords that spent > $50 with 0 conversions
        $startDate      = '2024-05-01';
        $endDate        = '2024-05-31';

        // This queries the keyword_performance_daily table, which stores highly indexed daily metrics primarily to identify zero-conversion waste[cite: 268, 438].
        $sql = "
            SELECT 
                k.id AS keyword_id,
                k.keyword_text,
                k.match_type,
                ag.name AS ad_group_name,
                c.name AS campaign_name,
                SUM(kp.cost) AS total_wasted_spend,
                SUM(kp.clicks) AS total_clicks
            FROM keywords k
            JOIN ad_groups ag ON k.ad_group_id = ag.id
            JOIN campaigns c ON ag.campaign_id = c.id
            JOIN keyword_performance_daily kp ON k.id = kp.keyword_id
            WHERE c.ad_account_id = :account_id
              AND k.status = 'ENABLED'
              AND k.deleted_at IS NULL
              AND kp.perf_date BETWEEN :start_date AND :end_date
            GROUP BY k.id, k.keyword_text, k.match_type, ag.name, c.name
            HAVING SUM(kp.conversions) = 0 
               AND total_wasted_spend > :spend_threshold
            ORDER BY total_wasted_spend DESC
        ";

        $results = $this->db->fetchAll($sql, Enum::FETCH_ASSOC, [
            'account_id'      => $adAccountId,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'spend_threshold' => $spendThreshold
        ]);

        return $this->response->setJsonContent(['data' => $results]);
    }

    /**
     * 2B. A/B TESTING LOSERS REPORT (Ad Level)
     * Purpose: Identifies ads that have mathematically lost a statistical A/B test.
     * Action: Used to bulk pause the losing variations so the winner receives 100% of traffic.
     */
    public function abTestLosersAction()
    {
        // This report joins the ad_tests and ad_test_variations tables with the ad_performance_daily table[cite: 243].
        // It aggregates long-term statistical testing data to identify losing variations[cite: 269].
        $sql = "
            SELECT 
                t.id AS test_id,
                t.name AS test_name,
                t.target_metric,
                aa.id AS ad_asset_id,
                aa.headlines,
                SUM(ap.clicks) AS total_clicks,
                SUM(ap.impressions) AS total_impressions,
                (SUM(ap.clicks) / NULLIF(SUM(ap.impressions), 0)) * 100 AS ctr_percentage
            FROM ad_tests t
            JOIN ad_test_variations atv ON t.id = atv.test_id
            JOIN ads_assets aa ON atv.ad_asset_id = aa.id
            LEFT JOIN ad_performance_daily ap ON aa.id = ap.ad_asset_id
            WHERE t.status = 'completed' 
              AND atv.is_winner = 0 -- 0 represents FALSE in MySQL
              AND aa.status = 'ENABLED'
              AND aa.deleted_at IS NULL
            GROUP BY t.id, t.name, t.target_metric, aa.id, aa.headlines
            ORDER BY t.id DESC, ctr_percentage ASC
        ";

        $results = $this->db->fetchAll($sql, Enum::FETCH_ASSOC);

        return $this->response->setJsonContent(['data' => $results]);
    }

    /**
     * 2C. CRITICAL HEALTH ALERTS REPORT (Ad Level)
     * Purpose: Identifies currently active ads that point to broken landing pages (404/500 errors).
     * Action: Used to immediately pause the ads to stop spending money on dead links.
     */
    public function healthAlertsAction()
    {
        $adAccountId = 1000000000;

        // This report pulls from the url_crawl_logs table, which tracks HTTP status codes like 404 or 500[cite: 246, 300].
        $sql = "
            SELECT 
                aa.id AS ad_asset_id,
                aa.final_url,
                ucl.http_status_code,
                ucl.checked_at,
                ag.name AS ad_group_name,
                c.name AS campaign_name
            FROM url_crawl_logs ucl
            JOIN ads_assets aa ON ucl.ad_asset_id = aa.id
            JOIN ad_groups ag ON aa.ad_group_id = ag.id
            JOIN campaigns c ON ag.campaign_id = c.id
            WHERE c.ad_account_id = :account_id
              AND aa.status = 'ENABLED'
              AND aa.deleted_at IS NULL
              AND ucl.http_status_code NOT IN (200, 301) -- Flags anything that is an error
            ORDER BY ucl.checked_at DESC
        ";

        $results = $this->db->fetchAll($sql, Enum::FETCH_ASSOC, [
            'account_id' => $adAccountId
        ]);

        return $this->response->setJsonContent(['data' => $results]);
    }
}