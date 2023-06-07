<?php

use Core\App;

if (!function_exists('config')) {
    /**
     * @return void
     */
    function config()
    {

    }
}

if (!function_exists('replace_vars')) {
    /**
     * @param string $str
     * @param array $replace
     * @return array|string|string[]
     */
    function replace_vars($str, $replace = []) {
        $s = [];
        $r = [];
        if (sizeof($replace)) {
            foreach ($replace as $k => $v) {
                $s[] = "{%{$k}}";
                $r[] = $v;
            }
        }
        return str_replace($s, $r, $str);
    }
}

if (!function_exists('__')) {
    /**
     * @param string $key
     * @param array $replace
     * @return string
     */
    function __($key, $replace = [])
    {
        return App::$localization->get($key, $replace);
    }
}