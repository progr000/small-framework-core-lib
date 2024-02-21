<?php

namespace Core;

abstract class SeederDriver
{
    public function call(array $classes)
    {
        foreach ($classes as $class) {
            if (class_exists($class)) {
                if (method_exists($class, 'run')) {
                    $seeder = new $class();
                    $seeder->run();
                } else {
                    LogDriver::error("The '{$class}' class is incorrect, it doesn't have a run() method.");
                }
            } else {
                LogDriver::error("Class '{$class}' not found");
            }
        }
    }

    abstract public function run();
}