<?php

namespace Core;

use Core\Exceptions\ValidatorException;

class ValidatorDriver
{
    /** @var array */
    private $failed = [];
    /** @var array */
    private $success = [];
    /** @var array */
    private $data;

    /** @var RequestDriver  */
    private $request;

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
     * @return bool
     * @throws ValidatorException
     */
    public function validate()
    {
        $ret = true;
        foreach ($this->request->rules() as $key => $rule) {

            $methods = [];
            $params = [];
            $arr = explode('|', $rule);
            foreach ($arr as $value) {
                $tmp = explode(':', $value);
                if (isset($tmp[1])) {

                    if (method_exists($this, $tmp[0])) {
                        $methods[] = $tmp[0];
                        for ($i=1; $i<count($tmp); $i++) {
                            $params[$tmp[$i]] = $tmp[$i];
                        }
                    } else {
                        if (in_array($tmp[0], ['min', 'max']))
                            $params[$tmp[0]] = doubleval($tmp[1]);
                        elseif ($tmp[0] === 'length')
                            $params[$tmp[0]] = intval($tmp[1]);
                        else
                            $params[$tmp[0]] = $tmp[1];
                    }

                } else {
                    if (method_exists($this, $tmp[0])) {
                        $methods[] = $tmp[0];
                    } else {
                        throw new ValidatorException("Wrong rule in RequestClass " . get_class($this->request) . ". Validator-method {$tmp[0]}() doesn't exist.");
                    }
                }
            }

            if (empty($methods)) {
                throw new ValidatorException('Wrong rule in RequestClass ' . get_class($this->request));
            }

            $test_var = true;
            foreach ($methods as $method) {
                if (isset($this->data[$key]) || $method === 'required') {
                    //dump($this->data[$key], $method, $this->$method($key, $params));
                    if (!$this->$method($key, $params)) {
                        $ret = false;
                        $test_var = false;
                    }
                }
            }
            if ($test_var && isset($this->data[$key])) {
                $this->success[$key] = $this->data[$key];
            }

        }

        $this->request->setValidated($this->success);
        $this->request->setErrors($this->failed);

        return $ret;
    }

    /**
     * @param string $key
     * @return bool
     */
    private function required($key)
    {
        //dump("required($key)");
        if (!key_exists($key, $this->data)) {
            $this->failed[$key][] = 'value is required';
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
                    $this->failed[$key][] = "value too small, min value {$params['min']}";
                    $ret = false;
                }
                if (isset($params['max']) && $val > $params['max']) {
                    $this->failed[$key][] = "value too big, max value {$params['max']}";
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
            $this->failed[$key][] = 'value must be an int';
            $ret = false;
        }

        return $ret;
    }

    /**
     * @param string $key
     * @param array $params
     * @return bool
     */
    private function double($key, array $params = [])
    {
        //dump("double($key, " . json_encode($params) .")");
        if (preg_match("/^[0-9\.]+$/", $this->data[$key])) {
            $val = doubleval($this->data[$key]);
            if (isset($params['min']) || isset($params['max'])) {
                if (isset($params['min']) && $val < $params['min']) {
                    $this->failed[$key][] = "value too small, min value {$params['min']}";
                    $ret = false;
                }
                if (isset($params['max']) && $val > $params['max']) {
                    $this->failed[$key][] = "value too big, max value {$params['max']}";
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
            $this->failed[$key][] = 'value must be a double';
            $ret = false;
        }

        return $ret;
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
                throw new ValidatorException('Wrong rule in RequestClass ' . get_class($this->request) . "Param length can't correspond with params min or max");
            }

            if (isset($params['min']) && mb_strlen($val) < intval($params['min'])) {
                $this->failed[$key][] = "value too short, min length {$params['min']}";
                $ret = false;
            }
            if (isset($params['max']) && mb_strlen($val) > intval($params['max'])) {
                $this->failed[$key][] = "value too long, max length {$params['max']}";
                $ret = false;
            }
            if (isset($params['length']) && mb_strlen($val) !== intval($params['length'])) {
                $this->failed[$key][] = "value length must be {$params['length']}";
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

}