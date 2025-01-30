<?php

namespace TwentyTwoEastDigital\Zoho\Analytics\V20;

use Illuminate\Support\Facades\Cache;
use TwentyTwoEastDigital\Zoho\ZohoOAuth;

class Read
{
    protected $zohoOAuth;
    protected $apiVersion;
    protected $rateLimitInterval;
    protected static $lastRequestTime;
    protected $apiBaseUrl;
    protected $workspaceId;
    protected $viewName;
    protected $cacheDuration;
    protected $overrideCache;
    protected $organizationId;

    public function __construct(ZohoOAuth $zohoOAuth, $organizationId, $workspaceId, $viewName, $cacheDuration = 600, $overrideCache = false)
    {
        $this->zohoOAuth = $zohoOAuth;
        $this->organizationId = $organizationId;
        $this->workspaceId = $workspaceId;
        $this->viewName = $viewName;
        $this->apiVersion = 'v2';
        $this->rateLimitInterval = config('zoho.analytics.rate_limit_interval');
        self::$lastRequestTime = self::$lastRequestTime ?? microtime(true);
        $this->apiBaseUrl = 'https://analyticsapi.zoho.com/restapi/';
        $this->cacheDuration = $cacheDuration;
        $this->overrideCache = $overrideCache;
    }

    private function buildUrl()
    {
        return "{$this->apiBaseUrl}v2/workspaces/{$this->workspaceId}/views/{$this->viewName}/data";
    }

    protected function throttle()
    {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - self::$lastRequestTime;
        
        if ($timeSinceLastRequest < $this->rateLimitInterval) {
            usleep(($this->rateLimitInterval - $timeSinceLastRequest) * 1e6);
        }
        
        self::$lastRequestTime = microtime(true);
    }

    public function getRecords($queryParams = [])
    {
        $cacheKey = "analytics_v2_{$this->workspaceId}_{$this->viewName}_records";
        
        if (!$this->overrideCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $this->throttle();
        $requestUrl = $this->buildUrl();

        $queryParams['CONFIG'] = json_encode(['responseFormat' => 'JSON']);
        
        if (!empty($queryParams)) {
            $requestUrl .= '?' . http_build_query($queryParams);
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Zoho-oauthtoken ' . $this->zohoOAuth->getAccessToken(),
                'Content-Type: application/json',
                'Accept: application/json',
                'ZANALYTICS-ORGID: ' . $this->organizationId,
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['success' => false, 'error' => $err];
        }
        
        $responseJson = json_decode($response, true);
        
        if (!empty($responseJson)) {
            Cache::put($cacheKey, $responseJson, $this->cacheDuration);
            return $responseJson;
        }
        
        return ['success' => false, 'error' => 'No records found.'];
    }
}