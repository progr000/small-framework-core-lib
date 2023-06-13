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
        $container = SessionDriver::getInstance('csrf');
        return $container->get('csrf', '');
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
        $container = SessionDriver::getInstance('old-request');
        $old_val = $container->get($key, $default);
        if ($clear_after_access) {
            $container->delete($key);
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
        $container = SessionDriver::getInstance('error-request');
        if ($key) {
            $error = $container->get($key, null);
            if ($clear_after_access) {
                $container->delete($key);
            }
        } else {
            $error = $container->all();
            if ($clear_after_access) {
                $container->clear();
            }
        }
        return $error;
    }
}

const FLASH_INFO = 'info';
const FLASH_SUCCESS = 'success';
const FLASH_WARNING = 'warning';
const FLASH_ERROR = 'error';
if (!function_exists('get_flash_messages')) {
    /**
     * @param bool $clear_after_access
     * @return mixed
     */
    function get_flash_messages($clear_after_access = true)
    {
        $container = SessionDriver::getInstance('flash-messages');
        $messages = $container->all();
        if ($clear_after_access) {
            $container->clear();
        }
        return $messages;
    }
}

if (!function_exists('set_flash_messages')) {
    /**
     * @param string $message
     * @param string $type
     * @return true
     */
    function set_flash_messages($message, $type = FLASH_ERROR, $ttl = 0, $id = null)
    {
        $container = SessionDriver::getInstance('flash-messages');
        $key = md5($message . $type);
        $container->put([$key => [
            'message' => $message,
            'type' => $type,
            'ttl' => $ttl,
            'id' => isset($id) ? $id : $key
        ]]);
        return true;
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
    function css_stack(array $css, $www_dir = null)
    {
        $str = "";
        foreach ($css as $item) {
            if (strrpos($item, '<style') !== false) {
                $str .= $item . "\n";
            } else {
                $filemtime = file_exists($www_dir . "/" . $item)
                    ? filemtime($www_dir . "/" . $item)
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
    function js_stack(array $js, $www_dir = null)
    {
        $str = "";
        foreach ($js as $item) {
            if (strrpos($item, '<script') !== false) {
                $str .= $item . "\n";
            } else {
                $filemtime = file_exists($www_dir . "/" . $item)
                    ? filemtime($www_dir . "/" . $item)
                    : time();
                $str .= '<script src="' . $item . (App::$config->get('IS_DEBUG', false) ? '?v=' . $filemtime : '') . '"></script>' . "\n";
            }
        }
        return $str;
    }
}
