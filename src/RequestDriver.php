<?php

namespace Core;

use Core\Interfaces\RequestInterface;
use Exception;
use Core\Exceptions\ValidatorException;


class RequestDriver implements RequestInterface
{
    /** all possible request vars are stored in this vars */
    /** @var array */
    protected $post = [];
    /** @var array */
    protected $get = [];
    /** @var array */
    protected $cookie = [];
    /** @var array */
    protected $session;
    /** @var array */
    protected $server = [];
    /** @var array */
    protected $json = [];
    /** @var false|string */
    protected $rawContent;
    /** @var array */
    protected $all_request = [];
    /** @var array|false */
    protected $headers = [];

    /** all request technical info is stored in this vars */
    /** @var string */
    protected $method;
    /** @var mixed|null */
    protected $protocol;
    /** @var mixed|string */
    protected $host;
    /** @var int|null */
    protected $port;
    /** @var string */
    protected $route;
    /** @var string */
    protected $query;
    /** @var string */
    protected $full_url;
    /** @var string|null */
    protected $referer;
    /** @var mixed|string */
    protected $ip;
    /** @var array */
    protected $trustProxies = [];

    /** variables in which are stored data after validation based on rules() */
    /** @var array */
    protected $validated = [];
    /** @var array */
    protected $errors = [];


    /**
     * Constructor
     * @return void|mixed
     * @throws ValidatorException
     */
    public function __construct()
    {
        $this->method = mb_strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
        $this->protocol = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : null;
        $this->host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $this->port = isset($_SERVER['SERVER_PORT']) ? intval($_SERVER['SERVER_PORT']) : null;
        $this->route = App::$route->getRoute();
        $this->query = App::$route->getQuery();
        $this->full_url = $this->route . ($this->query ? '?' . $this->query : '');
        $this->referer = App::$route->getReferer();
        $this->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $this->headers = function_exists('getallheaders') ? getallheaders() : [];

        /* check protocol via proxy */
        $check_proxy_proto = $this->header('X-Forwarded-Proto');
        if ($check_proxy_proto) {
            $this->protocol = $check_proxy_proto;
        }

        if (!empty($_POST)) {
            $this->post = $_POST;
        }
        if (!empty($_GET)) {
            $this->get = $_GET;
        }
        if (!empty($_COOKIE)) {
            $this->cookie = $_COOKIE;
        }
        if (!empty($_SERVER)) {
            $this->server = $_SERVER;
        }
        try {
            $this->rawContent = file_get_contents('php://input');
            if ($this->rawContent === false) $this->rawContent = '';
        } catch (Exception $e) {
            $this->rawContent = '';
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            if (strrpos($_SERVER['CONTENT_TYPE'], 'json') !== false) {
                try {
                    $this->json = json_decode($this->rawContent, true);
                } catch (Exception $e) {
                    $this->json = [];
                }
                /*
                if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                    if (!is_array($this->json)) $this->json = [];
                    $this->post = array_merge($this->json, $_POST);
                }
                */
            }
        }

        if (is_array($this->json)) {
            $this->all_request = array_merge($this->get, $this->post, $this->cookie, $this->json);
        } else {
            $this->all_request = array_merge($this->get, $this->post, $this->cookie);
        }

        if (($this->validate() === false) && method_exists($this, 'onFailedValidation')) {
            return $this->onFailedValidation();
        }
    }

    /**
     * @param array $ips
     * @return $this
     */
    public function setTrustProxies(array $ips = [])
    {
        $this->trustProxies = $ips;
        if (in_array($this->ip, $this->trustProxies)) {
            if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                $this->ip = $_SERVER['HTTP_X_REAL_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $this->ip = $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        return $this;
    }
    
    
    /** ========== Validation methods ============== */
    /**
     * This method must return rules for validation
     * Strongly recommended leave empty rules in this class
     * and override this method in child class with needed rules
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Start validation
     * @return bool
     * @throws ValidatorException
     */
    public function validate()
    {
        //dd($this->rules());
        if (empty($this->rules())) {
            return null;
        }

        $validator = new ValidatorDriver($this);
        return $validator->validate();
    }

    /**
     * Set validated data
     * (used by validator driver, not recommended to use in direct way)
     * @param array $data
     * @return $this
     */
    public function setValidated(array $data)
    {
        $this->validated = $data;
        return $this;
    }

    /**
     * get array with validated vars
     * @return array
     */
    public function validated()
    {
        return $this->validated;
    }

    /**
     * Set errors array
     * (used by validator driver, not recommended to use in direct way)
     * @param array $data
     * @return $this
     */
    public function setErrors(array $data)
    {
        $this->errors = $data;
        return $this;
    }

    /**
     * Get errors after validation fail
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }


    /** ========== getters ============== */
    /**
     * Get the bearer token from the request headers.
     * @return string|null
     */
    public function bearerToken()
    {
        $header = $this->header('Authorization', '');
        $position = strrpos($header, 'Bearer ');
        if ($position !== false) {
            $token = substr($header, $position + 7);
            return strpos($token, ',') !== false ? strstr($token, ',', true) : $token;
        }
        return null;
    }

    /**
     * @return string
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function protocol()
    {
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function host()
    {
        return $this->host;
    }

    /**
     * @return int|null
     */
    public function port()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function route()
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function fullUrl()
    {
        return $this->full_url;
    }

    /**
     * @return string|null
     */
    public function referer()
    {
        return $this->referer;
    }

    /**
     * @return string
     */
    public function ip()
    {
        return $this->ip;
    }

    /**
     * @return string|null
     */
    public function userAgent()
    {
        return $this->header('User-Agent');
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function header($key = null, $default = null)
    {
        if (is_null($key)) return $this->headers;
        return isset($this->headers[$key]) ? $this->headers[$key] : $default;
    }

    /**
     * @return false|string
     */
    public function rawContent()
    {
        return $this->rawContent;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed|null
     */
    public function post($key = null, $default = null)
    {
        if (is_null($key)) return $this->post;
        return isset($this->post[$key]) ? $this->post[$key] : $default;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed|null
     */
    public function get($key = null, $default = null)
    {
        if (is_null($key)) return $this->get;
        return isset($this->get[$key]) ? $this->get[$key] : $default;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed|null
     */
    public function cookie($key = null, $default = null)
    {
        if (is_null($key)) return $this->cookie;
        return isset($this->cookie[$key]) ? $this->cookie[$key] : $default;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed|null
     */
    public function json($key = null, $default = null)
    {
        if (is_null($key)) return $this->json;
        return isset($this->json[$key]) ? $this->json[$key] : $default;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed|null
     */
    public function server($key = null, $default = null)
    {
        if (is_null($key)) return $this->get;
        return isset($this->server[$key]) ? $this->server[$key] : $default;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed|null
     */
    public function all($key = null, $default = null)
    {
        if (is_null($key)) return $this->all_request;
        return isset($this->all_request[$key]) ? $this->all_request[$key] : $default;
    }
}
