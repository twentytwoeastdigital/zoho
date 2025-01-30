<?php 

namespace TwentyTwoEastDigital\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TwentyTwoEastDigital\Zoho\Analytics\V20\Read;
use TwentyTwoEastDigital\Zoho\Analytics\V20\Bulk;

class ZohoAnalytics
{
    protected $zohoOAuth;
    protected $endpoint;

    public function __construct(ZohoOAuth $zohoOAuth)
    {
        $this->zohoOAuth = $zohoOAuth;
    }

    public function get($organizationId, $workspaceId, $endpoint, $query = null, $cache_override = false, $cache_duration = 600)
    {
        $request = new Read($this->zohoOAuth, $organizationId, $workspaceId, $endpoint, $cache_override, $cache_duration);
        $response = $request->getRecords($query);
        return $response;
    }

    public function bulk($organizationId, $workspaceId, $endpoint, $cache_override = false, $cache_duration = 600)
    {
        $request = new Bulk($this->zohoOAuth, $organizationId, $workspaceId, $endpoint, $cache_override, $cache_duration);
        $response = $request->request();
        return $response;
    }
}