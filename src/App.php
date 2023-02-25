<?php

namespace Core;

use Exception;
use Core\Exceptions\ConfigException;
use Core\Exceptions\HttpForbiddenException;
use Core\Exceptions\HttpNotFoundException;
use Core\Exceptions\NotImplementedException;

//require_once __DIR__ . "/DumperDriver.php";


class App
{
    /** @var self */
    private static $instance;
    /** @var ConfigDriver */
    public static $config;
    /** @var RouteDriver */
    public static $route;
    /** @var RequestDriver */
    public static $request;
    /** @var ResponseDriver */
    public static $response;
    /** @var DbDriver */
    public static $db;

    /**
     * Constructor
     */
    private function __construct($config_dir)
    {
        self::$config = ConfigDriver::getInstance($config_dir);
        self::$route = RouteDriver::getInstance();
        self::$request = new RequestDriver();
        self::$response = new ResponseDriver();
        self::$db = DbDriver::getInstance();
    }

    /**
     * Initialization App
     * @return App
     */
    public static function init($config_dir)
    {
        if (self::$instance === null) {
            self::$instance = new self($config_dir);
        }
        return self::$instance;
    }

    /**
     * @return void
     * @throws ConfigException
     * @throws HttpNotFoundException
     */
    public function run()
    {
        try {
            /**/
            ob_start();
            $res = self::$route->processRoute();
            $debug_data = ob_get_contents();
            ob_end_clean();

            if ($res instanceof ResponseDriver) {
                $res->setDebugData($debug_data);
                $res->send();
            } else {
                self::$response->setDebugData($debug_data);
                self::$response->setBody($res)->send();
            }
            die();

        } catch (HttpForbiddenException $e) {
            $throw_body = ['status' => false, 'code' => $e->getCode(), 'error' => $e->getMessage()];
            !self::$response->isJson() && $throw_body = ViewDriver::render('http-exceptions/403', $throw_body);
            self::$response->setBody($throw_body)->setStatus($e->getCode())->send();
        } catch (HttpNotFoundException $e) {
            $throw_body = ['status' => false, 'code' => $e->getCode(), 'error' => $e->getMessage()];
            !self::$response->isJson() && $throw_body = ViewDriver::render('http-exceptions/404', $throw_body);
            self::$response->setBody($throw_body)->setStatus($e->getCode())->send();
        } catch (NotImplementedException $e) {
            $throw_body = ['status' => false, 'code' => $e->getCode(), 'error' => $e->getMessage()];
            !self::$response->isJson() && $throw_body = ViewDriver::render('http-exceptions/405', $throw_body);
            self::$response->setBody($throw_body)->setStatus($e->getCode())->send();
        } catch (Exception $e) {
            $throw_body = ['status' => false, 'code' => $e->getCode(), 'error' => $e->getMessage()];
            !self::$response->isJson() && $throw_body = ViewDriver::render('http-exceptions/500', $throw_body);
            self::$response->setBody($throw_body)->setStatus($e->getCode())->send();
        }
        die();
    }
}