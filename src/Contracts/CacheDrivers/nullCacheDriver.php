<?php

namespace Core\Contracts\CacheDrivers;

use Core\Interfaces\CacheInterface;

class nullCacheDriver extends CacheInterface
{
    /**
     * @param array $conf
     */
    public function __construct(array $conf)
    {
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $seconds if 0 then unlimited
     * @return void
     */
    public function set($key, $value, $seconds = 0)
    {
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $default;
    }

    /**
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
    }

    /**
     * @return void
     */
    public function clearCache()
    {
    }
}