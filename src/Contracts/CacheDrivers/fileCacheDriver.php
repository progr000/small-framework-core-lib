<?php

namespace Core\Contracts\CacheDrivers;

use Core\Exceptions\ConfigException;
use Core\Interfaces\CacheInterface;

class fileCacheDriver extends CacheInterface
{
    /** @var string */
    private $cache_container;

    /**
     * @param array $conf
     * @throws ConfigException
     */
    public function __construct(array $conf)
    {
        $this->cache_container = $conf['store_dir'];
        @mkdir($this->cache_container, 0755, true);
        @chmod($this->cache_container, 0755);
        if (!file_exists($this->cache_container) || !is_dir($this->cache_container)) {
            throw new ConfigException('Directory for cache-driver not exist');
        }
        if (!is_writable($this->cache_container)) {
            throw new ConfigException('Directory for cache-driver not writeable');
        }
    }

    /**
     * @param string $key
     * @return string
     */
    private function filenameByKey($key)
    {
        return $this->cache_container . "/cache-" . md5($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $seconds if 0 then unlimited
     * @return void
     */
    public function set($key, $value, $seconds = 0)
    {
        $file = $this->filenameByKey($key);
        @unlink($file);
        if ($seconds) {
            $die_after = time() + $seconds;
        } else {
            $die_after = false;
        }
        file_put_contents($file, serialize(['value' => $value, 'die_after' => $die_after]));
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $file = $this->filenameByKey($key);
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
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
        }

        return $default;
    }

    /**
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        @unlink($this->filenameByKey($key));
    }

    /**
     * @return void
     */
    public function clearCache()
    {
        $files = array_diff(scandir($this->cache_container), ['.','..']);
        foreach ($files as $file) {
            !is_dir("{$this->cache_container}/{$file}") && @unlink("{$this->cache_container}/{$file}");
        }
    }
}