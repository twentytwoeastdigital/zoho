<?php

namespace TwentyTwoEastDigital\Zoho\Books\V30;

use Illuminate\Support\Facades\Cache;
use TwentyTwoEastDigital\Zoho\ZohoOAuth;

class Delete
{
    protected ZohoOAuth $zohoOAuth;
    protected string $apiVersion;
    protected float $rateLimitInterval;
    protected static ?float $lastRequestTime = null;
    protected string $apiBaseUrl;
    protected string $organizationId;
    protected string $module;

    public function __construct(ZohoOAuth $zohoOAuth, string $organizationId, string $module)
    {
        $this->zohoOAuth         = $zohoOAuth;
        $this->organizationId    = $organizationId;
        $this->module            = $module;
        $this->apiVersion        = 'v3';
        $this->rateLimitInterval = (float) config('zoho.books.rate_limit_interval', 1.0);
        self::$lastRequestTime   = self::$lastRequestTime ?? microtime(true);
        $this->apiBaseUrl        = 'https://www.zohoapis.com/books/';
    }

    private function buildUrl(string $recordId): string
    {
        return "{$this->apiBaseUrl}{$this->apiVersion}/{$this->module}/{$recordId}?organization_id={$this->organizationId}";

    }

    protected function throttle(): void
    {
        $currentTime         = microtime(true);
        $elapsed             = $currentTime - self::$lastRequestTime;
        $interval            = $this->rateLimitInterval;
        if ($elapsed < $interval) {
            // sleep the remaining interval (in microseconds)
            usleep((int)(($interval - $elapsed) * 1e6));
        }
        self::$lastRequestTime = microtime(true);
    }

    /**
     * Delete a record in the given module.
     *
     * @param  string  $recordId
     * @return array|string[]|mixed
     */
    public function request(string $recordId)
    {
        $this->throttle();
        $url = $this->buildUrl($recordId);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => [
                'Authorization: Zoho-oauthtoken ' . $this->zohoOAuth->getAccessToken(),
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['success' => false, 'error' => $err];
        }

        return json_decode($response, true);
    }
}