<?php

namespace TwentyTwoEastDigital\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TwentyTwoEastDigital\Zoho\CRM\V70\Read;

class ZohoCRM
{
    protected $zohoOAuth;
    protected $endpoint;

    public function __construct(ZohoOAuth $zohoOAuth)
    {
        $this->zohoOAuth = $zohoOAuth;
    }

    public function get($endpoint, $query = null, $cache_override = false, $cache_duration = 600)
    {
        $request = new Read($this->zohoOAuth, $endpoint, $query, $cache_override, $cache_duration);
        $response = $request->getRecords();
        return $response;
    }

    public function getById($endpoint, $recordId)
    {
        $request = new Read($this->zohoOAuth, $endpoint);
        $response = $request->getRecordById($recordId);
        return $response;
    }
}
