<?php

namespace TwentyTwoEastDigital\Zoho\Facades;

use Illuminate\Support\Facades\Facade;

class ZohoCreator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'zoho.creator';
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

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}
