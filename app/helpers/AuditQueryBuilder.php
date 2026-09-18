<?php

namespace App\Helpers;

class AuditQueryBuilder
{
    private string $targetEntity;
    private array $logic;
    private array $bindings = [];
    private int $bindIndex = 0;

    /**
     * Maps the target_entity ENUM to the actual Domain 2 table name.
     */
    private const ENTITY_TABLE_MAP = [
        'account'  => 'ad_accounts',
        'campaign' => 'campaigns',
        'ad_group' => 'ad_groups',
        'keyword'  => 'keywords',
        'ad'       => 'ads_assets'
    ];

    /**
     * Maps the base table to the prefix needed for performance tables.
     * ads_assets maps to 'ad' to properly target 'ad_performance_daily'.
     */
    private const ENTITY_BASE_TABLE_MAP = [
        'campaigns'  => 'campaign',
        'ad_groups'  => 'ad_group',
        'keywords'   => 'keyword',
        'ads_assets' => 'ad' 
    ];

    /**
     * Lists performance metrics that physically exist and require standard SUM() aggregation.
     */
    private const PERFORMANCE_METRICS = [
        'cost', 'clicks', 'impressions', 'conversions', 'conversion_value'
    ];

    /**
     * Identifies string values that should be treated as dynamic subqueries/columns rather than bound strings.
     */
    private const DYNAMIC_RIGHT_SIDE_VALUES = [
        'target_cpa', 'target_roas', 'ad_group_avg_ctr'
    ];

    public function __construct(string $targetEntity, string|array $conditionLogic)
    {
        if (!array_key_exists($targetEntity, self::ENTITY_TABLE_MAP)) {
            throw new \InvalidArgumentException("Invalid target entity: {$targetEntity}");
        }

        $this->targetEntity = $targetEntity;
        $this->logic = is_string($conditionLogic) ? json_decode($conditionLogic, true) : $conditionLogic;
    }

    /**
     * Builds and returns the raw SQL query and PDO bindings.
     * @return array ['sql' => string, 'bind' => array]
     */
    public function build(): array
    {
        $baseTable = self::ENTITY_TABLE_MAP[$this->targetEntity];
        
        // 1. Explicitly map the entity ID column to match your schema perfectly
        $entityIdCol = match ($this->targetEntity) {
            'account' => 'id',
            'ad'      => 'ad_asset_id', // Must explicitly match Domain 3 schemas
            default   => $this->targetEntity . '_id'
        };
        
        $select = "SELECT e.id FROM {$baseTable} e";
        $joins = $this->buildJoins($baseTable, $entityIdCol);
        
        // 2. Fix ad_accounts status bug: Only append e.status='ENABLED' if not evaluating an account
        $defaultWhere = ["e.deleted_at IS NULL"];
        if ($this->targetEntity !== 'account') {
            $defaultWhere[] = "e.status = 'ENABLED'";
        }

        $ruleWhereClauses = [];
        $havingClauses = [];

        // Parse Timeframe (Domain 3 filters)
        $timeframeSql = $this->buildTimeframe($this->logic['timeframe'] ?? 'last_30_days');
        if ($timeframeSql) {
            $defaultWhere[] = $timeframeSql;
        }

        // Parse Conditions Array
        $logicalOperator = strtoupper($this->logic['logical_operator'] ?? 'AND');
        $conditions = $this->logic['conditions'] ?? [];

        foreach ($conditions as $condition) {
            $domain   = $condition['domain'] ?? 'structure';
            $metric   = $condition['metric'];
            $operator = $condition['operator'];
            $value    = $condition['value'];

            // Translate Virtual Metrics on the Left Side
            $translatedLeft = $this->translateVirtualMetrics($domain, $metric, $this->targetEntity);
            $sqlMetric = $translatedLeft['sql'];
            $clauseType = $translatedLeft['type']; // 'where' or 'having'

            // Handle the Right Side Value
            if (is_string($value) && in_array($value, self::DYNAMIC_RIGHT_SIDE_VALUES)) {
                $parsedCondition = $this->translateVirtualRightSide($value, $this->targetEntity, $clauseType);
            } else {
                $parsedCondition = $this->parseCondition($metric, $operator, $value);
            }

            // Append to the appropriate clause array
            if ($clauseType === 'having') {
                $havingClauses[] = "{$sqlMetric} {$operator} {$parsedCondition}";
            } else {
                $ruleWhereClauses[] = "{$sqlMetric} {$operator} {$parsedCondition}";
            }
        }

        // Assemble the Query
        $sql = $select . "\n" . $joins . "\n";
        
        // 3. Fix Logic Operator Bug: Split default filters and user filters to allow 'OR' groups
        $sql .= "WHERE " . implode(" AND ", $defaultWhere) . "\n";
        
        if (!empty($ruleWhereClauses)) {
            $sql .= "  AND (" . implode(" {$logicalOperator} ", $ruleWhereClauses) . ")\n";
        }

        // Group by the entity ID to allow HAVING aggregations
        $sql .= "GROUP BY e.id\n";

        if (!empty($havingClauses)) {
            $sql .= "HAVING " . implode(" {$logicalOperator} ", $havingClauses);
        }

        var_dump($sql);

        return [
            'sql'  => trim($sql),
            'bind' => $this->bindings
        ];
    }

