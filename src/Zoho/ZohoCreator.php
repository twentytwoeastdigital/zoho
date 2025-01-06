<?php

namespace TwentyTwoEastDigital\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TwentyTwoEastDigital\Zoho\Creator\V21\Read;
use TwentyTwoEastDigital\Zoho\Creator\V21\Bulk;
use TwentyTwoEastDigital\Zoho\Creator\V21\Create;
use TwentyTwoEastDigital\Zoho\Creator\V21\Delete;
use TwentyTwoEastDigital\Zoho\Creator\V21\Update;

class ZohoCreator
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
        $response = $request->request();
        return $response;
    }

    public function getById($endpoint, $recordId, $cache_override = false, $cache_duration = 600)
    {
        $request = new Read($this->zohoOAuth, $endpoint, $recordId, $cache_override, $cache_duration);
        $response = $request->requestById();
        return $response;
    }

    public function bulk($endpoint, $cache_override = false, $cache_duration = 600)
    {
        $request = new Bulk($this->zohoOAuth, $endpoint, $cache_override, $cache_duration);
        $response = $request->request();
        return $response;
    }

    public function create($endpoint, $data)
    {
        $request = new Create($this->zohoOAuth, $endpoint, $data);
        $response = $request->request();
        return $response;
    }

    public function update($endpoint, $data, $recordId)
    {
        $request = new Update($this->zohoOAuth, $endpoint, $data, $recordId);
        $response = $request->request();
        return $response;
    }

    public function delete($endpoint, $recordId)
    {
        $request = new Delete($this->zohoOAuth, $endpoint);
        $response = $request->request($recordId);
        return $response;
    }
}
