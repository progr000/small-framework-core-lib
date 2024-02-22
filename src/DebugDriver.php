<?php

namespace Core;

use stdClass;

class DebugDriver extends stdClass
{
    const DEBUG_CSS_FILE = __DIR__ . '/Assets/debug-panel/panel.css';
    const DEBUG_JS_FILE = __DIR__ . '/Assets/debug-panel/panel.js';
    const DEBUG_HTML_FILE = __DIR__ . '/Assets/debug-panel/panel.php';

    /** @var self */
    private static $instance;

    /** @var array */
    private $sqlLog = [];

    /** @var array */
    private $wgetLog = [];

    /** @var array */
    private $sessionData = [];

    /** @var array */
    private $routeData = [];

    /** @var array */
    private $requestData = [];

    /** @var array */
    private $viewData = [];

    /** @var array */
    private $timingData = [];


    /**
     * @return DebugDriver
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor, load data into itself from config
     */
    private function __construct()
    {
        $this->timingData['BootStart'] = microtime(true);
    }

    /**
     * @param string $container
     * @param array|string $data
     * @return void
     */
    public function _set($container, $data)
    {
        if (config('IS_DEBUG', false)) {
            if (isset($this->$container)) {
                if (!is_array($data)) {
                    $this->$container = array_merge($this->$container, [$data]);
                } else {
                    $this->$container = array_merge($this->$container, $data);
                }
            }
        }
    }

    /**
     * @param string $container
     * @return mixed
     */
    public function _get($container)
    {
        if (config('IS_DEBUG', false)) {
            return isset($this->$container)
                ? $this->$container
                : null;
        } else {
            return ["This works only in debug mode, please put IS_DEBUG => true into config/main.php"];
        }
    }

    /**
     * @return array
     */
    public function getSqlLog()
    {
        return $this->_get('sqlLog');
    }

    /**
     * @return array
     */
    public function getSessionData()
    {
        return $_SESSION;
    }

    /**
     * @return array
     */
    public function getRouteData()
    {
        return $this->_get('routeData');
    }

    /**
     * @return array
     */
    public function getViewData()
    {
        return $this->_get('viewData');
    }

    public function setBootTiming()
    {
        $this->timingData['BootFinish'] = microtime(true);
        $this->timingData['AppStart'] = microtime(true);
    }

    /**
     * @return void
     */
    public function setAppTiming()
    {
        $this->timingData['AppFinish'] = microtime(true);
    }

    public function showDebugPanel()
    {
        if (config('SHOW_DEBUG_PANEL', false)) {
            return
                $this->getPanelCss() .
                $this->getPanelHtml() .
                $this->getPanelJs();
        }

        return '';
    }

    /**
     * @return string
     */
    private function getPanelCss()
    {
        if (file_exists(self::DEBUG_CSS_FILE)) {
            return "<style>" . minimize(file_get_contents(self::DEBUG_CSS_FILE)) . "</style>";
            //return "<style>" . file_get_contents(self::DEBUG_CSS_FILE) . "</style>";
        }

        return '';
    }

    /**
     * @return string
     */
    private function getPanelJs()
    {
        if (file_exists(self::DEBUG_JS_FILE)) {
            return "<script>" . minimize(file_get_contents(self::DEBUG_JS_FILE)) . "</script>";
            //return "<script>" . file_get_contents(self::DEBUG_JS_FILE) . "</script>";
        }

        return '';
    }

    /**
     * @return string
     */
    private function getPanelHtml()
    {
        if (file_exists(self::DEBUG_JS_FILE)) {

            ob_start();
            ob_implicit_flush(false);
            $__sql = $this->getSqlLog();
            $__session = $this->getSessionData();
            $__route = $this->getRouteData();
            $__view = $this->getViewData();
            $__timeline = $this->timingData;
            $__memory = memory_get_usage();
            include(self::DEBUG_HTML_FILE);
            $buffer = ob_get_contents();
            ob_end_clean();

            return $buffer;
        }

        return '';
    }
}