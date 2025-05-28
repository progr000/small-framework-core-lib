<?php

namespace Tests;

use Core\App;
use Core\ConfigDriver;
use Core\SessionDriver;

class SessionDriverTest extends _BaseTestCase
{
    /** @var array */
    private $put = [
        'a' => 1,
        'b' => 2,
        'c' => 3,
    ];

    /**
     * @return void
     */
    public function testGetInstance()
    {
        $instance = SessionDriver::getInstance('app-small-framework');
        $this->assertInstanceOf(SessionDriver::class, $instance);
    }

    /**
     * @return void
     */
    public function testSet()
    {
        $test_value = self::randomAlphanumericString();
        App::$session->set('test-param-to-set', $test_value);
        $this->assertEquals($test_value, App::$session->get('test-param-to-set'));
    }

    /**
     * @return void
     */
    public function testGetExisted()
    {
        $_SESSION['app-small-framework']['test-param-to-set'] = self::randomAlphanumericString();
        $value = App::$session->get('test-param-to-set');

        $this->assertEquals($_SESSION['app-small-framework']['test-param-to-set'], $value);
    }

    /**
     * @return void
     */
    public function testGetNonExisted()
    {
        $val = App::$session->get('test-param-non-exist');
        $this->assertEquals(null, $val);
    }

    /**
     * @return void
     */
    public function testGetNonExistedButDefaultValue()
    {
        $val = App::$session->get('test-param-non-exist', 111);
        $this->assertEquals(111, $val);
    }

    /**
     * @return void
     */
    public function testPut()
    {
        App::$session->put($this->put);
        $this->assertEquals(1, App::$session->get('a'));
        $this->assertEquals(2, App::$session->get('b'));
        $this->assertEquals(3, App::$session->get('c'));
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        App::$session->set('for-delete', 'test');
        $this->assertEquals('test', App::$session->get('for-delete'));
        App::$session->delete('for-delete');
        $this->assertNull(App::$session->get('for-delete'));
    }

    /**
     * @return void
     */
    public function testClear()
    {
        $this->testPut();
        App::$session->clear();
        $this->assertEmpty(App::$session->all());
    }

    /**
     * @return void
     */
    public function testHas()
    {
        $this->testPut();
        $this->assertTrue(App::$session->has('a'));
        $this->assertTrue(App::$session->has('b'));
        $this->assertTrue(App::$session->has('c'));
    }

    /**
     * @return void
     */
    public function testAll()
    {
        $this->testPut();
        $res = App::$session->all();
        self::assertEquals($this->put, $res);
    }
}