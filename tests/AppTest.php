<?php

namespace Tests;

use Maksym\Config\ConfigDriver;
use Maksym\Config\ConfigException;
use Maksym\SessCook\CookieDriver;
use Maksym\SessCook\SessionDriver;
use Core\App;
use Core\LocalizationDriver;
use Core\RequestDriver;
use Core\ResponseDriver;
use Core\RouteDriver;

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
     * @throws ConfigException
     */
    public function testAppInitNoConfig()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessageRegExp("*is not a file or not readable file*");
        App::init(__DIR__);
    }

    /**
     * @test
     * @return void
     * @throws ConfigException
     */
    public function testAppInitOk()
    {
        $app = App::init(__DIR__ . DIRECTORY_SEPARATOR . "config/main.php");
        $this->assertInstanceOf(App::class, $app);
        $this->assertInstanceOf(ConfigDriver::class, $app::$config);
        $this->assertInstanceOf(SessionDriver::class, $app::$session);
        $this->assertInstanceOf(CookieDriver::class, $app::$cookie);
        $this->assertInstanceOf(RouteDriver::class, $app::$route);
        $this->assertInstanceOf(RequestDriver::class, $app::$request);
        $this->assertInstanceOf(ResponseDriver::class, $app::$response);
        $this->assertInstanceOf(LocalizationDriver::class, $app::$localization);
    }
}