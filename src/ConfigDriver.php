<?php

namespace Core;

use stdClass;
use Core\Exceptions\ConfigException;
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
            throw new IntegrityException("Configuration file is missing: '" . ($config_dir . '/main.php') . "'");
        }

        $config = require $config_dir . '/main.php';
        if (file_exists($config_dir . '/main-local.php')) {
            $config = array_merge(
                $config,
                require $config_dir . '/main-local.php'
            );
        } elseif (defined('IS_DEBUG') && IS_DEBUG) {
            LogDriver::warning("You can create local config file '". $config_dir . '/main-local.php' ."', with some params, which can override params from config/main.php", 0);
        }

        $this->container = new stdClass();
        foreach ($config as $k=>$v) {
            $this->container->$k = $v;
        }
    }

    /**
     * Return value for param name
     * @param string $param
     * @return mixed
     * @throws ConfigException
     */
    public function get($param)
    {
        if (!property_exists($this->container, $param)) {
            if (defined('IS_DEBUG') && IS_DEBUG) {
                throw new ConfigException("Param '{$param}' missed in the config '" . realpath(__DIR__ . '/../config/main.php') . "'", 500);
            } else {
                return null;
            }
        }
        return $this->container->$param;
    }
}