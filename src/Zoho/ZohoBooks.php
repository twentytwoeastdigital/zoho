<?php 

namespace TwentyTwoEastDigital\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TwentyTwoEastDigital\Zoho\Books\V30\Create;
use TwentyTwoEastDigital\Zoho\Books\V30\Read;
use TwentyTwoEastDigital\Zoho\Books\V30\Update;

class ZohoBooks
{
    protected $zohoOAuth;
    protected $endpoint;

    public function __construct(ZohoOAuth $zohoOAuth)
    {
        $this->zohoOAuth = $zohoOAuth;
    }

    public function get($organizationId, $endpoint, $query = null, $cache_override = false, $cache_duration = 600)
    {
        $request = new Read($this->zohoOAuth, $organizationId, $endpoint, $cache_override, $cache_duration);
        $response = $request->getRecords($query);
        return $response;
    }

    public function getById($organizationId, $endpoint, $query = null, $cache_override = false, $cache_duration = 600)
    {
        $request = new Read($this->zohoOAuth, $organizationId, $endpoint, $cache_override, $cache_duration);
        $response = $request->getRecordById($query);
        return $response;
    }

    public function create($organizationId, $endpoint, $data)
    {
        $request = new Create($this->zohoOAuth, $organizationId, $endpoint);
        $response = $request->request($data);
        return $response;
    }

    public function update($organizationId, $endpoint, $recordId, $data)
    {
        $request = new Update($this->zohoOAuth, $organizationId, $endpoint);
        $response = $request->request($recordId, $data);
        return $response;
    }
}