<?php

declare(strict_types=1);


class ApiCallerService
{
    private $db = null;
    private $loginKey = null;
    private $login_key = [];

    public function __construct($dbInstance){
        $this->db = $dbInstance;
        // if (1){
        if (!isset($_SESSION['auth']['id']) || $_SESSION['auth']['id'] <= 0){
            header('Location: login');
        }
        $this->loginKey = $this->db->fetchOne(
            "SELECT `key` FROM login_key WHERE owner_type = 1 AND owner_id = :owner_id",
            \Phalcon\Db\Enum::FETCH_ASSOC,
            ["owner_id" => $_SESSION['auth']['id']]
        );
        if (!$this->loginKey){
            header('Location: login');
        }
        $this->login_key = [
            "email_or_mobile" => $_SESSION['auth']['email'],
            "owner_type" => 1,
            "owner_id" => $_SESSION['auth']['id'],
            "owner_token" =>  $this->loginKey['key'],
            "apikey" =>  $_SESSION['auth']['apikey'],
            "expiry_date" => strtotime("+7 days"),
        ];
    }

    function callApi(string $endpoint, array $_body = [], array $headers = [], int $timeout = 30): array
    {
        $ch = curl_init();

        $method = "POST";
        $url = "https://www.samtstore.com/api/v5.0-teacher/";
        $_body['API_URL'] = $endpoint;
        $_body['login_key'] = $this->login_key;

        // Handle query parameters for GET requests
        if (strtoupper($method) === 'GET' && !empty($body)) {
            $url .= '?' . http_build_query($body);
            $body = []; // Ensure no body is sent with GET
        }else{
            //Append the body
            $body['data'] = json_encode($_body);
        }

        // vd($body);
        // die();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Set connection timeout

        // Set headers
        $formattedHeaders = [];
        foreach ($headers as $key => $value) {
            $formattedHeaders[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);

        // Set body for non-GET requests
        if (!empty($body) && strtoupper($method) !== 'GET') {
            $jsonBody = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        }

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code

        // Error handling
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ['error' => $error_msg, 'status' => 500];
        }

        curl_close($ch);

        // Decode JSON response safely
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid JSON response', 'status' => $httpCode, 'raw' => $response];
        }

        return ['data' => $decodedResponse, 'status' => $httpCode];
    }

}
