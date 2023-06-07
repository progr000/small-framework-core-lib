<?php

namespace Core;

use Core\Exceptions\DbException;
use Core\Exceptions\HttpForbiddenException;
use Core\Exceptions\HttpNotFoundException;
use Core\Exceptions\IntegrityException;
use Core\Exceptions\NotImplementedException;
use Core\Middleware\Maintenance;
use ReflectionException;


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
    /** @var LocalizationDriver */
    public static $localization;
    /** @var DbDriver */
    public static $db;
    /** @var DbDriver[] */
    public static $DbInstances;

    /** @var string */
    public static $locale;

    /**
     * @param string $config_dir
     * @throws IntegrityException
     */
    private function __construct($config_dir)
    {
        session_start();

        require_once __DIR__ . "/DumperDriver.php";

        self::$config = ConfigDriver::getInstance($config_dir);
        self::$route = RouteDriver::getInstance();
        self::$request = new RequestDriver();
        self::$response = new ResponseDriver();
        self::$localization = LocalizationDriver::getInstance();

        /**/
        self::$locale = self::$config->get('localization', ['default-locale' => "en"])['default-locale'];

        /**/
        foreach (self::$config->get('databases', []) as $conn_name => $conn_params) {
            if ($conn_name !== 'default-db-connection-name') {
                DbDriver::getInstance($conn_name);
            }
        }
        if (isset(self::$config->get('databases', [])['default-db-connection-name'])
            && isset(App::$DbInstances[self::$config->get('databases', [])['default-db-connection-name']])
        ) {
            self::$db = DbDriver::getInstance(self::$config->get('databases', [])['default-db-connection-name']);
        }

    }

    /**
     * Initialization App
     * @return App
     * @throws IntegrityException
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
     * @throws HttpNotFoundException
     * @throws ReflectionException|IntegrityException
     */
    public function run()
    {
        try {
            /* maintenance global */
            $m = new Maintenance();
            $m->handle(App::$request);

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
        } catch (DbException $e) {
            $throw_body = ['status' => false, 'code' => $e->getCode(), 'error' => $e->getMessage()];
            !self::$response->isJson() && $throw_body = ViewDriver::render('http-exceptions/500', $throw_body);
            self::$response->setBody($throw_body)->setStatus($e->getCode())->send();
        }
        die();
    }
}