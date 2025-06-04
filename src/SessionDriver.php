<?php

namespace Core;

class SessionDriver
{
    /** @var self */
    private static $instance = [];
    /** @var array */
    private $container;

    /**
     * @param string $container
     * @return SessionDriver
     */
    public static function getInstance($container)
    {
        if (!isset(self::$instance[$container])) {
            self::$instance[$container] = new self($container);
        }
        return self::$instance[$container];
    }

    /**
     * @param string $container
     */
    private function __construct($container)
    {
        $this->container = $container;
    }

    /**
     *
     */
    public function init()
    {
        session_start();
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (isset($_SESSION[$this->container][$key])) {
            return $_SESSION[$this->container][$key];
        }

        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set($key, $value)
    {
        $_SESSION[$this->container][$key] = $value;
        return isset($_SESSION[$this->container][$key]);
    }

    /**
     * @param array $data
     * @return bool
     */
    public function put($data)
    {
        foreach ($data as $k => $v) {
            $_SESSION[$this->container][$k] = $v;
        }
        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        unset($_SESSION[$this->container][$key]);
        return !isset($_SESSION[$this->container][$key]);
    }

    /**
     * Clear whole session-container
     * @return void
     */
    public function clear()
    {
        unset($_SESSION[$this->container]);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset($_SESSION[$this->container][$key]);
    }

    /**
     * @return array
     */
    public function all()
    {
        return isset($_SESSION[$this->container])
            ? $_SESSION[$this->container]
            : [];
    }
}