<?php

namespace TwentyTwoEastDigital\Zoho\CRM\V70;

use TwentyTwoEastDigital\Zoho\ZohoOAuth;

class Create
{
    protected $zohoOAuth;
    protected $apiVersion;
    protected $rateLimitInterval;
    protected static $lastRequestTime;
    protected $apiBaseUrl;
    protected $module;
    protected $data;

    public function __construct(ZohoOAuth $zohoOAuth, $module, $data = [])
    {
        $this->zohoOAuth = $zohoOAuth;
        $this->module = $module;
        $this->apiVersion = config('zoho.crm.api_version', 'v7');
        $this->rateLimitInterval = config('zoho.crm.rate_limit_interval', 1);
        self::$lastRequestTime = self::$lastRequestTime ?? microtime(true);
        $this->apiBaseUrl = config('zoho.crm.api_base_url', 'https://www.zohoapis.com/crm/');
        $this->data = $data;
    }

    private function buildUrl()
    {
        return "{$this->apiBaseUrl}{$this->apiVersion}/{$this->module}";
    }

    protected function throttle()
    {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - self::$lastRequestTime;
        if ($timeSinceLastRequest < $this->rateLimitInterval) {
            usleep((int)(($this->rateLimitInterval - $timeSinceLastRequest) * 1e6));
        }
        self::$lastRequestTime = microtime(true);
    }

    public function request()
    {
        $this->throttle();
        $requestUrl = $this->buildUrl();
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 240,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($this->data),
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Zoho-oauthtoken ' . $this->zohoOAuth->getAccessToken(),
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['error' => $err];
        } else {
            $response = json_decode($response, true);
            if(isset($response['data'][0]['code']) && $response['data'][0]['code'] == 'SUCCESS') {
                return $response['data'][0]['details']['id'];
            }
            return $response ?? ['error' => 'Invalid response from Zoho CRM'];
        }
    }
}
