<?php

namespace Tests;

use Core\App;
use Core\ConfigDriver;
use Core\Contracts\CacheDrivers\fileCacheDriver;
use Core\CookieDriver;
use Core\DebugDriver;
use Core\LocalizationDriver;
use Core\RequestDriver;
use Core\ResponseDriver;
use Core\RouteDriver;
use Core\SessionDriver;

class AppTest extends _BaseTestCase
{
    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        // here must be empty to redefine _BaseTestCase::setUpBeforeClass()
        // because this class must create app by itself for specified tests-case
    }

    /**
     * @test
     * @return void
     * @throws \Core\Exceptions\ConfigException
     * @throws \Core\Exceptions\IntegrityException
     */
    public function testAppInitNoConfig()
    {
        $this->expectException(\Core\Exceptions\IntegrityException::class);
        $this->expectExceptionMessageRegExp("*Configuration file is missing*");
        App::init(__DIR__);
    }

    /**
     * @test
     * @return void
     * @throws \Core\Exceptions\ConfigException
     * @throws \Core\Exceptions\IntegrityException
     */
    public function testAppInitOk()
    {
        $app = App::init(__DIR__ . DIRECTORY_SEPARATOR . 'config');
        $this->assertInstanceOf(App::class, $app);
        $this->assertInstanceOf(DebugDriver::class, $app::$debug);
        $this->assertInstanceOf(ConfigDriver::class, $app::$config);
        $this->assertInstanceOf(SessionDriver::class, $app::$session);
        $this->assertInstanceOf(CookieDriver::class, $app::$cookie);
        $this->assertInstanceOf(fileCacheDriver::class, $app::$cache);
        $this->assertInstanceOf(RouteDriver::class, $app::$route);
        $this->assertInstanceOf(RequestDriver::class, $app::$request);
        $this->assertInstanceOf(ResponseDriver::class, $app::$response);
        $this->assertInstanceOf(LocalizationDriver::class, $app::$localization);
    }
}