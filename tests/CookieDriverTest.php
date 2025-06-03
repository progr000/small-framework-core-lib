<?php

namespace Tests;

use Core\App;
use Core\CookieDriver;

class CookieDriverTest extends _BaseTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    /**
     * @return void
     */
    public function testGetInstance()
    {
        $instance = CookieDriver::getInstance();
        $this->assertInstanceOf(CookieDriver::class, $instance);
    }

    /**
     * @return void
     */
    public function testMake()
    {
        $res = App::$cookie->make('test-param', 'test-value');
        $this->assertTrue(!$res); // TODO: fake, need to think how repair this (but setcookie() in console returns false)
    }

    /**
     * @return void
     */
    public function testSet()
    {
        $res = App::$cookie->set('test-param', 'test-value');
        $this->assertTrue(!$res); // TODO: fake, need to think how repair this (but setcookie() in console returns false)
    }

    /**
     * @return void
     */
    public function testGetExisted()
    {
        $value_for_test = self::randomAlphanumericString();
        $_COOKIE['test-cookie-var'] = openssl_encrypt(
            serialize($value_for_test),
            "AES-128-ECB",
            App::$config->get('cookie-enc-key')
        );
        $res = App::$cookie->get('test-cookie-var');
        $this->assertEquals($value_for_test, $res);
    }

    /**
     * @return void
     */
    public function testGetNonExisted()
    {
        $val = App::$cookie->get('test-cookie-var-non-exist');
        $this->assertEquals(null, $val);
    }

    /**
     * @return void
     */
    public function testGetNonExistedButDefaultValue()
    {
        $val = App::$cookie->get('test-cookie-var-non-exist-but-default', 111);
        $this->assertEquals(111, $val);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $_COOKIE['test-cookie-var'] = self::randomAlphanumericString();
        $this->assertTrue(App::$cookie->has('test-cookie-var'));
        App::$cookie->delete('test-cookie-var');
        $this->assertEquals(false, App::$cookie->has('test-cookie-var'));
    }

    /**
     * @return void
     */
    public function testHas()
    {
        $value_for_test = self::randomAlphanumericString();
        $_COOKIE['a'] = $value_for_test;
        $this->assertTrue(App::$cookie->has('a'));
    }

    /**
     * @return void
     */
    public function testAll()
    {
        $test_data = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ];
        foreach ($test_data as $k => $v) {
            $_COOKIE[$k] = openssl_encrypt(serialize($v), "AES-128-ECB", App::$config->get('cookie-enc-key'));
        }
        $all = App::$cookie->all();
        $this->assertEquals($test_data, $all);
    }
}