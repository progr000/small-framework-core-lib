<?php

namespace Core;

class CookieDriver
{
    /** @var self */
    private static $instance;
    /** @var array */
    private $enc_key;

    /**
     * @return CookieDriver
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param string $val
     * @return false|mixed|string
     */
    private function encrypt($val)
    {
        if (function_exists('openssl_encrypt')) {
            return openssl_encrypt($val, "AES-128-ECB", $this->enc_key);
        } else {
            return $val;
        }
    }

    /**
     * @param string $val
     * @return false|mixed|string
     */
    private function decrypt($val)
    {
        if (function_exists('openssl_decrypt')) {
            return openssl_decrypt($val, "AES-128-ECB", $this->enc_key);
        } else {
            return $val;
        }
    }

    /**
     *
     */
    private function __construct()
    {
        $this->enc_key = config('cookie-enc-key', 'cookie-enc-key-value');
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (in_array($key, ['PHPSESSID'])) {
            return $_COOKIE[$key];
        }
        if (isset($_COOKIE[$key])) {
            return unserialize($this->decrypt($_COOKIE[$key]));
        }

        return $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param int $ttl_seconds
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return bool
     */
    public function make($name, $value, $ttl_seconds = 0, $path = "/", $domain = "", $secure = false, $httpOnly = true)
    {
        return setcookie(
            $name,
            $this->encrypt(serialize($value)),
            ($ttl_seconds > 0) ? time() + $ttl_seconds : 0,
            $path,
            $domain,
            $secure,
            $httpOnly
        );
//        App::$response->setCookie($name, $this->encrypt(serialize($value)), [
//            'expire' => ($ttl_seconds > 0) ? time() + $ttl_seconds : 0,
//            'path' => $path,
//            'domain' => $domain,
//            'secure' => $secure,
//            'httponly' => $httpOnly
//        ]);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param int $ttl_seconds
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return bool
     */
    public function set($name, $value, $ttl_seconds = 0, $path = "/", $domain = "", $secure = false, $httpOnly = true)
    {
        return $this->make($name, $value, $ttl_seconds, $path, $domain, $secure, $httpOnly);
    }

    /**
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return bool
     */
    public function delete($name, $path = "/", $domain = "", $secure = false, $httpOnly = true)
    {
        unset($_COOKIE[$name]);
        return setcookie(
            $name,
            "",
            time() - 3600,
            $path,
            $domain,
            $secure,
            $httpOnly
        );
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset($_COOKIE[$key]);
    }

    /**
     * @return array|null
     */
    public function all()
    {
        $all = [];
        foreach ($_COOKIE as $key => $value) {
            $all[$key] = $this->get($key);
        }
        return $all;
    }
}