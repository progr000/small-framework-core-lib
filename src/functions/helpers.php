<?php

use Core\App;
use Core\SessionDriver;

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

if (!function_exists('csrf')) {
    /**
     * @return string
     */
    function csrf()
    {
        return App::$session->get('csrf', '');
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

if (!function_exists('old')) {
    /**
     * @param string $key
     * @param mixed $default
     * @param bool $clear_after_access
     * @return mixed
     */
    function old($key, $default = '', $clear_after_access = true)
    {
        $old = SessionDriver::getInstance('old-request');
        $old_val = $old->get($key, $default);
        if ($clear_after_access) {
            $old->delete($key);
        }
        return $old_val;
    }
}

if (!function_exists('request_errors')) {
    /**
     * @param string|null $key
     * @param bool $clear_after_access
     * @return mixed
     */
    function request_errors($key = null, $clear_after_access = true)
    {
        $errors = SessionDriver::getInstance('error-request');
        if ($key) {
            $error_val = $errors->get($key, null);
            if ($clear_after_access) {
                $errors->delete($key);
            }
        } else {
            $error_val = $errors->all();
            if ($clear_after_access) {
                $errors->clear();
            }
        }
        return $error_val;
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

if (!function_exists('css_stack')) {
    /**
     * @param array $css
     * @return string
     */
    function css_stack(array $css)
    {
        $str = "";
        foreach ($css as $item) {
            if (strrpos($item, '<style') !== false) {
                $str .= $item . "\n";
            } else {
                $filemtime = file_exists($item)
                    ? filemtime($item)
                    : time();
                $str .= '<link href="' . $item . (App::$config->get('IS_DEBUG', false) ? '?v=' . $filemtime : '') . '" rel="stylesheet">' . "\n";
            }
        }
        return $str;
    }
}

if (!function_exists('js_stack')) {
    /**
     * @param array $js
     * @return string
     */
    function js_stack(array $js)
    {
        $str = "";
        foreach ($js as $item) {
            if (strrpos($item, '<script') !== false) {
                $str .= $item . "\n";
            } else {
                $filemtime = file_exists($item)
                    ? filemtime($item)
                    : time();
                $str .= '<script src="' . $item . (App::$config->get('IS_DEBUG', false) ? '?v=' . $filemtime : '') . '"></script>' . "\n";
            }
        }
        return $str;
    }
}