<?php

declare(strict_types=1);

/*
 * CURL Library
 * Created by: Chakrit Pinwanit
 * Purpose: Service Wrapper for easy usage of the PHP Curl
 */

define("CURL_LIB_VERSION", "v1.10.9");
// Time before the request is aborted when attempting to connect.
define("CURL_CONNECT_TIMEOUT", 30);
// Time before the request is aborted.
define("CURL_TIMEOUT", 60);
//Default CONTENT_TYPE_HEADER values
define("CURL_DEFAULT_CONTENT_TYPE_HEADER", "application/json");

global $CURL_CONFIG;

$CURL_CONFIG['CURL_VERBOSE'] = false;                                     //In production this must be FALSE only!!!
$CURL_CONFIG['CURLOPT_SSL_VERIFYHOST_AND_PEER'] = true;                  //In production this must be TRUE only!!!

class CurlService {

    // Request methods
    const REQUEST_GET = 'GET';
    const REQUEST_POST = 'POST';
    const REQUEST_DELETE = 'DELETE';
    const REQUEST_PATCH = 'PATCH';

    /** Function to execute curl request
     * @param  string $url
     * @param  string $REQUEST_METHOD
     * @param  array $HEADER - Array in the format array('Content-type: text/plain', 'Content-length: 100')
     * @param  array  $PARAMS - Array of key -> values parameters
     * @throws Exception
     * @return string
     */
    public function execute($URL, $REQUEST_METHOD, $HEADER = array(), $PARAMS = array(),$PARAM_IS_JSON = false) {
        global $CURL_CONFIG;

        $ch = curl_init($URL);

        $headers = [];
        foreach ($HEADER as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }

        curl_setopt_array($ch, $this->genOptions($REQUEST_METHOD, $headers));

        if ($PARAM_IS_JSON){
            $PARAMS_JSON = json_encode($PARAMS);
        }else{
            $PARAMS_JSON = http_build_query($PARAMS);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $PARAMS_JSON);

        $verbose = null;
        //If Verbose mode = ON
        if ($CURL_CONFIG['CURL_VERBOSE']) {
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
        }
        //Verify host
        if ($CURL_CONFIG['CURLOPT_SSL_VERIFYHOST_AND_PEER'] == FALSE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        }
        //Execute Curl and Get Result
//        ob_start();
        // Make a request or thrown an exception.
        if (($result = curl_exec($ch)) === false) {
            $error = curl_error($ch);
            $error_no = curl_errno($ch);
            curl_close($ch);
            //Show verbose details
            if ($CURL_CONFIG['CURL_VERBOSE']) {
                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                var_dump($verboseLog);
            }
            throw new Exception($error, $error_no);
        }
//        ob_get_clean();
        //Show verbose details
        if ($CURL_CONFIG['CURL_VERBOSE']) {
            $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT);
            var_dump("Header Sent:" . $headerSent);
            var_dump("Params:" . $PARAMS_JSON);
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            var_dump("Verbose Log:" . $verboseLog);
        }
        $CURL_STATUS = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);
        $RETURN_DATA = array("http-status" => $CURL_STATUS, "response" => $result);
        return $RETURN_DATA;
    }

    /**
     * Creates an option for php-curl from the given request method and parameters in an associative array.
     * @param  string $REQUEST_METHOD
     * @param  array  $PARAMS
     * @return array
     */
    private function genOptions($REQUEST_METHOD, $HEADER) {
        $user_agent = "CURL-LIB/" . CURL_LIB_VERSION . " PHP/" . phpversion();

        $options = array(
            // Set the HTTP version to 1.1.
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            // Set the request method.
            CURLOPT_CUSTOMREQUEST => $REQUEST_METHOD,
            // Make php-curl returns the data as string.
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
//            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);    // you currently have http
            // Set the HTTP Header
            CURLOPT_HTTPHEADER => $HEADER,
            // Include the header in the output. (True/False)
            CURLOPT_HEADER => true,
            // Track the header request string and set the referer on redirect.
            CURLINFO_HEADER_OUT => true,
            CURLOPT_AUTOREFERER => true,
            // Make HTTP error code above 400 an error.
            // CURLOPT_FAILONERROR => true,
            // Time before the request is aborted.
            CURLOPT_TIMEOUT => CURL_TIMEOUT,
            // Time before the request is aborted when attempting to connect.
            CURLOPT_CONNECTTIMEOUT => CURL_CONNECT_TIMEOUT,
            //Force SSL Version
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_0,
                // Authentication.
//            CURLOPT_USERPWD => $userpwd,
                // CA bundle.
//            CURLOPT_CAINFO => realpath(__DIR__ . '/../../certificate/ca_certificates.pem')
        );

        // Config UserAgent
        $options += array(CURLOPT_USERAGENT => $user_agent);
        return $options;
    }

}
