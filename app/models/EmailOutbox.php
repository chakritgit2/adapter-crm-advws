<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class EmailOutbox extends Model
{
    public const TABLE_NAME = 'email_outbox';

    public const STATUS_NEW = 'new';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';
    public const STATUS_FAILED = 'failed';

    public ?int $id = null;
    public string $recipient;
    public string $subject;
    public string $body;
    public string $status = self::STATUS_NEW;
    public int $retry_count = 0;
    public ?string $next_retry_at = null;
    public ?string $error_message = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function initialize(): void
    {
        $this->setSource(self::TABLE_NAME);
    }
}
