<?php

namespace Core\Providers;

use Core\App;
use Core\Exceptions\ConfigException;

class CacheProvider
{
    /**
     * @throws ConfigException
     */
    public function register()
    {
        $driver = App::$config->get('caching->driver', 'session');
        $className = "Core\\Contracts\\CacheDrivers\\{$driver}CacheDriver";
        if (class_exists($className)) {
            $cacheDriverCredentials = App::$config->get("caching->{$driver}", []);
            return new $className($cacheDriverCredentials);
        } else {
            throw new ConfigException('Cache driver not exist');
        }
    }
}