<?php

namespace Tests;

use Core\App;
use Core\LocalizationDriver;

class LocalizationDriverTest extends _BaseTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        App::$locale = "en";
        App::$config->set('localization', [
            'json-path' => __DIR__ . '/config/',
            'default-locale' => 'en',
            'available-locales' => [
                'en' => 'English',
                'de' => 'Deutsch',
            ],
        ]);
    }

    /**
     * @return void
     */
    public function testGetInstance()
    {
        $instance = LocalizationDriver::getInstance();
        $this->assertInstanceOf(LocalizationDriver::class, $instance);
    }

    /**
     * @return void
     */
    public function testInitOk()
    {
        $res = App::$localization->init();
        $this->assertTrue($res);
    }

    /**
     * @return void
     */
    public function testInitFail()
    {
        App::$locale = "de";
        $res = App::$localization->init();
        $this->assertEquals(false, $res);
    }

    /**
     * @return void
     */
    public function testGetExisted()
    {
        $res = LocalizationDriver::getInstance()->get("Name-key");
        $this->assertEquals("Name", $res);
    }

    /**
     * @return void
     */
    public function testGetNonExisted()
    {
        $res = LocalizationDriver::getInstance()->get("NonExisted-key");
        $this->assertEquals("NonExisted-key", $res);
    }
}