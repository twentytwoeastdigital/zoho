<?php

namespace TwentyTwoEastDigital\Zoho\Books\V30;

use Illuminate\Support\Facades\Cache;
use TwentyTwoEastDigital\Zoho\ZohoOAuth;

class Create
{
    protected $zohoOAuth;
    protected $apiVersion;
    protected $rateLimitInterval;
    protected static $lastRequestTime;
    protected $apiBaseUrl;
    protected $organizationId;
    protected $module;

    public function __construct(ZohoOAuth $zohoOAuth, $organizationId, $module)
    {
        $this->zohoOAuth = $zohoOAuth;
        $this->organizationId = $organizationId;
        $this->module = $module;
        $this->apiVersion = 'v3';
        $this->rateLimitInterval = config('zoho.books.rate_limit_interval');
        self::$lastRequestTime = self::$lastRequestTime ?? microtime(true);
        $this->apiBaseUrl = 'https://www.zohoapis.com/books/';
        if (self::$lastRequestTime === null)
        {
            self::$lastRequestTime = microtime(true);
        }
    }

    private function buildUrl()
    {
        return "{$this->apiBaseUrl}{$this->apiVersion}/{$this->module}?organization_id={$this->organizationId}";
    }

    protected function throttle()
    {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - self::$lastRequestTime;
        $sleepTime = 0;
        if ($timeSinceLastRequest < $this->rateLimitInterval)
        {
            $sleepTime = ($this->rateLimitInterval - $timeSinceLastRequest) * 1e6; // Convert to microseconds
            $sleepTime = (int)$sleepTime;
            usleep($sleepTime);
        }
        self::$lastRequestTime = microtime(true);
    }

    public function request($data)
    {
        $this->throttle();
        $requestUrl = $this->buildUrl();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => array(
                'Authorization: Zoho-oauthtoken ' . $this->zohoOAuth->getAccessToken(),
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['success' => false, 'error' => $err];
        } else {
            $response_json = json_decode($response, true);
            return $response_json;
        }
    }
}