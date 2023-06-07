<?php

namespace Core;

use Core\Exceptions\HttpNotFoundException;
use Core\Exceptions\IntegrityException;
use Core\Interfaces\RequestInterface;
use ReflectionException;


/**
 * Class Route
 * @package core\Services
 */
class RouteDriver
{
    /** @var self */
    private static $instance;
    /** @var array */
    private $available_routes;
    /** @var string */
    private $route;
    /** @var string */
    private $query;
    /** @var string */
    private $script_root;
    /** @var string|null */
    private $referer;

    /** @var string */
    private $controller;
    /** @var string */
    private $action;
    /** @var string */
    private $route_pattern;

    /**
     * Route constructor.
     * @throws IntegrityException
     */
    private function __construct()
    {
        /* get list of available routes from config */
        $routes = config('routes');
        if (!$routes) {
            throw new IntegrityException('routes is required parameter in config (must be an special array)', 500);
        }

        /* prepare routes from config data */
        foreach ($routes as $k => $v) {
            if (gettype($k) === 'integer') {
                /* for the rest type of route will be created additional routes */
                if (isset($v[1])) {
                    $route_path = '~^' . $v[1];
                } else {
                    $route_path = '~^/' . str_replace(
                            'controller',
                            '',
                            mb_strtolower(substr($v[0], strrpos($v[0], '\\') + 1))
                        ). '/';
                }

                if (isset($v['middleware']))
                    $middleware = $v['middleware'];
                else
                    $middleware = null;

                $this->available_routes[] = ['controller' => $v[0], 'action' => 'index',     'middleware' => $middleware, 'method' => 'GET',    'pattern' => $route_path . '?$~'];
                $this->available_routes[] = ['controller' => $v[0], 'action' => 'view',      'middleware' => $middleware, 'method' => 'GET',    'pattern' => $route_path . '(\d+)/?$~'];
                $this->available_routes[] = ['controller' => $v[0], 'action' => 'view',      'middleware' => $middleware, 'method' => 'GET',    'pattern' => $route_path . '(\d+)/view/?$~'];
                $this->available_routes[] = ['controller' => $v[0], 'action' => 'edit',      'middleware' => $middleware, 'method' => 'GET',    'pattern' => $route_path . '(\d+)/edit/?$~'];
                $this->available_routes[] = ['controller' => $v[0], 'action' => 'update',    'middleware' => $middleware, 'method' => 'PUT',    'pattern' => $route_path . '(\d+)/?$~'];
                $this->available_routes[] = ['controller' => $v[0], 'action' => 'create',    'middleware' => $middleware, 'method' => 'GET',    'pattern' => $route_path . 'create/?$~'];
                $this->available_routes[] = ['controller' => $v[0], 'action' => 'store',     'middleware' => $middleware, 'method' => 'POST',   'pattern' => $route_path . '?$~'];
                $this->available_routes[] = ['controller' => $v[0], 'action' => 'delete',    'middleware' => $middleware, 'method' => 'DELETE', 'pattern' => $route_path . '(\d+)/?$~'];
                $this->available_routes[] = ['controller' => $v[0], 'action' => '{{%ANY%}}', 'middleware' => $middleware, 'method' => null,  'pattern' => $route_path . '(\d+)/([a-z\-]{3,15})/?$~'];
            } elseif (gettype($v) === 'array') {
                /* for ordinal route */

                if (isset($v['middleware']))
                    $middleware = $v['middleware'];
                else
                    $middleware = null;

                $pattern = '~^' . $k . '$~';
                if (gettype($v[0]) === 'object') {
                    $tmp = ['controller' => null, 'action' => $v[0], 'middleware' => $middleware, 'pattern' => $pattern];
                } else {
                    $action = isset($v[1]) ? $v[1] : 'index';
                    $tmp = ['controller' => $v[0], 'action' => $action, 'middleware' => $middleware, 'pattern' => $pattern];
                }

                if (isset($v[2])) {
                    $tmp['method'] = $v[2];
                }
                if (isset($v['method'])) {
                    $tmp['method'] = $v['method'];
                }
                $this->available_routes[] = $tmp;

            } elseif (gettype($v) === 'object') {
                $pattern = '~^' . $k . '$~';
                $this->available_routes[] = ['controller' => null, 'action' => $v, 'pattern' => $pattern];
            }
        }
        //dd($this->available_routes);
        $this->prepareURI();
    }

