<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

/**
 * Domain 4: Auditing & Automation Engine
 *
 * Alerts generated at the keyword level by audit rules.
 */
class KeywordAuditAlerts extends Model
{
    public const TABLE_NAME = 'keyword_audit_alerts';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_IGNORED = 'ignored';

    public const EXECUTION_STATUS_MANUAL_REVIEW = 'manual_review';
    public const EXECUTION_STATUS_APPROVED_FOR_QUEUE = 'approved_for_queue';
    public const EXECUTION_STATUS_PROCESSING = 'processing';
    public const EXECUTION_STATUS_API_SUCCESS = 'api_success';
    public const EXECUTION_STATUS_API_FAILED = 'api_failed';

    public ?int $id = null;
    public int $rule_id;
    public int $keyword_id;
    public string $alert_message;
    public string $status = self::STATUS_ACTIVE;
    public ?string $action_payload = null;
    public string $execution_status = self::EXECUTION_STATUS_MANUAL_REVIEW;
    public ?string $detected_at = null;
    public ?string $resolved_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);

        $this->belongsTo('rule_id', AuditRules::class, 'id', [
            'alias' => 'auditRule',
            'foreignKey' => [
                'message' => 'The audit rule does not exist',
            ],
        ]);

        $this->belongsTo('keyword_id', Keywords::class, 'id', [
            'alias' => 'keyword',
            'foreignKey' => [
                'message' => 'The keyword does not exist',
            ],
        ]);
    }

    /**
     * Find records by execution_status.
     *
     * @param string $executionStatus
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    public static function findByExecutionStatus(string $executionStatus): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return self::find([
            'conditions' => 'execution_status = :status:',
            'bind'       => ['status' => $executionStatus],
            'order'      => 'detected_at DESC'
        ]);
    }

    /**
     * Find all records ordered by detected_at DESC.
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    public static function findAll(): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return self::find([
            'order' => 'detected_at DESC'
        ]);
    }
}
