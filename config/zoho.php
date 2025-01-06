<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zoho API Client ID
    |--------------------------------------------------------------------------
    |
    | The client ID from your Zoho application. This is used for the OAuth
    | authorization process. You can find this information in your Zoho
    | Developer Console.
    |
    */

    'client_id' => env('TWENTYTWOEASTDIGITAL_ZOHO_CLIENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Zoho API Client Secret
    |--------------------------------------------------------------------------
    |
    | The client secret from your Zoho application. This is used in conjunction
    | with the client ID to authenticate your application with Zoho's OAuth
    | server.
    |
    */

    'client_secret' => env('TWENTYTWOEASTDIGITAL_ZOHO_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Zoho API Redirect URI
    |--------------------------------------------------------------------------
    |
    | The redirect URI specified in your Zoho application. This is the URL that
    | Zoho will redirect to after a user authorizes your application. Ensure
    | that this URI matches the one set up in your Zoho Developer Console.
    |
    */

    'redirect_uri' => env('TWENTYTWOEASTDIGITAL_ZOHO_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Zoho API Scopes
    |--------------------------------------------------------------------------
    |
    | The scopes required by your application. Scopes determine the level of
    | access your application has to the user's data. You can adjust these
    | scopes based on the Zoho services you are integrating with.
    |
    */

    'scopes' => [
        'ZohoCRM.modules.ALL',
        'ZohoCreator.report.READ',
        'ZohoCreator.form.CREATE',
        'ZohoCreator.report.UPDATE',
        'ZohoCreator.bulk.CREATE',
        'ZohoCreator.bulk.READ',
        'ZohoBooks.fullaccess.all',
        'ZohoCreator.report.DELETE',
    ],

    /*
    |--------------------------------------------------------------------------
    | Zoho API Endpoints
    |--------------------------------------------------------------------------
    |
    | The endpoints for the Zoho API services. These can be customized if
    | necessary, but generally, they should point to the standard Zoho API
    | endpoints.
    |
    */

    'auth_url' => 'https://accounts.zoho.com/oauth/v2/auth',
    'token_url' => 'https://accounts.zoho.com/oauth/v2/token',

    /*
    |--------------------------------------------------------------------------
    | Token Storage Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for storing OAuth tokens. You can adjust these settings
    | based on your storage preferences. By default, this package uses Laravel's
    | cache system.
    |
    */

    'token_storage' => [
        'driver' => 'cache',  // Options: 'cache', 'database', etc.
        'cache' => [
            'store' => 'default',  // Cache store to use
        ],
        'database' => [
            'connection' => null,  // Database connection to use
            'table' => 'zoho_oauth_tokens',  // Table to store tokens
        ],
    ],

    'creator' => [
       'api_version' => env('TWENTYTWOEASTDIGITAL_ZOHO_CREATOR_API_VERSION', 'v2.1'), 
       'rate_limit_interval' => env('TWENTYTWOEASTDIGITAL_ZOHO_CREATOR_RATE_LIMIT_INTERVAL', 1.2), 
        'api_base_url' => env('TWENTYTWOEASTDIGITAL_ZOHO_CREATOR_API_BASE_URL', 'https://www.zohoapis.com/creator/'),
       'paging' => [ 
            'default_page' => env('TWENTYTWOEASTDIGITAL_ZOHO_CREATOR_DEFAULT_PAGE', 1), 
            'default_per_page' => env('TWENTYTWOEASTDIGITAL_ZOHO_CREATOR_DEFAULT_PER_PAGE', 1000), 
        ],
        'app_owner' => env('TWENTYTWOEASTDIGITAL_ZOHO_CREATOR_APP_OWNER'),
        'app_link_name' => env('TWENTYTWOEASTDIGITAL_ZOHO_CREATOR_APP_LINK_NAME'),
    ],

];