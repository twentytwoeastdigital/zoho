<?php

namespace TwentyTwoEastDigital\Zoho\Creator\V21;

use TwentyTwoEastDigital\Zoho\ZohoOAuth;

class Create
{
    protected $zohoOAuth;
    protected $apiVersion;
    protected $rateLimitInterval;
    protected static $lastRequestTime;
    protected $apiBaseUrl;
    protected $appOwner;
    protected $appLinkName;
    protected $endpoint;
    protected $data;

    public function __construct(ZohoOAuth $zohoOAuth, $endpoint, $data = [])
    {
        $this->zohoOAuth = $zohoOAuth;
        $this->endpoint = $endpoint;
        $this->apiVersion = config('zoho.creator.api_version');
        $this->rateLimitInterval = config('zoho.creator.rate_limit_interval');
        self::$lastRequestTime = self::$lastRequestTime ?? microtime(true);
        $this->apiBaseUrl = config('zoho.creator.api_base_url');
        $this->appOwner = config('zoho.creator.app_owner');
        $this->appLinkName = config('zoho.creator.app_link_name');
        if (self::$lastRequestTime === null) 
        { 
            self::$lastRequestTime = microtime(true); 
        }
        $this->data = $data;
    }

    private function buildUrl()
    {
        return "{$this->apiBaseUrl}{$this->apiVersion}/data/{$this->appOwner}/{$this->appLinkName}/form/{$this->endpoint}";
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

    public function request()
    {
        $this->throttle();
        $requestUrl = $this->buildUrl();
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 240,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode(['data' => [$this->data]]),
            CURLOPT_HTTPHEADER     => array(
                'Accept: application/json',
                'Authorization: Zoho-oauthtoken ' . $this->zohoOAuth->getAccessToken(),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return $err;
        } else {
            $response_json = json_decode($response, true);
            if ($response_json['code'] == 3000) {
                if(isset($response_json['result'][0]['data']['ID'])) {
                    return $response_json['result'][0]['data']['ID'];
                }
            } 
        }
        return $response_json;
    }
}