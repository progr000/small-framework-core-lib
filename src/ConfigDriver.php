<?php

namespace Core;

use stdClass;
use Core\Exceptions\IntegrityException;

class ConfigDriver
{
    /** @var self */
    private static $instance;
    /** @var stdClass */
    private $container;

    /**
     * @return ConfigDriver
     * @throws IntegrityException
     */
    public static function getInstance($config_dir)
    {
        if (self::$instance === null) {
            self::$instance = new self($config_dir);
        }
        return self::$instance;
    }

    /**
     * Constructor, load data into itself from config
     * @throws IntegrityException
     */
    private function __construct($config_dir)
    {
        if (!file_exists($config_dir . '/main.php')) {
            throw new IntegrityException("Configuration file is missing: '" . ($config_dir . '/main.php') . "'", 500);
        }

        $config = require $config_dir . '/main.php';
        $this->container = new stdClass();
        foreach ($config as $k=>$v) {
            $this->container->$k = $v;
        }
    }

    /**
     * Return value for param name
     * @param string $param
     * @param mixed $default
     * @return mixed
     */
    public function get($param, $default = null)
    {
        $test = explode('->', $param);
        if (isset($test[1])) {
            $key = $test[1];
            $param = $test[0];
        }
        if (!property_exists($this->container, $param)) {
            return $default;
        }
        if (isset($key)) {
            return isset($this->container->{$param}[$key])
                ? $this->container->{$param}[$key]
                : $default;
        } else {
            return $this->container->$param;
        }
    }

    /**
     * Check exist or not param in config
     * @param string $param
     * @return bool
     */
    public function exist($param)
    {
        return property_exists($this->container, $param);
    }

    /**
     * Set some params into config container
     * @param string|array $param
     * @param mixed|null $value
     * @return true
     */
    public function set($param, $value = null)
    {
        if (gettype($param) === 'array') {
            foreach ($param as $k => $v) {
                $this->container->$k = $v;
            }
        } else {
            $this->container->$param = $value;
        }
        return true;
    }
}