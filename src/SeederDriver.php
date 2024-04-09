<?php

namespace Core;

abstract class SeederDriver
{
    /**
     * @param array $classes
     * @return bool
     */
    public function call(array $classes)
    {
        $ret = true;
        foreach ($classes as $class) {
            $executeSeeder = LogDriver::executingMessage("Execute seeder [warn]{$class}[/warn]", 0);
            if (class_exists($class)) {
                if (method_exists($class, 'run')) {
                    $seeder = new $class();
                    if ($seeder->run()) {
                        $executeSeeder->showSuccess();
                    } else {
                        $executeSeeder->showError();
                        LogDriver::error("This seeder class '{$class}' return `false` after executing.", 2);
                        $ret = false;
                    }
                } else {
                    $executeSeeder->showError();
                    LogDriver::error("The '{$class}' class is incorrect, it doesn't have a run() method.", 2);
                    $ret = false;
                }
            } else {
                $executeSeeder->showError();
                LogDriver::error("Class '{$class}' not found", 2);
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * @return bool
     */
    abstract public function run();
}