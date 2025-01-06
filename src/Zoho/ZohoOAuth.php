<?php

namespace TwentyTwoEastDigital\Zoho;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ZohoOAuth
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $authUrl = 'https://accounts.zoho.com/oauth/v2/auth';
    protected $tokenUrl = 'https://accounts.zoho.com/oauth/v2/token';

    public function __construct()
    {
        $this->clientId = config('zoho.client_id');
        $this->clientSecret = config('zoho.client_secret');
        $this->redirectUri = config('zoho.redirect_uri');
    }

    /**
     * Get the authorization URL
     *
     * @param string $scope
     * @param string $state
     * @return string
     */
    public function getAuthUrl($scope, $state = null)
    {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(',', (array) $scope),
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return $this->authUrl . '?' . http_build_query($params);
    }

    /**
     * Handle the OAuth callback
     *
     * @param string $code
     * @return array
     */
    public function handleCallback($code)
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->storeTokens($data);
            return $data;
        }

        return [];
    }

    /**
     * Refresh the access token
     *
     * @return array
     */
    public function refreshToken()
    {
        $refreshToken = Cache::get('zoho_refresh_token');

        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->storeTokens($data);
            return $data;
        }

        return [];
    }

    /**
     * Store the tokens in cache
     *
     * @param array $data
     */
    protected function storeTokens($data)
    {
        if(!isset($data['access_token']) || !isset($data['expires_in']))
        {
            return;
        }

        Cache::put('zoho_access_token', $data['access_token'], $data['expires_in']);
        if(isset($data['refresh_token']))
            Cache::put('zoho_refresh_token', $data['refresh_token']);
    }

    /**
     * Get the access token from cache
     *
     * @return string
     */
    public function getAccessToken()
    {
        if (!Cache::has('zoho_access_token')) {
            $this->refreshToken();
        }
        
        return Cache::get('zoho_access_token');
    }
}
