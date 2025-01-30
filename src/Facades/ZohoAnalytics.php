<?php

namespace TwentyTwoEastDigital\Zoho\Facades;

use Illuminate\Support\Facades\Facade;

class ZohoAnalytics extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'zoho.analytics';
    }

    /**
     * Dynamically pass static method calls to the object.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();

        if (method_exists($instance, $method)) {
            return $instance->$method(...$args);
        }

        if (method_exists(app('zoho.oauth'), $method)) {
            return app('zoho.oauth')->$method(...$args);
        }

        if (method_exists(app('zoho.analytics'), $method)) {
            return app('zoho.analytics')->$method(...$args);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}