<?php

namespace Core\Contracts\CacheDrivers;

use Core\Interfaces\CacheInterface;
use \Memcached;

class memcachedCacheDriver extends CacheInterface
{
    /** @var Memcached */
    private $cache_container;

    /**
     * @param array $conf
     */
    public function __construct(array $conf)
    {
        $this->cache_container = new Memcached();
        foreach ($conf as $connection) {
            $this->cache_container->addServer($connection['server'], $connection['port']);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $seconds if 0 then unlimited
     * @return void
     */
    public function set($key, $value, $seconds = 0)
    {
        $this->cache_container->set($key, $value, $seconds);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return array|mixed|string|null
     */
    public function get($key, $default = null)
    {
        $ret = $this->cache_container->get($key);
        return $ret === false ? $default : $ret;
    }

    /**
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        $this->cache_container->delete($key);
    }

    /**
     * @return void
     */
    public function clearCache()
    {
        $this->cache_container->flush();
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->cache_container->quit();
    }
}