<?php

namespace Tests;

use Core\App;
use Core\DebugDriver;

class DebugDriverTest extends _BaseTestCase
{
    /**
     * @return void
     * @throws \Core\Exceptions\ConfigException
     * @throws \Core\Exceptions\IntegrityException
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        App::$config->set('IS_DEBUG', true);
    }

    /**
     * @return void
     */
    public function testGetInstance()
    {
        $instance = DebugDriver::getInstance();
        $this->assertInstanceOf(DebugDriver::class, $instance);
    }

    /**
     * @return void
     */
    public function testSetAndGet()
    {
        App::$config->set('IS_DEBUG', false);
        $res = App::$debug->_get("sqlLog");
        $this->assertContains("This works only in debug mode, please put IS_DEBUG => true into config/main.php", $res);
        App::$config->set('IS_DEBUG', true);
        App::$debug->_set("sqlLog", 'SELECT version()');
        App::$debug->_set("sqlLog", ['SELECT 1']);
        $res = App::$debug->_get("sqlLog");
        $this->assertContains('SELECT 1', $res);
        $this->assertContains('SELECT version()', $res);
        $res = App::$debug->_get("notExistContainer");
        $this->assertNull($res);
    }

    /**
     * @return void
     */
    public function testGetSqlLog()
    {
        $res = App::$debug->getSqlLog();
        $this->assertTrue(is_array($res));
    }

    /**
     * @return void
     */
    public function testGetSessionData()
    {
        $_SESSION['test'] = self::randomAlphanumericString();
        $res = App::$debug->getSessionData();
        $this->assertTrue(is_array($res));
    }

    /**
     * @return void
     */
    public function testGetRouteData()
    {
        $res = App::$debug->getRouteData();
        $this->assertTrue(is_array($res));
    }

    /**
     * @return void
     */
    public function testGetViewData()
    {
        $res = App::$debug->getViewData();
        $this->assertTrue(is_array($res));
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSetBootTiming()
    {
        App::$debug->setBootTiming();
        $class = new \ReflectionClass(App::$debug);
        $property = $class->getProperty('timingData');
        $property->setAccessible(true);
        $first = $property->getValue(App::$debug);
        usleep(30);
        App::$debug->setBootTiming();
        $second = $property->getValue(App::$debug);
        $this->assertNotEquals($first, $second);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testSetAppTiming()
    {
        App::$debug->setAppTiming();
        $class = new \ReflectionClass(App::$debug);
        $property = $class->getProperty('timingData');
        $property->setAccessible(true);
        $first = $property->getValue(App::$debug);
        usleep(30);
        App::$debug->setAppTiming();
        $second = $property->getValue(App::$debug);
        $this->assertNotEquals($first, $second);
    }
}