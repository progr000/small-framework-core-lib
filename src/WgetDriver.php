<?php

namespace Core;

class WgetDriver
{
    /** @var int */
    private static $instancesCount = 0;
    /** @var string[] */
    private static $allowed_methods = ['GET', 'POST', 'HEAD', 'PUT', 'PATCH', 'DELETE'];
    /** @var array */
    private $send_headers = [];
    /** @var bool */
    private $flagAsJson = false;

    /** @var */
    private $curl;

    /**
     * Constructor
     */
    private function __construct()
    {
        self::$instancesCount++;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        self::$instancesCount--;
    }

    /**
     * Create new instance
     * @return WgetDriver
     */
    public static function init()
    {
        $instance = new self();
        $instance->curl = curl_init();
        //curl_reset($instance->curl);
        curl_setopt($instance->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($instance->curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($instance->curl, CURLOPT_VERBOSE, true);
        curl_setopt($instance->curl, CURLOPT_HEADER, true);
        curl_setopt($instance->curl,CURLOPT_ENCODING , "gzip");  // TODO: discover this
        if (config('IGNORE_SSL_ERRORS', false)) {
            curl_setopt($instance->curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($instance->curl, CURLOPT_SSL_VERIFYPEER, 0);
        }
        //curl_setopt($instance->curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($instance->curl, CURLOPT_FRESH_CONNECT, true);
        (stripos(PHP_OS, 'win') !== false) && curl_setopt($instance->curl, CURLOPT_SSL_VERIFYPEER, false); // fucking windows for repair https://www.saotn.org/dont-turn-off-curlopt_ssl_verifypeer-fix-php-configuration/
        return $instance;
    }

    /**
     * @param string $bearerHash
     * @param array $additional_headers
     * @return $this
     */
    public function setBearerAutorisation($bearerHash, array $additional_headers=[])
    {
        $this->setHeaders(["Authorization: Bearer $bearerHash"]);
        $this->setHeaders($additional_headers);
        return $this;
    }

    /**
     * Set headers that request is json
     * @return $this
     */
    public function asJson()
    {
        $this->flagAsJson = true;
        $this->setHeaders(['Content-Type: application/json']);
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    private function setUrl($url)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        return $this;
    }

    /**
     * @param string $method
     * @return $this
     */
    private function setMethod($method)
    {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        return $this;
    }

    /**
     * @param array|string|null $data
     * @return $this
     */
    private function setData($data)
    {
        if (empty($data)) {
            $data = [];
        }
        if ($this->flagAsJson) {
            $data = json_encode($data);
        }
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $prepared_headers = [];
        foreach ($headers as $k => $v) {
            if (gettype($k) === 'string') {
                $prepared_headers[] = "{$k}: {$v}";
            } else {
                $prepared_headers[] = $v;
            }
        }
        $this->send_headers = array_merge($this->send_headers, $prepared_headers);
        return $this;
    }

    /**
     * @param int $port
     * @return $this
     */
    public function setPort($port)
    {
        curl_setopt($this->curl, CURLOPT_PORT, $port);
        return $this;
    }

    /**
     * Execute curl and process response
     * @return WgetResponse
     */
    private function exec()
    {
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->send_headers);
        return new WgetResponse($this->curl);
    }

    /**
     * @param string $url
     * @param null|string|array $get_data
     * @param null|string|array $body_data
     * @return WgetResponse
     */
    public function get($url, $get_data = null, $body_data = null)
    {
        if (!is_null($get_data)) {
            $url = trim(trim($url, '?'), '&');
            if (is_string($get_data)) {
                $str = trim(trim($get_data, '?'), '&');
            } elseif (is_array($get_data)) {
                $str = "";
                foreach ($get_data as $k => $v) {
                    if (!is_array($v) && !is_object($v)) {
                        $str .= $k . '=' . urlencode($v) . '&';
                    } elseif (is_array($v)) {
                        foreach ($v as $k2 => $v2) {
                            $str .= $k . '=' . urlencode($v2) . '&';
                        }
                    }
                }
            }
            /**/
            if (strrpos($url, '?') === false) {
                $url .= '?' . $str;
            } else {
                $url .= '&' . $str;
            }
        }

        return $this
            ->setUrl($url)
            ->setMethod('GET')
            ->setData($body_data)
            ->exec();
    }

    /**
     * @param string $url
     * @param array|string|null $data
     * @return WgetResponse
     */
    public function post($url, $data = null)
    {
        return $this
            ->setUrl($url)
            ->setMethod('POST')
            ->setData($data)
            ->exec();
    }

    /**
     * @param string $url
     * @param array|string|null $data
     * @return WgetResponse
     */
    public function put($url, $data = null)
    {
        return $this
            ->setUrl($url)
            ->setMethod('PUT')
            ->setData($data)
            ->exec();
    }

    /**
     * @param string $url
     * @param array|string|null $data
     * @return WgetResponse
     */
    public function patch($url, $data = null)
    {
        return $this
            ->setUrl($url)
            ->setMethod('PATCH')
            ->setData($data)
            ->exec();
    }

    /**
     * @param string $url
     * @param array|string|null $data
     * @return WgetResponse
     */
    public function delete($url, $data = null)
    {
        return $this
            ->setUrl($url)
            ->setMethod('DELETE')
            ->setData($data)
            ->exec();
    }
}
