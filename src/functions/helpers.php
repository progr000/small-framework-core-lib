<?php

use Core\App;

if (!function_exists('replace_vars')) {
    /**
     * @param string $str
     * @param array $replace
     * @return array|string|string[]
     */
    function replace_vars($str, $replace = [])
    {
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

if (!function_exists('config')) {
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config($key, $default = null)
    {
        return App::$config->get($key, $default);
    }
}

if (!function_exists('session')) {
    /**
     * @param string|array|null $key
     * @param mixed $default
     * @return \Core\SessionDriver|mixed|void
     */
    function session($key = null, $default = null)
    {
        if (is_null($key)) {
            return App::$session;
        }

        if (is_array($key)) {
            App::$session->put($key);
            return;
        }

        return App::$session->get($key, $default);
    }
}

if (!function_exists('cookie')) {
    /**
     * @param string|array|null $key
     * @param mixed $default
     * @return \Core\CookieDriver|mixed|void
     */
    function cookie($key = null, $default = null)
    {
        if (is_null($key)) {
            return App::$cookie;
        }

        return App::$cookie->get($key, $default);
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

if (!function_exists('asset')) {
    /**
     * @param string $file
     * @return string
     */
    function asset($file)
    {
        return App::$site_url . "/" . ltrim($file, '/');
    }
}

if (!function_exists('url')) {
    /**
     * @param string $file
     * @return string
     */
    function url($path)
    {
        return App::$site_url . "/" . ltrim($path, '/');
    }
}
