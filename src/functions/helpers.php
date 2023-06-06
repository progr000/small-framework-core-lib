<?php
if (! function_exists('config')) {
    /**
     * @return void
     */
    function config()
    {

    }
}

if (! function_exists('__')) {
    /**
     * @param string $key
     * @param array $replace
     * @return string
     */
    function __($key, $replace = [])
    {
        return $key;
    }
}