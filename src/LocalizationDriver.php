<?php

namespace Core;

use stdClass;
use Core\Exceptions\IntegrityException;


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
     *
     */
    public function init()
    {
        $file = App::$config->get('localization', []);
        if (isset($file['json-path']) && file_exists($file['json-path'] . "/" .  App::$locale . ".json")) {
            $this->container = json_decode(file_get_contents($file['json-path'] . "/" .  App::$locale . ".json"), true);
        }
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