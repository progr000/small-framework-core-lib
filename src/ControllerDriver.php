<?php

namespace Core;


class ControllerDriver
{
    public $layout;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->layout = "layouts/main";
    }

    /**
     * @param string $url
     * @param int $status
     * @return ResponseDriver
     */
    protected function redirect($url, $status = 302)
    {
        return App::$response->redirect($url, $status);
    }

    /**
     * @param string $template
     * @param array $vars
     * @return string
     */
    protected function render($template, array $vars = [])
    {
        try {
            return ViewDriver::render($template, $vars, $this->layout);
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @param string $template
     * @param array $vars
     * @return string
     */
    protected function renderPart($template, array &$vars = [])
    {
        try {
            return ViewDriver::renderPart($template, $vars);
        } catch (\Exception $e) {
            return "";
        }
    }
}