    /**
     * @return RouteDriver
     */
    public static function getInstance()
    {
        /**/
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * This function for add ability
     * to use sub-folder in document_root
     * as if it were document_root
     * @return void
     */
    private function prepareURI()
    {
        $uri = explode('?', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
        $test = str_replace(
            (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ''),
            '',
            (isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '')
        );
        $to_replace = mb_substr($test, 0, strrpos($test, '/'));
        $uri[0] = str_replace($to_replace, '', $uri[0]);

        $this->route = $uri[0];
        $this->query = isset($uri[1]) ? $uri[1] : '';
        $this->script_root = $to_replace;
        $this->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    /**
     * Find needed controller and action for current route
     * and try to execute and return result
     * @return mixed
     * @throws HttpNotFoundException
     * @throws ReflectionException
     */
    public function processRoute()
    {
        /**/
        $isRouteFound = false;
        foreach ($this->available_routes as $controllerAndAction) {

            $pattern = $controllerAndAction['pattern'];
            //dump($pattern, $controllerAndAction);
            unset($matches);
            preg_match($pattern, $this->route, $matches);
            if (!empty($matches)) {
                if (!isset($controllerAndAction['method'])) {
                    $this->route_pattern = $pattern;
                    $isRouteFound = true;
                    break;
                } elseif (mb_strtoupper($controllerAndAction['method']) === App::$request->method()) {
                    $this->route_pattern = $pattern;
                    $isRouteFound = true;
                    break;
                }
            }
        }

        /**/
        if (!$isRouteFound || !isset($controllerAndAction)) {
            throw new HttpNotFoundException('Not found', 404);
        }

        /**/
        if (isset($matches)) unset($matches[0]);
        if (!isset($matches)) $matches = [];

        /* Localization */
        App::$localization->init();

        /**/
        if (gettype($controllerAndAction['action']) === 'object') {

            $this->controller = null;
            $this->action = 'closure';
            return $controllerAndAction['action'](...$matches);

        } else {

            //$controller = new $controllerAndAction['controller'](...$matches);
            //dd($controllerAndAction['controller']);
            $controller = new $controllerAndAction['controller']();
            $this->controller = get_class($controller);

            /* to add any additional action for the REST-API */
            if ($controllerAndAction['action'] === '{{%ANY%}}' && isset($matches[2])) {
                $tmp = explode('-', $matches[2]);
                if (count($tmp) > 1) {
                    for ($i = 1; $i < count($tmp); $i++) {
                        $tmp[$i] = ucfirst($tmp[$i]);
                    }
                }
                $controllerAndAction['action'] = implode('', $tmp);
            }
            $this->action = $controllerAndAction['action'];

            /* try to get params and if params is class, create some object */
            if (method_exists($controller, $controllerAndAction['action'])) {

                $r = new \ReflectionMethod($controllerAndAction['controller'], $controllerAndAction['action']);
                $params = $r->getParameters();
                foreach ($params as $param) {
                    unset($className, $execClassName);
                    if (version_compare(phpversion(), '8', '>=')) {
                        $className = $param->getType();
                        if (is_object($className) && method_exists($className, 'getName')) {
                            $execClassName = $className->getName();
                        }
                    } else {
                        $className = $param->getClass();
                        if (!is_null($className) && isset($className->name)) {
                            $execClassName = $className->name;
                        }
                    }
                    if (isset($execClassName)) {
                        $tmpObj = new $execClassName();
                        if ($tmpObj instanceof RequestInterface) {
                            /* middleware apply to Request */
                            $allMiddleware = config('global-middleware', []);
                            foreach ($allMiddleware as $middleware) {
                                $m = new $middleware();
                                $m->handle($tmpObj);
                            }
                        }
                        $reflect[] = $tmpObj;
                    }
                }
                if (isset($reflect)) {
                    $matches = array_merge($reflect, $matches);
                }

                return $controller->{$controllerAndAction['action']}(...$matches);
            } else {
                throw new HttpNotFoundException("Not found (controller dosen't has method)", 404);
            }

        }
    }

    /**
     * Return current route
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Return current query string
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Return root dir for main script (entry-point-script)
     * @return string
     */
    public function getRoot()
    {
        return ($this->script_root === "")
            ? "/"
            : $this->script_root;
    }

    /**
     * @return string|null
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getRoutePatern()
    {
        return $this->route_pattern;
    }
}