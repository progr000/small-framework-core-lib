<?php

namespace Core;

use finfo;


class ResponseDriver
{
    /** @var bool */
    private $isSent;
    /** @var int */
    private $status;
    /** @var array */
    private $headers = [];
    /** @var string */
    private $body;
    /** @var string|null */
    private $debug_data;
    /** @var bool */
    private $returnAsJson;
    /** @var bool */
    private $returnAsFile;
    /** @var null|string */
    private $fileAppType;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->status = 200;
        $this->body = '';
        $this->isSent = false;
        $this->returnAsJson = false;
        $this->returnAsFile = false;
    }

    /**
     * @param string $name
     * @param string $value
     * @param array $options
     * @return $this
     */
    public function setCookie($name, $value, array $options = [])
    {
        setcookie(
            $name,
            $value,
            isset($options['expire']) ? $options['expire'] : 0,
            isset($options['path']) ? $options['path'] : "",
            isset($options['domain']) ? $options['domain'] : "",
            isset($options['secure']) ? $options['secure'] : false,
            isset($options['httponly']) ? $options['httponly'] : false
        );
        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeader(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * @param int $status
     * @param array $additional_headers
     * @return $this
     */
    public function setStatus($status, array $additional_headers = [])
    {
        $this->status = $status;
        if (sizeof($additional_headers)) {
            $this->headers = array_merge($this->headers, $additional_headers);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isJson()
    {
        return $this->returnAsJson;
    }

    /**
     * @return $this
     */
    public function asJson()
    {
        $this->returnAsJson = true;
        $this->returnAsFile = false;
        $this->setHeader(['Content-Type: application/json']);
        return $this;
    }

    /**
     * @param string $filename
     * @param bool $inlile
     * @param string $application_type
     * @return $this
     */
    public function asFile($filename, $inlile = false, $application_type = null)
    {
        /* unset Content-Type header if exists */
        foreach ($this->headers as $k => $v) {
            if (strrpos(mb_strtolower($v), 'content-type:') !== false) {
                unset($this->headers[$k]);
            }
        }

        /**/
        $this->returnAsFile = true;
        $this->returnAsJson = false;
        $this->fileAppType = $application_type;
        if ($inlile)
            $disposition = "inline";
        else
            $disposition = "attachment";
        $this->setHeader([
            'Content-Disposition: ' . $disposition . '; filename="' . basename($filename) . '"',
            'Cache-Control: no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0'
        ]);
        return $this;
    }

    /**
     * @param mixed $body
     * @return $this
     */
    public function setBody($body)
    {
        if ($this->returnAsJson) {
            $this->body = json_encode($body);
        } elseif ($this->returnAsFile) {
            if (!$this->fileAppType)
                $this->fileAppType = (new finfo(FILEINFO_MIME))->buffer($body);
            $this->setHeader(["Content-Type: {$this->fileAppType}"]);
            $this->body = $body;
        } else {
            $this->body = $body;
        }
        return $this;
    }

    /**
     * @param string $debug_data
     * @return $this
     */
    public function setDebugData($debug_data)
    {
        $this->debug_data = $debug_data;
        return $this;
    }

    /**
     * @param int $status
     * @return string
     */
    public static function getHeaderForResponseStatus($status)
    {
        switch ($status) {
            case 200:
                $h = "OK";
                break;
            case 400:
                $h = "Bad Request";
                break;
            case 401:
                $h = "Unauthorized";
                break;
            case 403:
                $h = "Forbidden";
                break;
            case 404:
                $h = "Not Found";
                break;
            case 405:
                $h = "Method Not Allowed";
                break;
            case 500:
                $h = "Internal Server Error";
                break;
            case 503:
                $h = "Service Unavailable";
                break;
            default:
                $h = "Unknown";
                break;
        }
        return "HTTP/1.1 {$status} {$h}";
    }

    /**
     * @return void
     */
    private function prepareHeaders()
    {
        header(self::getHeaderForResponseStatus($this->status));
        foreach ($this->headers as $v) {
            header($v);
        }
    }

    /**
     * @return void
     */
    public function send()
    {
        if ($this->isSent) {
            return;
        }

        /* all headers */
        $this->setHeader(['Server: dont-worry-be-happy']);
        $this->setHeader(['X-Powered-By: dont-worry-be-happy']);
        $this->prepareHeaders();

        /* If debug is ON then will be show all dump()
         * and another debug output before output base-content
         * Or, you can add %%%DEBUG-DATA%%% in your template,
         * then all debug output will be shown on that place
         * instead %%%DEBUG-DATA%%% */
        if (defined('IS_DEBUG') && IS_DEBUG && !is_null($this->debug_data)) {
            if (strrpos($this->body, "%%%DEBUG-DATA%%%") !== false) {
                $this->body = str_replace("%%%DEBUG-DATA%%%", $this->debug_data, $this->body);
            } else {
                echo $this->debug_data;
            }
        } else {
            $this->body = str_replace("%%%DEBUG-DATA%%%", '<br />', $this->body);
        }

        /* base-content */
        echo $this->body;
        $this->isSent = true;
        exit;
    }

    /**
     * @param string $url
     * @param int $status
     * @return $this
     */
    public function redirect($url, $status = 302)
    {
        $this->headers = [];
        $this->setHeader(["Location: {$url}"])
            ->setStatus($status);
        return $this;
    }

    /**
     * @return $this
     */
    public function goHome()
    {
        $this->headers = [];
        $this->setHeader(["Location: " . App::$route->getRoot()])
            ->setStatus(302);
        return $this;
    }

    /**
     * @return $this
     */
    public function goBack()
    {
        $this->headers = [];
        $referer = App::$route->getReferer();
        if (!is_null($referer)) {
            $this->setHeader(["Location: " . $referer]);
        } else {
            $this->setHeader(["Location: " . App::$route->getRoot()]);
        }
        $this->setStatus(302);

        return $this;
    }
}