<?php
class SMS {
    protected $apikey;

    public function __construct($number, $message) {
        $this->apikey = '955be0f88f2ab384e73432b6f695fa042743aabe'; // iProgTech API token

        $data = [
            'api_token'   => $this->apikey,
            'phone_number'=> $number, // Try changing to 'number' if needed
            'message'     => $message
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://sms.iprogtech.com/api/v1/sms_messages',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // disable SSL verification for debugging
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        /*echo "<b>HTTP Code:</b> $httpCode<br>";
        if ($err) {
            echo "<b>cURL Error:</b> $err<br>";
        }
        echo "<b>Response:</b><pre>$response</pre>";*/
    }
}
?>
