<?php

namespace TwentyTwoEastDigital\Zoho\Analytics\V20;

use Illuminate\Support\Facades\Cache;
use TwentyTwoEastDigital\Zoho\ZohoOAuth;

class Bulk
{
    protected $zohoOAuth;
    protected $organizationId;
    protected $workspaceId;
    protected $viewName;
    protected $apiVersion;
    protected $rateLimitInterval;
    protected static $lastRequestTime;
    protected $apiBaseUrl;
    protected $maxCalls = 60;
    protected $sleepInterval = 5;
    protected static $lastBulkRequestTime;
    protected static $bulkRequestCount;
    protected static $lastDownloadRequestTime;
    protected static $downloadRequestCount;
    protected $overrideCache;
    protected $cacheDuration;

    public function __construct(ZohoOAuth $zohoOAuth, $organizationId, $workspaceId, $viewName, $overrideCache = false, $cacheDuration = 600)
    {
        $this->zohoOAuth = $zohoOAuth;
        $this->organizationId = $organizationId;
        $this->workspaceId = $workspaceId;
        $this->viewName = $viewName;
        $this->apiVersion = 'v2';
        $this->rateLimitInterval = config('zoho.analytics.rate_limit_interval');
        self::$lastRequestTime = self::$lastRequestTime ?? microtime(true);
        $this->apiBaseUrl = 'https://analyticsapi.zoho.com/restapi/';
        self::$lastBulkRequestTime = self::$lastBulkRequestTime ?? microtime(true);
        self::$bulkRequestCount = self::$bulkRequestCount ?? 0;
        self::$lastDownloadRequestTime = self::$lastDownloadRequestTime ?? microtime(true);
        self::$downloadRequestCount = self::$downloadRequestCount ?? 0;
        $this->overrideCache = $overrideCache;
        $this->cacheDuration = $cacheDuration;
    }

    protected function buildUrl()
    {
        return "{$this->apiBaseUrl}v2/bulk/workspaces/{$this->workspaceId}/views/{$this->viewName}/data";
    }

    protected function throttle($type)
    {
        $currentTime = microtime(true);
        if ($type === 'bulk') {
            $timeSinceLastRequest = $currentTime - self::$lastBulkRequestTime;
            self::$bulkRequestCount++;
            if (self::$bulkRequestCount > 5) {
                if ($timeSinceLastRequest < 60) {
                    usleep((60 - $timeSinceLastRequest) * 1e6);
                }
                self::$bulkRequestCount = 0;
            }
            self::$lastBulkRequestTime = microtime(true);
        }
    }

    public function request()
    {
        $cacheKey = "bulk_{$this->workspaceId}_{$this->viewName}";
        
        if (!$this->overrideCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $this->throttle('bulk');
        $request = $this->requestExport();
        $exportId = $request['data']['jobId'] ?? null;

        if (!$exportId) {
            return ['success' => false, 'error' => 'Failed to initiate export request.'];
        }

        $callCount = 0;
        while ($callCount < $this->maxCalls) {
            sleep($this->sleepInterval);
            $status = $this->checkExportStatus($exportId);
            
            if (strtolower($status['data']['jobCode'] ?? '') === '1004') {
                $this->throttle('download');
                $result = $this->downloadExport($exportId);
                Cache::put($cacheKey, $result, $this->cacheDuration);
                return $result;
            }
            
            $callCount++;
        }

        return ['success' => false, 'error' => 'Export request timed out.'];
    }

    public function requestExport()
    {
        $this->throttle('bulk');
        $requestUrl = $this->buildUrl();

        $queryParams = [];
        $queryParams['CONFIG'] = json_encode(['responseFormat' => 'JSON']);

        if (!empty($queryParams)) {
            $requestUrl .= '?' . http_build_query($queryParams);
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER     => [
                'ZANALYTICS-ORGID: ' . $this->organizationId,
                'Authorization: Zoho-oauthtoken ' . $this->zohoOAuth->getAccessToken(),
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    public function checkExportStatus($exportId)
    {
        $requestUrl = "{$this->apiBaseUrl}v2/bulk/workspaces/{$this->workspaceId}/exportjobs/{$exportId}";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'ZANALYTICS-ORGID: ' . $this->organizationId,
                'Authorization: Zoho-oauthtoken ' . $this->zohoOAuth->getAccessToken(),
                'Content-Type: application/json'
            ],
        ]);
        
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    public function downloadExport($exportId)
    {
        $requestUrl = "{$this->apiBaseUrl}v2/bulk/workspaces/{$this->workspaceId}/exportjobs/{$exportId}/data";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'ZANALYTICS-ORGID: ' . $this->organizationId,
                'Authorization: Zoho-oauthtoken ' . $this->zohoOAuth->getAccessToken(),
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}