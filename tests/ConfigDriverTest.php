<?php

namespace Tests;

use Core\App;
use Core\ConfigDriver;

class ConfigDriverTest extends _BaseTestCase
{
    /**
     * @return void
     * @throws \Core\Exceptions\IntegrityException
     */
    public function testGetInstance()
    {
        $instance = ConfigDriver::getInstance(__DIR__ . DIRECTORY_SEPARATOR . 'config');
        $this->assertInstanceOf(ConfigDriver::class, $instance);
    }

    /**
     * @return void
     */
    public function testGetExisted()
    {
        $val = App::$config->get('test-param');
        $this->assertEquals('test-value', $val);
    }

    /**
     * @return void
     */
    public function testGetNonExisted()
    {
        $val = App::$config->get('test-param-non-exist');
        $this->assertEquals(null, $val);
    }

    /**
     * @return void
     */
    public function testGetNonExistedButDefaultValue()
    {
        $val = App::$config->get('test-param-non-exist', 111);
        $this->assertEquals(111, $val);
    }

    /**
     * @return void
     */
    public function testExistYes()
    {
        $val = App::$config->exist('test-param');
        $this->assertTrue($val);
    }

    /**
     * @return void
     */
    public function testExistNo()
    {
        $val = App::$config->exist('test-param-non-exist');
        $this->assertEquals(false, $val);
    }

    public function testSet()
    {
        $val = self::randomAlphanumericString();
        $set = App::$config->set('test-param-to-set', $val);
        $this->assertTrue($set);
        $this->assertEquals($val, App::$config->get('test-param-to-set'));
    }
}