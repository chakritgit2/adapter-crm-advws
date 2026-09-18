<?php

declare(strict_types=1);

/*
 * Email Service
 * Created by: Chakrit Pinwanit
 * Purpose: To enable a wrapper for easy usage of the PHP Cur
 */

use Phalcon\Di\DiInterface;

class EmailService
{
    private const SMTP2GO_API_URL = 'https://api.smtp2go.com/v3/email/send';
    private const MAX_RETRIES = 2;

    private DiInterface $di;
    private $errorService;
    private $config;
    private CurlService $curlService;
    private $db;

    public function __construct(DiInterface $di)
    {
        $this->di = $di;
        $this->errorService = $di->get('errorService');
        $this->config = $di->get('config');
        $this->curlService = new CurlService();
        $this->db = $di->get('db');
    }

    public function queueEmail(string $recipient, string $subject, string $body): array
    {
        try {
            $this->db->execute(
                "INSERT INTO email_outbox (recipient, subject, body, status) VALUES (?, ?, ?, ?)",
                [$recipient, $subject, $body, EmailOutbox::STATUS_NEW]
            );
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return $this->errorService->error('EMAIL_QUEUE_FAILED', $e->getMessage());
        }
    }

    public function sendEmail(string $recipient, string $subject, string $bodyHtml): array
    {
        $smtp2go = $this->config->get('smtp2go');
        if (!$smtp2go) {
            return $this->errorService->error('SMTP2GO_CONFIG_MISSING', 'SMTP2GO configuration is missing');
        }

        $apiKey = $smtp2go->get('api_key');
        $sender = $smtp2go->get('sender');

        if (empty($apiKey) || $apiKey === 'YOUR_SMTP2GO_API_KEY') {
            return $this->errorService->error('SMTP2GO_API_KEY_MISSING', 'SMTP2GO API key is not configured');
        }
        if (empty($sender) || $sender === 'sender@example.com') {
            return $this->errorService->error('SMTP2GO_SENDER_MISSING', 'SMTP2GO sender address is not configured');
        }

        $payload = [
            'sender' => $sender,
            'to' => [$recipient],
            'subject' => $subject,
            'html_body' => $bodyHtml,
            'text_body' => $this->stripHtmlToText($bodyHtml),
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Smtp2go-Api-Key' => $apiKey,
        ];

        try {
            $response = $this->curlService->execute(self::SMTP2GO_API_URL, 'POST', $headers, $payload, true);
        } catch (Exception $e) {
            return $this->errorService->error('SMTP2GO_CURL_ERROR', $e->getMessage());
        }

        $body = $this->parseResponseBody($response['response'] ?? '');
        $httpStatus = $response['http-status'] ?? 0;

        if ($httpStatus === 200 && isset($body['data']['succeeded']) && $body['data']['succeeded'] > 0) {
            return [
                'success' => true,
                'email_id' => $body['data']['email_id'] ?? null,
                'request_id' => $body['request_id'] ?? null,
            ];
        }

        $errorMessage = $body['data']['error'] ?? ($body['error'] ?? "HTTP {$httpStatus}");
        return $this->errorService->error('SMTP2GO_SEND_FAILED', $errorMessage);
    }

    public function sendInternal(): array
    {
        $this->db->begin();
        $email = $this->db->fetchOne(
            "SELECT id, recipient, subject, body, retry_count
             FROM email_outbox
             WHERE status IN ('new', 'error')
               AND (next_retry_at IS NULL OR next_retry_at <= NOW())
             ORDER BY id ASC
             LIMIT 1
             FOR UPDATE SKIP LOCKED",
            \Phalcon\Db\Enum::FETCH_ASSOC
        );

        if (empty($email)) {
            $this->db->commit();
            return ['success' => true, 'no_work' => true];
        }

        $this->db->execute(
            "UPDATE email_outbox SET status = 'processing', updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$email['id']]
        );
        $this->db->commit();

        $result = $this->sendEmail($email['recipient'], $email['subject'], $email['body']);

        if ($this->errorService->isError($result)) {
            $retryCount = (int)$email['retry_count'] + 1;
            if ($retryCount > self::MAX_RETRIES) {
                $this->db->execute(
                    "UPDATE email_outbox
                     SET status = 'failed', retry_count = ?, error_message = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?",
                    [$retryCount, $result['error_message'], $email['id']]
                );
            } else {
                $interval = $retryCount === 1 ? 'INTERVAL 1 MINUTE' : 'INTERVAL 5 MINUTE';
                $this->db->execute(
                    "UPDATE email_outbox
                     SET status = 'error', retry_count = ?, next_retry_at = NOW() + {$interval}, error_message = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?",
                    [$retryCount, $result['error_message'], $email['id']]
                );
            }
            return $result;
        }

        $this->db->execute(
            "UPDATE email_outbox
             SET status = 'success', error_message = NULL, next_retry_at = NULL, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$email['id']]
        );

        return $result;
    }

    private function stripHtmlToText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/p>/i', "\n", $text ?? '');
        $text = strip_tags($text ?? '');
        return trim($text);
    }

    private function parseResponseBody(string $rawResponse): array
    {
        if (empty($rawResponse)) {
            return [];
        }
        $parts = explode("\r\n\r\n", $rawResponse);
        if (count($parts) < 2) {
            $parts = explode("\n\n", $rawResponse);
        }
        $body = trim(end($parts));
        if (empty($body)) {
            return [];
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }
}