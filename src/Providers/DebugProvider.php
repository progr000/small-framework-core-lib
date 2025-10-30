<?php

namespace Core\Providers;

use Core\App;
use Maksym\Config\ConfigException;

class DebugProvider
{
    /**
     * @throws ConfigException
     */
    public function register()
    {
        $className = App::$config->get('DebugDriver', null);
        if (is_null($className)) {
            return null;
        } elseif (class_exists($className)) {
            return $className::getInstance();
        } else {
            throw new ConfigException('DebugPanel driver not exist');
        }
    }
}