    /**
     * Translates Virtual Metrics into Raw SQL Math or Subqueries
     */
    private function translateVirtualMetrics(string $domain, string $metric, string $targetEntity): array
    {
        if ($domain === 'performance') {
            if ($metric === 'actual_cpa') {
                return ['sql' => '(SUM(p.cost) / NULLIF(SUM(p.conversions), 0))', 'type' => 'having'];
            }
            if ($metric === 'ctr') {
                return ['sql' => '(SUM(p.clicks) / NULLIF(SUM(p.impressions), 0) * 100)', 'type' => 'having'];
            }
            if ($metric === 'lost_is_budget') {
                return ['sql' => 'SUM(p.lost_is_budget)', 'type' => 'having'];
            }
            if (in_array($metric, self::PERFORMANCE_METRICS)) {
                return ['sql' => "SUM(p.{$metric})", 'type' => 'having'];
            }
        }

        if ($domain === 'structure') {
            if ($metric === 'active_ad_count' && $targetEntity === 'ad_group') {
                return ['sql' => "(SELECT COUNT(*) FROM ads_assets WHERE ad_group_id = e.id AND status = 'ENABLED')", 'type' => 'where'];
            }
            if ($metric === 'active_keyword_count' && $targetEntity === 'ad_group') {
                return ['sql' => "(SELECT COUNT(*) FROM keywords WHERE ad_group_id = e.id AND status = 'ENABLED')", 'type' => 'where'];
            }
            if ($metric === 'quality_score' && $targetEntity === 'account') {
                return [
                    'sql' => "(SELECT AVG(k.quality_score) FROM keywords k INNER JOIN ad_groups ag ON k.ad_group_id = ag.id INNER JOIN campaigns c ON ag.campaign_id = c.id WHERE c.ad_account_id = e.id AND k.status = 'ENABLED')", 
                    'type' => 'where'
                ];
            }
            if ($metric === 'token_status' && $targetEntity === 'account') {
                return ['sql' => "(SELECT status FROM api_tokens WHERE id = e.api_token_id)", 'type' => 'where'];
            }
        }
        
        if ($domain === 'health') {
            return ['sql' => "h.{$metric}", 'type' => 'where'];
        }

        return ['sql' => "e.{$metric}", 'type' => 'where'];
    }

    /**
     * Translates Virtual Right-Side values into SQL columns or subqueries
     */
    private function translateVirtualRightSide(string $value, string $targetEntity, string $clauseType): string
    {
        if (in_array($value, ['target_cpa', 'target_roas'])) {
            if ($targetEntity === 'campaign') {
                return $clauseType === 'having' ? "MAX(e.{$value})" : "e.{$value}"; 
            }
            $subquery = "(SELECT {$value} FROM campaigns c WHERE c.id = (SELECT campaign_id FROM ad_groups ag WHERE ag.id = e.ad_group_id LIMIT 1))";
            return $clauseType === 'having' ? "MAX({$subquery})" : $subquery;
        }

        if ($value === 'ad_group_avg_ctr' && $targetEntity === 'ad') {
            // FIX: Replaced e.ad_group_id with a self-contained subquery to prevent ONLY_FULL_GROUP_BY scope errors
            return "(SELECT (SUM(apd.clicks) / NULLIF(SUM(apd.impressions), 0) * 100) 
                     FROM ad_performance_daily apd 
                     INNER JOIN ads_assets aa ON apd.ad_asset_id = aa.id 
                     WHERE aa.ad_group_id = (SELECT ad_group_id FROM ads_assets WHERE id = e.id LIMIT 1))";
        }

        return "e.{$value}";
    }

    private function buildJoins(string $baseTable, string $entityIdCol): string
    {
        $joinEntityTable = self::ENTITY_BASE_TABLE_MAP[$baseTable] ?? '';
        $joins = "";
        $domainsNeeded = array_column($this->logic['conditions'] ?? [], 'domain');

        if (in_array('performance', $domainsNeeded) && $joinEntityTable !== '') {
            $perfTable = $joinEntityTable . '_performance_daily';
            $joins .= "LEFT JOIN {$perfTable} p ON e.id = p.{$entityIdCol}\n";
        }

        if (in_array('health', $domainsNeeded) && $baseTable === 'ads_assets') {
            $joins .= "LEFT JOIN url_crawl_logs h ON e.id = h.ad_asset_id\n";
        }

        return $joins;
    }

    private function buildTimeframe(string $timeframe): ?string
    {
        return match ($timeframe) {
            // 'real_time'    => "p.perf_date = CURDATE()",
            'yesterday'    => "p.perf_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
            'last_7_days'  => "p.perf_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            'last_30_days' => "p.perf_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            'month_to_date'=> "p.perf_date >= DATE_FORMAT(CURDATE() ,'%Y-%m-01')",
            default        => null
        };
    }

    private function parseCondition(string $metric, string $operator, mixed $value): string
    {
        if (strtoupper($operator) === 'IS NULL' || strtoupper($operator) === 'IS NOT NULL') {
            return ""; 
        }

        $bindKey = "val" . $this->bindIndex++;
        $this->bindings[$bindKey] = $value;

        return ":" . $bindKey;
    }
}