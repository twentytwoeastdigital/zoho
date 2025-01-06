<?php

namespace TwentyTwoEastDigital\Zoho\Providers;

use Illuminate\Support\ServiceProvider;
use TwentyTwoEastDigital\Zoho\ZohoOAuth;

class ZohoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/zoho.php' => config_path('zoho.php'),
        ], 'config');
        
        $this->loadRoutesFrom(__DIR__.'/../Http/Routes/api.php');
    }

    /**
     * Register the application services.
     *
     * @return void
     */

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/zoho.php', 'zoho');

        $this->app->singleton('zoho.oauth', function ($app) {
            return new \TwentyTwoEastDigital\Zoho\ZohoOAuth();
        });

        $this->app->singleton('zoho.creator', function ($app) { 
            return new \TwentyTwoEastDigital\Zoho\ZohoCreator( 
                $app->make('zoho.oauth'), 
                config('zoho.creator.api_version'), 
                config('zoho.creator.rate_limit_interval'),
                config('zoho.creator.api_base_url'),
                config('zoho.creator.paging.default_per_page'),
                config('zoho.creator.app_owner'),
                config('zoho.creator.app_link_name'),
            ); 
        });

        $this->app->singleton('zoho.crm', function ($app) { 
            return new \TwentyTwoEastDigital\Zoho\ZohoCRM( 
                $app->make('zoho.oauth'), 
                config('zoho.crm.api_version'), 
                config('zoho.crm.rate_limit_interval'),
                config('zoho.crm.api_base_url'),
                config('zoho.crm.paging.default_per_page'),
                config('zoho.crm.app_owner'),
                config('zoho.crm.app_link_name'),
            ); 
        });

        $this->app->singleton('zoho.books', function ($app) { 
            return new \TwentyTwoEastDigital\Zoho\ZohoBooks( 
                $app->make('zoho.oauth'), 
                config('zoho.books.api_version'), 
                config('zoho.books.rate_limit_interval'),
                config('zoho.books.api_base_url'),
                config('zoho.books.paging.default_per_page'),
                config('zoho.books.app_owner'),
                config('zoho.books.app_link_name'),
            ); 
        });
    }

}