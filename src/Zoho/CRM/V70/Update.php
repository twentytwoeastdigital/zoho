<?php

namespace TwentyTwoEastDigital\Zoho\CRM\V70;

use Illuminate\Support\Facades\Log;
use TwentyTwoEastDigital\Zoho\ZohoOAuth;

class Update
{
    protected $zohoOAuth;
    protected $apiVersion;
    protected $rateLimitInterval;
    protected static $lastRequestTime;
    protected $apiBaseUrl;
    protected $module;
    protected $recordId;
    protected $data;

    /**
     * @param  ZohoOAuth  $zohoOAuth  The Zoho OAuth wrapper.
     * @param  string     $module     The Zoho CRM module name (e.g. Leads, Contacts).
     * @param  string     $recordId   The ID of the record to be updated.
     * @param  array      $data       The body you want to send to Zoho (usually with fields to update).
     */
    public function __construct(ZohoOAuth $zohoOAuth, $module, $recordId, $data = [])
    {
        $this->zohoOAuth         = $zohoOAuth;
        $this->module            = $module;
        $this->recordId          = $recordId;
        $this->apiVersion        = config('zoho.crm.api_version', 'v7');
        $this->rateLimitInterval = config('zoho.crm.rate_limit_interval', 1);
        self::$lastRequestTime   = self::$lastRequestTime ?? microtime(true);
        $this->apiBaseUrl        = config('zoho.crm.api_base_url', 'https://www.zohoapis.com/crm/');
        $this->data              = $data;
    }

    /**
     * Build the URL for the update endpoint:
     * e.g. https://www.zohoapis.com/crm/v7/{module}/{recordId}
     */
    private function buildUrl()
    {
        return "{$this->apiBaseUrl}{$this->apiVersion}/{$this->module}/{$this->recordId}";
    }

    /**
     * Throttle requests so they don’t exceed Zoho’s recommended rate.
     */
    protected function throttle()
    {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - self::$lastRequestTime;
        
        if ($timeSinceLastRequest < $this->rateLimitInterval) {
            usleep((int)(($this->rateLimitInterval - $timeSinceLastRequest) * 1e6));
        }
        
        self::$lastRequestTime = microtime(true);
    }

    /**
     * Perform the update request to Zoho CRM.
     * @return mixed The record ID on success, or an array with error information on failure.
     */
    public function request()
    {
        $this->throttle();
        $requestUrl = $this->buildUrl();

        $data = [
            'data' => [
                $this->data,
            ]
        ];

        Log::info($this->data);
        Log::info($data);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 240,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'PUT',    // PUT for updates
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Zoho-oauthtoken ' . $this->zohoOAuth->getAccessToken(),
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            // cURL-level error (e.g., couldn't connect)
            return ['error' => $err];
        }

        $response = json_decode($response, true);
        // Check Zoho's response for a success code
        if (isset($response['data'][0]['code']) && $response['data'][0]['code'] === 'SUCCESS') {
            return $response['data'][0]['details']['id'] ?? null;
        }

        // Fallback: return whatever we got from Zoho (or a basic error if unparseable)
        return $response ?? ['error' => 'Invalid response from Zoho CRM'];
    }
}
