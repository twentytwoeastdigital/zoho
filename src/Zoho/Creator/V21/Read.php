<?php

namespace TwentyTwoEastDigital\Zoho\Creator\V21;

use TwentyTwoEastDigital\Zoho\ZohoOAuth;
use Illuminate\Support\Facades\Cache;

class Read
{
    protected $zohoOAuth;
    protected $apiVersion;
    protected $rateLimitInterval;
    protected static $lastRequestTime;
    protected $apiBaseUrl;
    protected $defaultPerPage;
    protected $appOwner;
    protected $appLinkName;
    protected $endpoint;
    protected $criteria;
    protected $cache_override;
    protected $cache_duration;
    
    public function __construct(ZohoOAuth $zohoOAuth, $endpoint, $criteria = null, $cache_override = false, $cache_duration = 600)
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
        if (self::$lastRequestTime === null) 
        { 
            self::$lastRequestTime = microtime(true); 
        }
        $this->criteria = $criteria;
        $this->cache_override = $cache_override;
        $this->cache_duration = $cache_duration;
    }

    private function buildUrl()
    {
        return "{$this->apiBaseUrl}{$this->apiVersion}/data/{$this->appOwner}/{$this->appLinkName}/report/{$this->endpoint}";
    }

    protected function throttle() 
    { 
        $currentTime = microtime(true); 
        $timeSinceLastRequest = $currentTime - self::$lastRequestTime; 
        $sleepTime = 0;
        if ($timeSinceLastRequest < $this->rateLimitInterval) 
        { 
            $sleepTime = ($this->rateLimitInterval - $timeSinceLastRequest) * 1e6; 
            $sleepTime = (int)$sleepTime;
            usleep($sleepTime); 
        }
        self::$lastRequestTime = microtime(true); 
    }

    public function request()
    {
        $cacheKey = "read_{$this->endpoint}_{$this->criteria}";

        if (!$this->cache_override && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $requestUrl = $this->buildUrl();
        $data_return = array();
        $increment = $this->defaultPerPage;
        $record_cursor = null;
        $code = 3000;

        $loop_count = 0;
        $max_loops = 100;

        while ($code == 3000 && $record_cursor !== false && $loop_count < $max_loops) {
            $url  = $requestUrl.'?max_records=' . $increment; 
            if($this->criteria) {
                $url .= '&criteria=' . urlencode($this->criteria);
            }
            $this->throttle();

            $header_array = array(
                'Accept: */*',
                'Accept-Encoding: gzip, deflate',
                "Authorization: Zoho-oauthtoken {$this->zohoOAuth->getAccessToken()}",
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/json',
            );

            if($record_cursor) {
                $header_array[] = 'record_cursor: ' . $record_cursor;
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'GET',
                CURLOPT_HTTPHEADER     => $header_array,
            ));
            curl_setopt($curl, CURLOPT_HEADER, true);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            $code = 0;
            if ($err) { 
                echo 'cURL Error: ' . curl_error($curl); 
            } else { 
                $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE); 
                $header = substr($response, 0, $header_size); 
                $body = substr($response, $header_size);
                
                $response_json = json_decode($body);
                if (!empty($response_json->code)) {
                    $code = $response_json->code;
                    if ($response_json->code == 3000) {
                        $data_return = array_merge($data_return, $response_json->data);
                    }
                } 
                $record_cursor = false;
                $header_array = explode("\n", $header);
                foreach ($header_array as $header_value) {
                    if (strpos($header_value, 'record_cursor') !== false) {
                        $record_cursor = trim(str_replace('record_cursor:', '', $header_value));
                    }
                }
            }
            $loop_count++;
            if (ob_get_level() > 0) {
                ob_flush();
            }
        }

        $data_return = json_decode(json_encode($data_return), true);

        Cache::put($cacheKey, $data_return, $this->cache_duration);
        return $data_return;
    }

    public function requestById()
    {
        $cacheKey = "read_{$this->endpoint}_{$this->criteria}";

        if (!$this->cache_override && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $url = $this->buildUrl() . '/' . $this->criteria;
        $data_return = null;            
        $this->throttle();

        $header_array = array(
            'Accept: */*',
            'Accept-Encoding: gzip, deflate',
            "Authorization: Zoho-oauthtoken {$this->zohoOAuth->getAccessToken()}",
            'Cache-Control: no-cache',
            'Connection: keep-alive',
            'Content-Type: application/json',
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => $header_array,
        ));
        curl_setopt($curl, CURLOPT_HEADER, true);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) { 
            echo 'cURL Error: ' . curl_error($curl); 
        } else { 
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE); 
            $header = substr($response, 0, $header_size); 
            $body = substr($response, $header_size);
            
            $response_json = json_decode($body);
            if (!empty($response_json->code)) {
                $code = $response_json->code;
                if ($response_json->code == 3000) {
                    $data_return = $response_json->data;
                }
            } 
        }

        if (ob_get_level() > 0) {
            ob_flush();
        }

        if($data_return) {
            Cache::put($cacheKey, $data_return, $this->cache_duration);
        }

        return json_decode(json_encode($data_return), true);
    }
}