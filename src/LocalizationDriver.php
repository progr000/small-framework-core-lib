<?php

namespace Core;

use Maksym\Config\ConfigException;

class LocalizationDriver
{
    /** @var self */
    private static $instance;
    /** @var array */
    private $container;

    /**
     * @return LocalizationDriver
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     *
     */
    private function __construct()
    {
    }

    /**
     * @return bool
     * @throws ConfigException
     */
    public function init()
    {
        $file = config('localization', []);
        if (!empty($file['json-path']) && file_exists($file['json-path'] . "/" .  App::$locale . ".json")) {
            try {
                $this->container = json_decode(file_get_contents($file['json-path'] . "/" .  App::$locale . ".json"), true);
                return !empty($this->container);
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * @param string $key
     * @param array $replace
     * @return string
     */
    public function get($key, array $replace = [])
    {
        return replace_vars(
            (isset($this->container[$key]) ? $this->container[$key] : $key),
            $replace
        );
    }
}