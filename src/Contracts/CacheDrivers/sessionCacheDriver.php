<?php

namespace Core\Contracts\CacheDrivers;

use Core\Interfaces\CacheInterface;
use Core\SessionDriver;

class sessionCacheDriver extends CacheInterface
{
    /** @var array */
    private $cache_container;

    /**
     * @param array $conf
     */
    public function __construct(array $conf)
    {
        $this->cache_container = SessionDriver::getInstance('cache-container');
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $seconds if 0 then unlimited
     * @return void
     */
    public function set($key, $value, $seconds = 0)
    {
        if ($seconds) {
            $die_after = time() + $seconds;
        } else {
            $die_after = false;
        }
        $this->cache_container->set($key, ['value' => $value, 'die_after' => $die_after]);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $data = $this->cache_container->get($key, $default);
        if ($data) {
            if ($data['die_after']) {
                if ($data['die_after'] >= time()) {
                    return $data['value'];
                } else {
                    $this->delete($key);
                }
            } else {
                return $data['value'];
            }
        }

        return $default;
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
        $this->cache_container->clear();
    }
}