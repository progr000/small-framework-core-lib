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
     * @param string $field
     * @return string
     */
    public static function getRulesForHtmlInput($field)
    {
        $all_rules = (new static(true))->rules();
        if (isset($all_rules[$field])) {
            $rules = $all_rules[$field];
            if (!is_array($rules)) {
                $rules = explode('|', $rules);
            }
            foreach ($rules as $k => $v) {
                $rules['data-real-type'] = 'text';
                if (gettype($v) === 'string') {
                    $tmp = explode(':', $v);
                    if (isset($tmp[1]) && in_array($tmp[0], ['min', 'max', 'length'])) {
                        $rules[$tmp[0]] = $tmp[1];
                    } elseif (in_array($v, ['email', 'url'])) {
                        $rules['data-type'] = $v;
                    } elseif (in_array($v, ['string', 'domain'])) {
                        $rules['data-type'] = 'text';
                    } elseif (in_array($v, ['int', 'integer', 'number', 'double'])) {
                        $rules['data-type'] = 'text';
                        $rules['data-real-type'] = 'number';
                    } elseif ($v === 'required') {
                        $rules['required'] = 'required';
                    }
                    unset($rules[$k]);
                }
            }
            if ($rules['data-real-type'] === 'text') {
                if (isset($rules['min'])) {
                    $rules['minlength'] = $rules['min'];
                    unset($rules['min']);
                }
                if (isset($rules['max'])) {
                    $rules['maxlength'] = $rules['max'];
                    unset($rules['max']);
                }
                if (isset($rules['length'])) {
                    $rules['minlength'] = $rules['length'];
                    $rules['maxlength'] = $rules['length'];
                    unset($rules['length']);
                }
            }
            unset($rules['data-real-type']);
            $ret = '';
            foreach ($rules as $k => $v) {
                $ret .= " {$k}=\"{$v}\"";
            }
            return trim($ret);
        }
        return '';
    }

    /**
     * Constructor
     * @return void|mixed
     * @throws ValidatorException
     */
    public function __construct($only_get_rules = false)
    {
        /**/
        if ($only_get_rules) {
            return;
        }

        /**/
        $this->method = mb_strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
        if (isset($_REQUEST['_method']) && in_array(mb_strtoupper($_REQUEST['_method']), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->method = mb_strtoupper($_REQUEST['_method']);
        }
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
     * @return false
     */
    public function onFailedValidation()
    {
        SessionDriver::getInstance('old-request')->put($this->all());
        SessionDriver::getInstance('error-request')->put($this->getErrors());

        return false;
    }

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
     * This method must return messages for rules
     * Strongly recommended leave empty rules in this class
     * and override this method in child class with needed rules
     * @return array
     */
    public function messages()
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

        /* clear some old data */
        SessionDriver::getInstance('old-request')->clear();
        SessionDriver::getInstance('error-request')->clear();

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
     * @return bool
     */
    public function isGet()
    {
        return (mb_strtolower($this->method) === 'get');
    }

    /**
     * @return bool
     */
    public function isPost()
    {
        return (mb_strtolower($this->method) === 'post');
    }

    /**
     * @return bool
     */
    public function isDelete()
    {
        return (mb_strtolower($this->method) === 'delete');
    }

    /**
     * @return bool
     */
    public function isPut()
    {
        return (in_array(mb_strtolower($this->method), ['put', 'path']));
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return 'XMLHttpRequest' == $this->header('X-Requested-With');
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
