<?php

namespace Core\Interfaces;

abstract class CacheInterface
{
    /**
     * @param array $conf
     */
    abstract public function __construct(array $conf);

    /**
     * @param string $key
     * @param mixed $value
     * @param int $seconds if 0 then unlimited
     * @return void
     */
    abstract public function set($key, $value, $seconds = 0);

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    abstract public function get($key, $default = null);

    /**
     * @param string $key
     * @return void
     */
    abstract public function delete($key);

    /**
     * @return void
     */
    abstract public function clearCache();

    /**
     * if cache for closure exist will return this cache,
     * else execute closure and put result of execution into cache
     * and return this result
     * @param Callable $closure
     * @param int $seconds
     * @return mixed
     */
    public function cacheResult($closure, $seconds = 0)
    {
        /* generate pseudo_key */
        try {
            $rf = new \ReflectionFunction($closure);
            $pseudo_key = md5(
                $rf->getFileName() .
                $rf->getStartLine() .
                $rf->getEndLine() .
                (method_exists($rf, 'getStaticVariables') ? json_encode($rf->getStaticVariables()) : '') .
                (method_exists($rf, 'getAttributes') ? json_encode($rf->getAttributes()) : '') .
                (method_exists($rf, 'getParameters') ? json_encode($rf->getParameters()) : '') .
                (method_exists($rf, 'getClosureUsedVariables') ? json_encode($rf->getClosureUsedVariables()) : '') .
                $rf->getNumberOfParameters() .
                $rf->getNumberOfRequiredParameters()
            );
        } catch (\ReflectionException $e) {
            $pseudo_key = md5(uniqid());
        }

        /* try to get data by $pseudo_key and return if success*/
        $ret = $this->get($pseudo_key);
        if ($ret) {
            return $ret;
        }

        /* set data for $pseudo_key from execution $closure */
        $data = $closure();
        $this->set($pseudo_key, $data, $seconds);

        /* return $data received from closure */
        return $data;
    }
}