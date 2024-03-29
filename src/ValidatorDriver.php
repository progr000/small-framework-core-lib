<?php

namespace Core;

use Core\Exceptions\ValidatorException;

class ValidatorDriver
{
    /** @var array */
    protected $failed = [];
    /** @var array */
    protected $success = [];
    /** @var array */
    protected $data;

    /** @var RequestDriver  */
    private $request;

    /**
     * @param RequestDriver $request
     */
    public function __construct(RequestDriver $request)
    {
        $this->request = $request;
        $this->data = $this->request->all();
    }

    /**
     * @return array
     */
    public function getValidated()
    {
        return $this->success;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->failed;
    }

    /**
     * @param string $key
     * @param string $rule_item
     * @param string $default_message
     * @param array $replace
     * @return string
     */
    private function getMessage($key, $rule_item, $default_message, $replace = [])
    {
        $m = $this->request->messages();
        return replace_vars(
            (isset($m["{$key}_$rule_item"]) ? $m["{$key}_$rule_item"] : $default_message),
            $replace
        );
    }

    /**
     * @return bool
     * @throws ValidatorException
     */
    public function validate()
    {
        $ret = true;
        foreach ($this->request->rules() as $key => $rule) {

            $methods = [];
            $params = [];
            if (!is_array($rule)) {
                $rule = explode('|', $rule);
            }
            foreach ($rule as $key2 => $value) {

                if ($value === 'file') {
                    $params['file'] = 'file';
                }

                if (gettype($value) === 'object' && is_callable($value)) {

                    $methods[] = ['type' => 'closure', 'exec' => $value];

                } else {

                    if (gettype($key2) !== 'integer' && in_array($key2, ['min', 'max', 'length'])) {
                        $value = $key2 . ":" . $value;
                    }

                    $tmp = explode(':', $value);

                    if (isset($tmp[1])) {

                        if (method_exists($this, $tmp[0])) {
                            $methods[] = ['type' => 'function', 'exec' => $tmp[0]];
                            for ($i = 1; $i < count($tmp); $i++) {
                                //$params[$tmp[$i]] = $tmp[$i];
                                $params[$tmp[0]] = $tmp[$i];
                            }
                        } else {
                            if (in_array($tmp[0], ['min', 'max']))
                                $params[$tmp[0]] = doubleval($tmp[1]);
                            elseif ($tmp[0] === 'length')
                                $params[$tmp[0]] = intval($tmp[1]);
                            elseif ($tmp[0] === 'regex')
                                $params[$tmp[0]] = intval($tmp[1]);
                            else
                                $params[$tmp[0]] = $tmp[1];
                            //$params[] = $tmp[1];
                        }

                    } else {

                        if (method_exists($this, $tmp[0])) {
                            $methods[] = ['type' => 'function', 'exec' => $tmp[0]];
                        } elseif (class_exists($tmp[0])) {
                            $methods[] = ['type' => 'class', 'exec' => $tmp[0]];
                        } else {
                            throw new ValidatorException("Wrong rule in RequestClass " . get_class($this->request) . ". Validator-method {$tmp[0]}() doesn't exist.", 500);
                        }

                    }

                }
            }

            if (empty($methods)) {
                throw new ValidatorException('Wrong rule in RequestClass ' . get_class($this->request), 500);
            }

            $test_var = true;
            foreach ($methods as $method) {
                if (isset($this->data[$key]) || $method['exec'] === 'required') {
                    //dump($this->data[$key], $method, $this->$method($key, $params));
                    if ($method['type'] === 'function') {
                        if (!$this->{$method['exec']}($key, $params)) {
                            $ret = false;
                            $test_var = false;
                        }
                    } elseif ($method['type'] === 'closure') {
                        if (!$method['exec']($key, $params, $this->data)) {
                            $this->failed[$key][] = $this->getMessage($key, 'closure', __('Wrong value'));
                            $ret = false;
                            $test_var = false;
                        }
                    } else {
                        $f = new $method['exec']($this);
                        if (!$f($this->data[$key], $params, $this->data)) {
                            $this->failed[$key][] = $this->getMessage($key, $method['exec'], (isset($f->errorMessage) ? __($f->errorMessage, $params) : __('Wrong value')));
                            $ret = false;
                            $test_var = false;
                        }
                    }
                }
            }
            if ($test_var && isset($this->data[$key])) {
                $this->success[$key] = $this->data[$key];
            }

        }

        $this->request->setValidated($this->success);
        $this->request->setErrors($this->getErrors());

        return $ret;
    }

