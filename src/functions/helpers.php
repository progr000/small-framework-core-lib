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
                $s[] = ":{$k}";
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

if (!function_exists('minimize')) {
    /**
     * @param string $str
     * @return string
     */
    function minimize($str)
    {
        if (!config('minimize-plain-css-js', false)) {
            return $str;
        }
        return str_replace(
            ["\n", "\r\n", ": ", "; ", "} ", "{ ", " }", " {", " =", "= ", ", ", " ,"],
            ["", "", ":", ";", "}", "{", "}", "{", "=", "=", ",", ","],
            replaceMultiSpacesAndNewLine($str)
        );
    }
}

if (!function_exists('url')) {
    /**
     * @param string $path
     * @param array|string $params
     * @return string
     */
    function url($path, $params = '')
    {
        $tmp = [];
        if (is_array($params) && sizeof($params)) {
            foreach ($params as $k => $v) {
                $tmp[] = "{$k}=" . urlencode($v);
            }
            $params = implode('&', $tmp);
        }
        $params = ltrim(trim($params), '?');

        return App::$site_url . "/" . ltrim($path, '/') . ($params ? "?" . $params : "");
    }
}

if (!function_exists('replaceMultiSpacesAndNewLine')) {
    /**
     * @param string $str
     * @return string
     */
    function replaceMultiSpacesAndNewLine($str, $to = " ")
    {
        return preg_replace("/[\s]+/", $to, $str);
    }
}

if (!function_exists('http')) {
    /**
     * @return \Core\WgetDriver
     */
    function http()
    {
        return Core\WgetDriver::init();
    }
}

if (!function_exists('httpClient')) {
    /**
     * @return \Core\WgetDriver
     */
    function httpClient()
    {
        return Core\WgetDriver::init();
    }
}

if (!function_exists('size_format')) {
    /**
     * @param integer $bytes
     * @param integer $decimal_digits
     * @param string $force ('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb')
     * @param string $space_between
     * @param bool $no_power
     * @return string
     */
    function size_format($bytes, $decimal_digits = 2, $space_between = ' ', $force = null, $no_power = false)
    {
        $bytes = max(0, round($bytes));
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $power = array_search($force, $units);
        if ($power === false) {
            $power = ($bytes > 0) ? floor(log($bytes, 1024)) : 0;
        }
        if ($no_power) {
            return number_format(round($bytes / pow(1024, $power), 2), $decimal_digits, '.', '');
        } else {
            return number_format(round($bytes / pow(1024, $power), 2), $decimal_digits, '.', '') . $space_between . $units[$power];
        }
    }
}