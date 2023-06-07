<?php

namespace Core;

use Core\Exceptions\IntegrityException;
use Exception;
use Core\Exceptions\HttpNotFoundException;


/**
 * Class View
 * @package core\View
 */
class ViewDriver
{
    /** @var string */
    public static $layout = 'layouts/main';

    /**
     * @param string $template
     * @param array $vars
     * @return string
     */
    public function renderView($template, array &$vars = [])
    {
        try {
            return self::renderPart($template, $vars);
        } catch (Exception $exception) {
            return "";
        }
    }

    /**
     * @param string $templateName
     * @param array $vars
     * @return string
     * @throws IntegrityException
     * @throws HttpNotFoundException
     */
    public static function renderPart($templateName, array &$vars = [])
    {
        $tpl_path = config('template-path');
        if (!$tpl_path) {
            throw new IntegrityException('template-path is required parameter in config (must be a string, real path to dir with templates)', 400);
        }
        
        if (!file_exists( "{$tpl_path}/{$templateName}.php")) 
            throw new HttpNotFoundException("Template <b>{$tpl_path}/{$templateName}.php</b> not exist.", 404);
        
        ob_start();
        ob_implicit_flush(false);
        $vars['view'] = new self();
        extract($vars, EXTR_OVERWRITE);
        include("{$tpl_path}/{$templateName}.php");
        $buffer = ob_get_contents();
        ob_get_clean();

        foreach ($vars as $key => $val) {
            if (in_array(gettype($val), ['string', 'integer', 'double'])) {
                $buffer = str_replace("{%" . $key . "}", $val, $buffer);
            }
        }

        return $buffer;
    }

    /**
     * @param string $templateName
     * @param array $vars
     * @param null $layout
     * @return string
     * @throws IntegrityException
     * @throws HttpNotFoundException
     */
    public static function render($templateName, array $vars = [], $layout = null)
    {
        $tpl_path = config('template-path');
        if (!$tpl_path) {
            throw new IntegrityException('template-path is required parameter in config (must be a string, real path to dir with templates)', 400);
        }

        if (is_null($layout))
            $layout = self::$layout;
        
        if (!file_exists( "{$tpl_path}/{$layout}.php")) 
            throw new HttpNotFoundException("Layout <b>{$tpl_path}/{$layout}.php</b> not found.", 404);
        
        $content = self::renderPart($templateName, $vars);

        ob_start();
        ob_implicit_flush(false);
        $vars['view'] = new self();
        extract($vars, EXTR_OVERWRITE);
        include("{$tpl_path}/{$layout}.php");
        $buffer = ob_get_contents();
        ob_end_clean();

        foreach ($vars as $key => $val) {
            if (in_array(gettype($val), ['string', 'integer', 'double'])) {
                $buffer = str_replace("{%" . $key . "}", $val, $buffer);
            }
        }

        return $buffer;
    }
}