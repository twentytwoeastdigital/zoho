<?php

namespace TwentyTwoEastDigital\Zoho\Creator\V21;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use TwentyTwoEastDigital\Zoho\ZohoOAuth;
use ZipArchive;

class Bulk
{
    protected $zohoOAuth;
    protected $endpoint;
    protected $apiVersion;
    protected $rateLimitInterval;
    protected static $lastRequestTime;
    protected $apiBaseUrl;
    protected $defaultPerPage;
    protected $appOwner;
    protected $appLinkName;
    protected $max_calls = 60;
    protected $sleep_interval = 5;
    protected static $lastBulkRequestTime; 
    protected static $bulkRequestCount; 
    protected static $lastDownloadRequestTime; 
    protected static $downloadRequestCount;
    protected $overrideCache;
    protected $cacheDuration;

    public function __construct(ZohoOAuth $zohoOAuth, $endpoint, $overrideCache = false, $cacheDuration = 600)
    {
        $this->zohoOAuth = $zohoOAuth;
        $this->endpoint = $endpoint;
        $this->apiVersion = config('zoho.creator.api_version');
        $this->rateLimitInterval = config('zoho.creator.rate_limit_interval');
        self::$lastRequestTime = self::$lastRequestTime ?? microtime(true);
        $this->apiBaseUrl = config('zoho.creator.api_base_url');
        $this->defaultPerPage = config('zoho.creator.paging.default_per_page');
        $this->appOwner = config('zoho.creator.app_owner');
        $this->appLinkName = config('zoho.creator.app_link_name');
        self::$lastBulkRequestTime = self::$lastBulkRequestTime ?? microtime(true); 
        self::$bulkRequestCount = self::$bulkRequestCount ?? 0; 
        self::$lastDownloadRequestTime = self::$lastDownloadRequestTime ?? microtime(true); 
        self::$downloadRequestCount = self::$downloadRequestCount ?? 0;
        $this->overrideCache = $overrideCache;
        $this->cacheDuration = $cacheDuration;
    }

    protected function throttle($type) { 
        $currentTime = microtime(true); 
        if ($type === 'bulk') 
        { 
            $timeSinceLastRequest = $currentTime - self::$lastBulkRequestTime; 
            self::$bulkRequestCount++; 
            if (self::$bulkRequestCount > 5) 
            { 
                if ($timeSinceLastRequest < 60) 
                { 
                    $sleepTime = (int)((60 - $timeSinceLastRequest) * 1e6);
                    usleep($sleepTime);  
                } 
                self::$bulkRequestCount = 0; 
            } 
            self::$lastBulkRequestTime = microtime(true); 
        } 
        if ($type === 'download') 
        { 
            $timeSinceLastRequest = $currentTime - self::$lastDownloadRequestTime; 
            self::$downloadRequestCount++; 
            if (self::$downloadRequestCount > 10) 
            { 
                if ($timeSinceLastRequest < 60) 
                { 
                    $sleepTime = (int)((60 - $timeSinceLastRequest) * 1e6);
                    usleep($sleepTime);
                } 
                self::$downloadRequestCount = 0; 
            } 
            self::$lastDownloadRequestTime = microtime(true); 
        } 
    }

    protected function buildUrl($endpoint = null)
    {
        $endpoint = $endpoint ?? $this->endpoint;
        return "{$this->apiBaseUrl}{$this->apiVersion}/bulk/{$this->appOwner}/{$this->appLinkName}/report/{$endpoint}";
    }

    public function request()
    {
        $endpoints = is_array($this->endpoint) ? $this->endpoint : [$this->endpoint];
        $cacheKeys = array_map(fn($endpoint) => "bulk_{$endpoint}", $endpoints);

        if (!$this->overrideCache) {
            $cachedResults = [];
            foreach ($cacheKeys as $index => $cacheKey) {
                if (Cache::has($cacheKey)) {
                    $cachedResults[$endpoints[$index]] = Cache::get($cacheKey);
                }
            }
            if (count($cachedResults) === count($endpoints)) {
                return $cachedResults;
            }
        }

        $requestIDs = [];
        foreach ($endpoints as $endpoint) {
            $this->throttle('bulk');
            $request = $this->submitRequest($endpoint);
            $requestIDs[$endpoint] = $request['details']['id'];
        }

        $call_count = 0;
        $results = [];
        while (count($results) < count($endpoints) && $call_count < $this->max_calls) {
            foreach ($requestIDs as $endpoint => $requestID) {
                if(isset($results[$endpoint])) {
                    continue;
                }
                if (strtolower($this->checkRequest($endpoint, $requestID)['details']['status']) == 'completed') {
                    $this->throttle('download');
                    $results[$endpoint] = $this->downloadRequest($endpoint, $requestID);
                    continue;
                }
            }
            if (count($results) < count($endpoints)) {
                sleep($this->sleep_interval);
                $call_count++;
            }
        }

        foreach ($results as $endpoint => $data) {
            Cache::put("bulk_{$endpoint}", $data, $this->cacheDuration);
        }

        return $results;
    }

    protected function submitRequest($endpoint)
    {
        $requestUrl = "{$this->buildUrl($endpoint)}/read";
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
                'Accept-Encoding: gzip, deflate',
                "Authorization: Zoho-oauthtoken {$this->zohoOAuth->getAccessToken()}",
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/json',
                'cache-control: no-cache'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    protected function checkRequest($endpoint, $id)
    {
        $requestUrl = "{$this->buildUrl($endpoint)}/read/{$id}";
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
                'Accept-Encoding: gzip, deflate',
                "Authorization: Zoho-oauthtoken {$this->zohoOAuth->getAccessToken()}",
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/json',
                'cache-control: no-cache'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    protected function downloadRequest($endpoint, $id)
    {
        $requestUrl = "{$this->buildUrl($endpoint)}/read/{$id}/result";
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
                'Accept-Encoding: gzip, deflate',
                "Authorization: Zoho-oauthtoken {$this->zohoOAuth->getAccessToken()}",
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/json',
                'cache-control: no-cache'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $this->convertToArray($id, $response);
    }

    private function convertToArray($id, $data)
    {
        $zipFilePath = "temp/{$id}/data.zip";
        Storage::put($zipFilePath, $data);

        $zip = new ZipArchive();
        if ($zip->open(Storage::path($zipFilePath)) === TRUE) {
            $zip->extractTo(Storage::path("temp/{$id}"));
            $zip->close();
        } else {
            throw new \Exception('Failed to extract ZIP file.');
        }

        $csvData = [];
        $files = Storage::files("temp/{$id}");
        foreach ($files as $file) {
            if (!str_contains($file, '.csv')) 
                continue;
            if (!Storage::exists($file)) 
                continue;

            $csvFilePathFull = Storage::path($file);
            if (($handle = fopen($csvFilePathFull, 'r')) !== FALSE) {
                $header = fgetcsv($handle, null, ',');
                while (($row = fgetcsv($handle, null, ',')) !== FALSE) {
                    if (count($header) == count($row) && array_filter($row)) {
                        $csvData[] = array_combine($header, $row);
                    }
                }
                fclose($handle);
            } else {
                throw new \Exception('Failed to open the CSV file.');
            }
        }

        return $csvData;
    }
}
