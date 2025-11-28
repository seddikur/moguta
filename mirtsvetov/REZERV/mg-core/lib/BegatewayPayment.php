<?php

/**
 * Class BegatewayPayment
 */
class BegatewayPayment
{
    private $shopId;
    private $shopSecretKey;
    private $shopPublicKey = null;
    private $paymentDomain;
    private $test;

    /**
     * BegatewayPayment constructor.
     * @param $shopId
     * @param $shopSecretKey
     * @param $shopPublicKey
     * @param $paymentDomain
     * @param $test
     */
    public function __construct($shopId, $shopSecretKey, $shopPublicKey, $paymentDomain, $test)
    {
        $this->shopId = $shopId;
        $this->shopSecretKey = $shopSecretKey;
        if (!empty($shopPublicKey)) {
            $this->shopPublicKey = $shopPublicKey;
        }
        $this->paymentDomain = 'https://' . $paymentDomain;
        $this->test = $test;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getPayLink($data)
    {
        $success_url = $this->correctUrl($data['orderInfo'][$data['id']]['payment_id'], 'success', $data['id']);
        $failure_url = $this->correctUrl($data['orderInfo'][$data['id']]['payment_id'], 'fail', $data['id']);
        $notify_url = $this->correctUrl($data['orderInfo'][$data['id']]['payment_id'], 'result', $data['id']);

        $language = 'ru';
        if(defined('LANG') && LANG != 'LANG' && LANG != 'default') {
            $language = LANG;
        }

        $amount = round($data['summ'] * 100);
        $currency = $data['orderInfo'][$data['id']]['currency_iso'];
        if ($currency == 'RUR') {
            $currency = 'RUB';
        }
        if ($currency == 'BYR') {
            $currency = 'BYN';
        }

        $tokenData = [
            'checkout' => [
                'order' => [
                    'amount' => $amount,
                    'currency' => $currency,
                    'description' => 'Order N: ' . $data['orderNumber'],
                    'tracking_id' => $data['orderNumber'],
                    'additional_data' => [
                        'platform_data' => 'Moguta.CMS ' . EDITION . ' ' . VER,
                        'integration_data' => 'beGateway payment integration'
                    ],
                ],
                'settings' => [
                    'success_url' => $success_url,
                    'decline_url' => $failure_url,
                    'fail_url' => $failure_url,
                    'cancel_url' => $failure_url,
                    'notification_url' => $notify_url,
                    'language' => $language,
                ],
                'customer' => [
                    'email' => $data['orderInfo'][$data['id']]['user_email'],
                    'first_name' => $data['orderInfo'][$data['id']]['name_buyer'],
                    'phone' => preg_replace("/[^0-9+]/", '', $data['orderInfo'][$data['id']]['phone']),
                ],
                'transaction_type' => 'payment',
                'version' => 2,
                'test' => (boolean) $this->test,
            ]
        ];

        return $this->curlSubmit($this->paymentDomain . '/ctp/api/checkouts', $tokenData, $this->shopId, $this->shopSecretKey);
    }

    /**
     * @return bool
     */
    public function isAuthorized()
    {
        if (isset($_SERVER['HTTP_CONTENT_SIGNATURE']) && !is_null($this->shopPublicKey)) {
            $signature  = base64_decode($_SERVER['HTTP_CONTENT_SIGNATURE']);
            $public_key = str_replace(array("\r\n", "\n"), '', $this->shopPublicKey);
            $public_key = chunk_split($public_key, 64);
            $public_key = "-----BEGIN PUBLIC KEY-----\n" . $public_key . "-----END PUBLIC KEY-----";
            $key = openssl_pkey_get_public($public_key);
            if ($key) {
                return openssl_verify(file_get_contents('php://input'), $signature, $key, OPENSSL_ALGO_SHA256) == 1;
            }
        }

        $token = null;
        $_id = null;
        $_key = null;

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $_id  = $_SERVER['PHP_AUTH_USER'];
            $_key = $_SERVER['PHP_AUTH_PW'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION']) && !is_null($_SERVER['HTTP_AUTHORIZATION'])) {
            $token = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !is_null($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $token = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if ($token != null) {
            if (strpos(strtolower($token), 'basic') === 0) {
                list($_id, $_key) = explode(':', base64_decode(substr($token, 6)));
            }
        }

        return $_id == $this->shopId
            && $_key == $this->shopSecretKey;
    }

    /**
     * Set Errors params and header for error page
     *
     * @param $code
     * @param $message
     * @return string
     */
    public function setError($code, $message)
    {
        MG::loger('ERROR PAYMENT: ' . $code . ": " . $message);
        header("{$_SERVER['SERVER_PROTOCOL']} " . $code . " " . $message);
        return '<h1>Error ' . $code . '</h1><p>' . $message . '</p>';
    }

    /**
     * @param $host
     * @param $data
     * @param $shopId
     * @param $shopSecretKey
     * @return mixed
     */
    private function curlSubmit($host, $data, $shopId, $shopSecretKey)
    {
        $process = curl_init($host);
        $json = json_encode($data);

        if (!empty($data)) {
            curl_setopt($process, CURLOPT_HTTPHEADER,
                array(
                    'Accept: application/json',
                    'Content-type: application/json',
                    'X-API-Version: 2',
                )
            );
            curl_setopt($process, CURLOPT_POST, 1);
            curl_setopt($process, CURLOPT_POSTFIELDS, $json);
        } else {
            curl_setopt($process, CURLOPT_HTTPHEADER,
                array(
                    'Accept: application/json',
                    'X-API-Version: 2',
                ),
        );
        }

        curl_setopt($process, CURLOPT_URL, $host);
        curl_setopt($process, CURLOPT_USERPWD, $shopId . ":" . $shopSecretKey);
        curl_setopt($process, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, TRUE);
        $response = curl_exec($process);
        curl_close($process);

        return json_decode($response);
    }

    /**
     * @param $id
     * @param $pay
     * @param $orderNumber
     * @return string
     */
    private function correctUrl($id, $pay, $order)
    {
        $base_url = SITE;
        if (defined('LANG') && LANG != 'LANG' && LANG != 'default') {
            $base_url = '/' . LANG;
        }

        return htmlspecialchars_decode($base_url . '/payment?id=' . $id . '&pay=' . $pay . '&order=' . urlencode($order));
    }

}