    /**
     * @param string $key
     * @return bool
     */
    private function required($key, $params = [])
    {
        if (isset($params['file'])) {
            if (!key_exists($key, $this->data) || empty($this->data[$key]) || empty($this->data[$key]['tmp_name'])) {
                $this->failed[$key][] = $this->getMessage($key, 'required', __('Value is required'));
                return false;
            }
        }
        if (!key_exists($key, $this->data) || empty($this->data[$key])) {
            $this->failed[$key][] = $this->getMessage($key, 'required', __('Value is required'));
            return false;
        }
        return true;
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function int($key, array $params = [])
    {
        //dump("int($key, " . json_encode($params) .")");
        if (preg_match("/^[0-9]+$/", $this->data[$key])) {
            $val = intval($this->data[$key]);
            if (isset($params['min']) || isset($params['max'])) {
                if (isset($params['min']) && $val < $params['min']) {
                    $this->failed[$key][] = $this->getMessage($key, 'min',__("Value too small, min value {%min}"), $params);
                    $ret = false;
                }
                if (isset($params['max']) && $val > $params['max']) {
                    $this->failed[$key][] = $this->getMessage($key,'max', __("Value too big, max value {%max}"), $params);
                    $ret = false;
                }
                if (!isset($ret)) {
                    $this->data[$key] = $val;
                    $ret = true;
                }
            } else {
                $this->data[$key] = $val;
                $ret = true;
            }
        } else {
            $this->failed[$key][] = $this->getMessage($key,'int', __('Value must be an integer'));
            $ret = false;
        }

        return $ret;
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function integer($key, array $params = [])
    {
        return $this->int($key, $params);
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function double($key, array $params = [])
    {
        //dump("double($key, " . json_encode($params) .")");
        if (preg_match("/^[0-9]{1,10}(?:\.[0-9]{1,5})?$/", $this->data[$key])) {
            $val = doubleval($this->data[$key]);
            if (isset($params['min']) || isset($params['max'])) {
                if (isset($params['min']) && $val < $params['min']) {
                    $this->failed[$key][] = $this->getMessage($key,'min', __("Value too small, min value {%min}"), $params);
                    $ret = false;
                }
                if (isset($params['max']) && $val > $params['max']) {
                    $this->failed[$key][] = $this->getMessage($key,'max',__("Value too big, max value {%max}"), $params);
                    $ret = false;
                }
                if (!isset($ret)) {
                    $this->data[$key] = $val;
                    $ret = true;
                }
            } else {
                $this->data[$key] = $val;
                $ret = true;
            }
        } else {
            $this->failed[$key][] = $this->getMessage($key,'double', __('Value must be a double'));
            $ret = false;
        }

        return $ret;
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function number($key, array $params = [])
    {
        return $this->double($key, $params);
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     * @throws ValidatorException
     */
    private function string($key, array $params = [])
    {
        $val = $this->data[$key];
        if (isset($params['min']) || isset($params['max']) || isset($params['length'])) {

            if (isset($params['length']) && (isset($params['min']) || isset($params['max']))) {
                throw new ValidatorException('Wrong rule in RequestClass ' . get_class($this->request) . "Param length can't correspond with params min or max", 500);
            }

            if (!empty($val) && isset($params['min']) && mb_strlen($val) < intval($params['min'])) {
                $this->failed[$key][] = $this->getMessage($key,'min', __("Value too short, min length {%min}"), $params);
                $ret = false;
            }
            if (!empty($val) && isset($params['max']) && mb_strlen($val) > intval($params['max'])) {
                $this->failed[$key][] = $this->getMessage($key,'max', __("Value too long, max length {%max}"), $params);
                $ret = false;
            }
            if (isset($val, $params['length']) && mb_strlen($val) !== intval($params['length'])) {
                $this->failed[$key][] = $this->getMessage($key,'length', __("Value length must be {%length}"), $params);
                $ret = false;
            }
            if (!isset($ret)) {
                $this->data[$key] = $val;
                $ret = true;
            }
        } else {
            $this->data[$key] = $val;
            $ret = true;
        }

        return $ret;
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function regex($key, array $params = [])
    {
        /* do not check if empty */
        if (empty($this->data[$key])) {
            return true;
        }

        /**/
        if (empty($params['regex'])) {
            $this->failed[$key][] = $this->getMessage($key,'regex_bad', __('Wrong regex for validation'));
            return false;
        }

        if (preg_match($params['regex'], $this->data[$key])) {
            return true;
        }

        $this->failed[$key][] = $this->getMessage($key,'regex', __("Value not match with regex {%regex}"), $params);
        return false;
    }

    /**
     * @param $key
     * @param array $params
     * @return bool
     * @throws ValidatorException
     */
    private function compareEquals($key, array $params)
    {
        if (empty($params)) {
            throw new ValidatorException('Wrong rule in RequestClass ' . get_class($this->request), 500);
        }
        $key_compare = array_shift($params);
        if (!isset($this->data[$key_compare])) {
            throw new ValidatorException('Wrong compare field in rule in RequestClass ' . get_class($this->request), 500);
        }

        if ($this->data[$key] === $this->data[$key_compare]) {
            return true;
        }

        $this->failed[$key][] = $this->getMessage($key,'compareEquals', __("Value doesn't equals value {%compareEquals}"), $params);
        return false;
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function email($key, array $params = [])
    {
        /* do not check if empty */
        if (empty($this->data[$key])) {
            return true;
        }

        /**/
        if (!(bool)preg_match('/^[a-z0-9_\-\.]+(\+[a-z0-9_\-\.]+)*@[a-z0-9_\-\.]+\.[a-z]{2,6}$/i', $this->data[$key])) {
            $this->failed[$key][] = $this->getMessage($key,'email', __("Wrong email format"), $params);
            return false;
        }
        if (!filter_var($this->data[$key], FILTER_VALIDATE_EMAIL)) {
            $this->failed[$key][] = $this->getMessage($key,'email', __("Email validation failed"), $params);
            return false;
        }

        /**/
        if (!empty($params['email']) && $params['email'] === 'dns') {
            $host = mb_substr($this->data[$key], strpos($this->data[$key],'@') + 1);
            if (!isset($host)) {
                $this->failed[$key][] = $this->getMessage($key,'email', __("Email host not found"), $params);
                return false;
            }
            if (!filter_var(gethostbyname($host), FILTER_VALIDATE_IP)) {
                $this->failed[$key][] = $this->getMessage($key,'email', __("Email dns check failed"), $params);
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function phone($key, array $params = [])
    {
        /* do not check if empty */
        if (empty($this->data[$key])) {
            return true;
        }

        /**/
        if (!(bool)preg_match('/^\+?[0-9\s\-\(\)]{5,20}$/', $this->data[$key])) {
            $this->failed[$key][] = $this->getMessage($key,'phone', __("Wrong phone format"), $params);
            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function url($key, array $params = [])
    {
        /* do not check if empty */
        if (empty($this->data[$key])) {
            return true;
        }

        /**/
        if (!filter_var($this->data[$key], FILTER_VALIDATE_URL)) {
            $this->failed[$key][] = $this->getMessage($key,'url', __("Url validation failed"), $params);
            return false;
        };
        if (!empty($params['url']) && $params['url'] === 'dns') {
            $tmp = parse_url($this->data[$key]);
            if (!isset($tmp['host'])) {
                $this->failed[$key][] = $this->getMessage($key,'url', __("Url host not found"), $params);
                return false;
            }
            if (!filter_var(gethostbyname($tmp['host']), FILTER_VALIDATE_IP)) {
                $this->failed[$key][] = $this->getMessage($key,'url', __("Url dns check failed"), $params);
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function domain($key, array $params = [])
    {
        /* do not check if empty */
        if (empty($this->data[$key])) {
            return true;
        }

        /**/
        if (!filter_var($this->data[$key],  FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $this->failed[$key][] = $this->getMessage($key,'domain', __("Domain validation failed"), $params);
            return false;
        };
        if (!empty($params['domain']) && $params['domain'] === 'dns') {
            if (!filter_var(gethostbyname($this->data[$key]), FILTER_VALIDATE_IP)) {
                $this->failed[$key][] = $this->getMessage($key,'domain', __("Domain dns check failed"), $params);
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function file($key, array $params = [])
    {
        /* do not check if empty */
        if (empty($this->data[$key]) || empty($this->data[$key]['tmp_name'])) {
            return true;
        }

        /**/
        if (isset($params['type'])) {
            if (empty($this->data[$key]['type'])) {
                $this->failed[$key][] = $this->getMessage($key,'file', __("Type of file is undefined"), $params);
                return false;
            }
            $expected_types = explode(',', $params['type']);
            $found_expected_type = false;
            foreach ($expected_types as $v) {
                $v = trim($v);
                if (strrpos($this->data[$key]['type'], $v)) {
                    $found_expected_type = true;
                    break;
                }
            }
            if (!$found_expected_type) {
                $this->failed[$key][] = $this->getMessage($key,'file', __("Wrong file type. Expected {%type}"), $params);
                return false;
            }
        }
        if (isset($params['max'])) {
            $max_size = $params['max'];
            $cur_size = intval($this->data[$key]['size']);
            if ($cur_size > $max_size) {
                $this->failed[$key][] = $this->getMessage($key,'file', __("File to lage, max size {%max} bytes"), $params);
                return false;
            }
        }

        return true;
    }
}