<?php

namespace Core;

use Core\Exceptions\ConfigException;
use Core\Exceptions\HttpForbiddenException;
use Core\Exceptions\HttpNotFoundException;
use Core\Exceptions\IntegrityException;
use Core\Exceptions\MaintenanceException;
use Core\Exceptions\NotImplementedException;
use Core\Exceptions\BadResponseException;
use Core\Interfaces\CacheInterface;
use Core\Interfaces\MiddlewareInterface;
use Core\Providers\CacheProvider;
use ReflectionException;

class App
{
    /** @var self */
    private static $instance;
    /** @var DebugDriver */
    public static $debug;
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
    /** @var SessionDriver */
    public static $session;
    /** @var CookieDriver */
    public static $cookie;
    /** @var CacheInterface */
    public static $cache;
    /** @var DbDriver */
    public static $db;
    /** @var DbDriver[] */
    public static $DbInstances;
    /** @var \Models\User */
    public static $user;
    /** @var string */
    public static $site_root;
    /** @var string */
    public static $site_url = "";

    /** @var string */
    public static $locale;

    /**
     * @param string $config_dir
     * @throws IntegrityException
     * @throws ConfigException
     */
    private function __construct($config_dir)
    {
        /**/
        self::$debug = DebugDriver::getInstance();
        self::$config = ConfigDriver::getInstance($config_dir);
        self::$session = SessionDriver::getInstance(self::$config->get('session-container-name', 'app-small-framework'));
        self::$cookie = CookieDriver::getInstance();
        self::$cache = (new CacheProvider())->register();
        self::$route = RouteDriver::getInstance();
        self::$request = new RequestDriver();
        self::$response = new ResponseDriver();
        self::$localization = LocalizationDriver::getInstance();

        /* error_reporting and display_errors */
        ini_set('error_reporting', self::$config->get('error_reporting', 0));
        ini_set('display_errors', self::$config->get('display_errors', 0));
        $error_handler = self::$config->get('error_handler', null);
        if (is_callable($error_handler)) {
            set_error_handler($error_handler);
        }

        /**/
        self::$site_root = self::$config->get('SITE_ROOT', (defined('__WWW_DIR__') ? __WWW_DIR__ : null));
        self::$site_url = self::$config->get('SITE_URL', null);
        if (!self::$site_url && self::$request->protocol() && self::$request->host() && self::$request->port()) {
            self::$site_url = self::$request->protocol() . "://" . self::$request->host();
            if (!in_array(self::$request->port(), [80, 443, "80", "443"])) {
                self::$site_url .= ":" . self::$request->port();
            }
        }
        
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
     * @throws ConfigException
     */
    public static function init($config_dir)
    {
        if (self::$instance === null) {
            self::$instance = new self($config_dir);
        }
        self::$debug->setBootTiming();
        return self::$instance;
    }

    /**
     * @return void
     * @throws HttpNotFoundException
     * @throws ReflectionException
     * @throws MaintenanceException
     * @throws IntegrityException
     * @throws BadResponseException
     */
    public function run()
    {
        try {
            /* session start */
            App::$session->init();

            /* user initialization */
            App::$user = session('Auth');

            /* global middleware check and apply */
            $globalMiddleware = config('global-middleware', []);
            if (!is_array($globalMiddleware)) {
                throw new IntegrityException('"global-middleware" must be an array with middleware class names');
            }
            foreach ($globalMiddleware as $middleware) {
                $m = new $middleware();
                if ($m instanceof MiddlewareInterface) {
                    $m->handleOnRequest(App::$request, App::$response);
                }
            }

            /* localization init */
            App::$localization->init();

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
                if ($res instanceof \Exception) {
                    $exception = get_class($res);
                    throw new $exception($res->getMessage(), $res->getCode());
                } else {
                    self::$response->setBody($res)->send();
                }
            }
            die();

        } catch (HttpForbiddenException $e) {
            $throw_body = ['status' => false, 'code' => $e->getCode(), 'message' => $e->getMessage(), 'error' => $e->getMessage()];
            if (self::$request->isAjax()) { self::$response->asJson(); }
            !self::$response->isJson() && $throw_body = ViewDriver::render('http-exceptions/403', $throw_body);
            self::$response->setBody($throw_body)->setStatus($e->getCode())->send();
            die();
        } catch (HttpNotFoundException $e) {
            if (self::$config->get('OWN_404_HANDLER', true)) {
                $throw_body = ['status' => false, 'code' => $e->getCode(), 'message' => $e->getMessage(), 'error' => $e->getMessage()];
                if (self::$request->isAjax()) { self::$response->asJson(); }
                !self::$response->isJson() && $throw_body = ViewDriver::render('http-exceptions/404', $throw_body);
                self::$response->setBody($throw_body)->setStatus($e->getCode())->send();
                die();
            }
        } catch (NotImplementedException $e) {
            $throw_body = ['status' => false, 'code' => $e->getCode(), 'message' => $e->getMessage(), 'error' => $e->getMessage()];
            if (self::$request->isAjax()) { self::$response->asJson(); }
            !self::$response->isJson() && $throw_body = ViewDriver::render('http-exceptions/405', $throw_body);
            self::$response->setBody($throw_body)->setStatus($e->getCode())->send();
            die();
        } catch (\Exception $e) {
            $throw_body = ['status' => false, 'code' => $e->getCode(), 'message' => $e->getMessage(), 'error' => $e->getMessage()];
            if (self::$request->isAjax()) { self::$response->asJson(); }
            !self::$response->isJson() && $throw_body = ViewDriver::render('http-exceptions/500', ['e' => $e]);
            self::$response->setBody($throw_body)->setStatus($e->getCode())->send();
            die();
        }
    }
}