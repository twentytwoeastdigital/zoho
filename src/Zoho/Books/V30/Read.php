<?php

namespace TwentyTwoEastDigital\Zoho\Books\V30;

use Illuminate\Support\Facades\Cache;
use TwentyTwoEastDigital\Zoho\ZohoOAuth;

class Read
{
    protected $zohoOAuth;
    protected $apiVersion;
    protected $rateLimitInterval;
    protected static $lastRequestTime;
    protected $apiBaseUrl;
    protected $organizationId;
    protected $module;
    protected $cacheDuration;
    protected $overrideCache;

    public function __construct(ZohoOAuth $zohoOAuth, $organizationId, $module, $cacheDuration = 600, $overrideCache = false)
    {
        $this->zohoOAuth = $zohoOAuth;
        $this->organizationId = $organizationId;
        $this->module = $module;
        $this->apiVersion = 'v3';
        $this->rateLimitInterval = config('zoho.books.rate_limit_interval');
        self::$lastRequestTime = self::$lastRequestTime ?? microtime(true);
        $this->apiBaseUrl = 'https://www.zohoapis.com/books/';
        $this->cacheDuration = $cacheDuration;
        $this->overrideCache = $overrideCache;
        if (self::$lastRequestTime === null)
        {
            self::$lastRequestTime = microtime(true);
        }
    }

    private function buildUrl()
    {
        return "{$this->apiBaseUrl}{$this->apiVersion}/{$this->module}?organization_id={$this->organizationId}";
    }

    private function buildRecordUrl($recordId)
    {
        return "{$this->apiBaseUrl}{$this->apiVersion}/{$this->module}/{$recordId}?organization_id={$this->organizationId}";
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

    public function getRecords($query = null)
    {
        // todo: add paging
        $cacheKey = "books_v3_{$this->organizationId}_{$this->module}_records";

        if (!$this->overrideCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $this->throttle();
        $requestUrl = $this->buildUrl();

        if (!empty($query)) {
            $requestUrl .= "&{$query}";
        }

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
            return ['success' => false, 'error' => $err];
        } else {
            $response_json = json_decode($response, true);
            Cache::put($cacheKey, ['success' => true, 'response' => $response_json], $this->cacheDuration);
            if(isset($response_json[$this->module]))
            {
                return $response_json[$this->module];
            }
            else
            {
                return $response_json;
            }
        }
    }

    public function getRecordById($recordId)
    {
        $cacheKey = "books_v3_{$this->organizationId}_{$this->module}_record_{$recordId}";

        if (!$this->overrideCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $this->throttle();
        $requestUrl = $this->buildRecordUrl($recordId);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 60,
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
            return ['success' => false, 'error' => $err];
        } else {
            $response_json = json_decode($response, true);

            if(!empty($response_json))
            {
                // Cache the result
                Cache::put($cacheKey, $response_json, $this->cacheDuration);

                return $response_json;
            }
            else
            {
                return ['success' => false, 'error' => 'Record not found.'];
            }
        }
    }
}
