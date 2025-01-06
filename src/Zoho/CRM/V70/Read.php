<?php

namespace TwentyTwoEastDigital\Zoho\CRM\V70;

use Illuminate\Support\Facades\Log;
use TwentyTwoEastDigital\Zoho\ZohoOAuth;

class Read
{
    protected $zohoOAuth;
    protected $apiVersion;
    protected $rateLimitInterval;
    protected static $lastRequestTime;
    protected $apiBaseUrl;
    protected $module;
    protected $query;

    public function __construct(ZohoOAuth $zohoOAuth, $module, $query = null, $cache_override = false, $cache_duration = 600)
    {
        $this->zohoOAuth = $zohoOAuth;
        $this->module = $module;
        $this->apiVersion = 'v7';
        $this->rateLimitInterval = config('zoho.crm.rate_limit_interval');
        self::$lastRequestTime = self::$lastRequestTime ?? microtime(true);
        $this->apiBaseUrl = 'https://www.zohoapis.com/crm/';
        if (self::$lastRequestTime === null)
        {
            self::$lastRequestTime = microtime(true);
        }
        $this->query = $query;
    }

    private function buildUrl()
    {
        if ($this->query)
        {
            return "{$this->apiBaseUrl}{$this->apiVersion}/{$this->module}/search?criteria={$this->query}";
        }
        return "{$this->apiBaseUrl}{$this->apiVersion}/{$this->module}";
    }

    private function buildRecordUrl($recordId)
    {
        return "{$this->apiBaseUrl}{$this->apiVersion}/{$this->module}/{$recordId}";
    }

    protected function throttle()
    {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - self::$lastRequestTime;
        $sleepTime = 0;
        if ($timeSinceLastRequest < $this->rateLimitInterval)
        {
            $sleepTime = ($this->rateLimitInterval - $timeSinceLastRequest) * 1e6; // Convert to microseconds
            usleep($sleepTime);
        }
        self::$lastRequestTime = microtime(true);
    }

    public function getRecords()
    {
        $this->throttle();
        $requestUrl = $this->buildUrl();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
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
            return $err;
        } else {
            $response_json = json_decode($response, true);
            if(isset($response_json['data'])) {
                return $response_json['data'];
            } else {
                return $response_json;
            }
        }
    }

    public function getRecordById($recordId)
    {
        $this->throttle();
        $requestUrl = $this->buildRecordUrl($recordId);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
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
            Log::error('cURL Error: ' . $err);
            return ['success' => false, 'error' => $err];
        } else {
            $response_json = json_decode($response, true);
            if (isset($response_json['data'][0])) {
                return $response_json['data'][0];
            } else {
                return $response_json;
            }
        }
    }
}