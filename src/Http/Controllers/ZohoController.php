<?php 

namespace TwentyTwoEastDigital\Zoho\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use TwentyTwoEastDigital\Zoho\Facades\ZohoOAuth;

class ZohoController
{
    public function authorize()
    {
        $authUrl = ZohoOAuth::getAuthUrl(config('zoho.scopes'));
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return response()->json(['error' => 'Authorization code not provided.'], 400);
        }

        $tokens = ZohoOAuth::handleCallback($code);
        return response()->json($tokens);
    }
}
