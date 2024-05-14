<?php

namespace Core;

use Exception;

/**
 * Class WgetResponse used by WgetDriver
 * for convenient manage response
 */
class WgetResponse
{
    /** @var string|false */
    private $body = false;
    /** @var string|false */
    private $request_headers = false;
    /** @var string|false */
    private $response_headers = false;
    /** @var int|false */
    private $status = false;
    /** @var string */
    private $url;
    /** @var array */
    private $errors;

    /**
     * Constructor
     * execute curl and prepare response
     * @param $curl_resource
     */
    public function __construct($curl_resource)
    {
        try {
            $response = curl_exec($curl_resource);
            $this->request_headers = curl_getinfo($curl_resource, CURLINFO_HEADER_OUT);
            if ($response === false) {
                $this->errors[] = 'Curl return false';
                $this->errors[] = curl_error($curl_resource);
                $this->errors[] = curl_errno($curl_resource);
                return;
            }
            $this->url = curl_getinfo($curl_resource, CURLINFO_EFFECTIVE_URL);
            $this->status = intval(curl_getinfo($curl_resource, CURLINFO_HTTP_CODE));
            $header_size = curl_getinfo($curl_resource, CURLINFO_HEADER_SIZE);
            $this->response_headers = mb_substr($response, 0, $header_size);
            $this->body = mb_substr($response, $header_size);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    /**
     * Return error-stack
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function url()
    {
        return $this->url;
    }

    /**
     * Return body of response
     * @return false|string
     */
    public function body()
    {
        return $this->body;
    }

    /**
     * Save response as file
     * @param $file_name
     * @return bool
     */
    public function save($file_name)
    {
        $dir = dirname($file_name);
        if (file_exists($dir) && is_dir($dir) && is_writable($dir)) {
            file_put_contents($file_name, $this->body());
            return true;
        } else {
            $this->errors[] = "Can't write into this path";
            return false;
        }
    }

    /**
     * Return response as json
     * @param null $key
     * @param mixed $default
     * @return false|mixed
     */
    public function json($key = null, $default = null)
    {
        if (gettype($this->body) === 'string') {
            $decoded = json_decode((string) $this->body, true);

            if (is_null($key)) {
                return $decoded;
            }

            return isset($decoded[$key])
                ? $decoded[$key]
                : $default;
        }

        return $default;
    }

    /**
     * Return all request-headers of response
     * @return false|string[]
     */
    public function request_headers()
    {
        return explode("\n", $this->request_headers);
    }

    /**
     * Return all response-headers
     * @return false|string[]
     */
    public function response_headers()
    {
        return explode("\n", $this->response_headers);
    }

    /**
     * Return status code of response
     * @return false|int
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Return all cookies of response
     * @return array
     */
    public function cookies()
    {
        /** TODO */
        return [];
    }
}
