<?php
declare(strict_types=1);

use Phalcon\Http\Client\Provider\Curl;
use Phalcon\Http\Client\Provider\Exception as HttpClientException;

class LineAuthService
{
    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var string
     */
    private $channelSecret;

    /**
     * @var Curl
     */
    private $httpClient;

    /**
     * LINE API endpoints
     */
    const AUTH_URL = 'https://access.line.me/oauth2/v2.1/authorize';
    const TOKEN_URL = 'https://api.line.me/oauth2/v2.1/token';
    const PROFILE_URL = 'https://api.line.me/v2/profile';
    const VERIFY_URL = 'https://api.line.me/oauth2/v2.1/verify';
    const REVOKE_URL = 'https://api.line.me/oauth2/v2.1/revoke';

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->redirectUri = $config['redirect_uri'] ?? '';
        $this->channelSecret = $config['channel_secret'] ?? '';
        $this->httpClient = new Curl();
    }

    /**
     * Get LINE Login authorization URL
     */
    public function getAuthUrl(string $state, array $scope = ['openid', 'profile', 'email']): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => implode(' ', $scope),
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Get access token from authorization code
     */
    public function getAccessToken(string $code): ?array
    {
        try {
            $response = $this->httpClient->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ], [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);

            if ($response->header->statusCode === 200) {
                return json_decode($response->body, true);
            }

            return null;
        } catch (HttpClientException $e) {
            // Log error
            error_log('LINE Auth Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user profile
     */
    public function getProfile(string $accessToken): ?array
    {
        try {
            $response = $this->httpClient->get(self::PROFILE_URL, [], [
                'Authorization' => 'Bearer ' . $accessToken,
            ]);

            if ($response->header->statusCode === 200) {
                return json_decode($response->body, true);
            }

            return null;
        } catch (HttpClientException $e) {
            // Log error
            error_log('LINE Profile Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify ID token
     */
    public function verifyIdToken(string $idToken): ?array
    {
        try {
            $response = $this->httpClient->post(self::VERIFY_URL, [
                'id_token' => $idToken,
                'client_id' => $this->clientId,
            ], [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);

            if ($response->header->statusCode === 200) {
                return json_decode($response->body, true);
            }

            return null;
        } catch (HttpClientException $e) {
            // Log error
            error_log('LINE Verify Token Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Revoke access token
     */
    public function revokeToken(string $accessToken): bool
    {
        try {
            $response = $this->httpClient->post(self::REVOKE_URL, [
                'access_token' => $accessToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ], [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);

            return $response->header->statusCode === 200;
        } catch (HttpClientException $e) {
            // Log error
            error_log('LINE Revoke Token Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $signature, string $body): bool
    {
        $hash = hash_hmac('sha256', $body, $this->channelSecret, true);
        $signatureBase64 = base64_encode($hash);
        return hash_equals($signatureBase64, $signature);
    }
}
