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

    public static $CSS_STACK = [];
    public static $JS_STACK = [];

    /**
     * @param string $template
     * @param array $vars
     * @return string
     */
    public function renderView($template, array &$vars = [])
    {
        //try {
            return self::renderPart($template, $vars);
        //} catch (Exception $exception) {
        //    return "";
        //}
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
        
        if (!file_exists( "{$tpl_path}/{$templateName}.php")) {
            throw new HttpNotFoundException("Template <b>{$tpl_path}/{$templateName}.php</b> not exist.", 404);
        }
        
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

        $buffer = str_replace("{%CSS-STACK}", self::prepareCssStack(), $buffer);
        $buffer = str_replace("{%JS-STACK}", self::prepareJsStack(), $buffer);

        foreach ($vars as $key => $val) {
            if (in_array(gettype($val), ['string', 'integer', 'double'])) {
                $buffer = str_replace("{%" . $key . "}", $val, $buffer);
            }
        }

        return $buffer;
    }

    /**
     * @return string
     */
    private static function prepareCssStack()
    {
        $str = "";
        foreach (self::$CSS_STACK as $item) {
            if (strrpos($item, '<style') !== false) {
                $str .= $item . "\n";
            } else {
                if (defined('__WWW_DIR__')) {
                    $filemtime = file_exists(__WWW_DIR__ . "/" . $item)
                        ? filemtime(__WWW_DIR__ . "/" . $item)
                        : time();
                } else {
                    $filemtime = time();
                }
                $str .= '<link href="' . $item . (App::$config->get('IS_DEBUG', false) ? '?v=' . $filemtime : '') . '" rel="stylesheet">' . "\n";
            }
        }
        return $str;
    }

    /**
     * @param string|string[] $css
     * @return void
     */
    public function firstInCssStack($css)
    {
        if (!is_array($css)) {
            $css = [$css];
        }
        self::$CSS_STACK = array_merge($css, self::$CSS_STACK);
    }

    /**
     * @param string|string[] $css
     * @return void
     */
    public function putInCssStack($css)
    {
        if (!is_array($css)) {
            $css = [$css];
        }
        self::$CSS_STACK = array_merge(self::$CSS_STACK, $css);
    }

    /**
     * @return string
     */
    private static function prepareJsStack()
    {
        $str = "";
        foreach (self::$JS_STACK as $item) {
            if (strrpos($item, '<script') !== false) {
                $str .= $item . "\n";
            } else {
                if (defined('__WWW_DIR__')) {
                    $filemtime = file_exists(__WWW_DIR__ . "/" . $item)
                        ? filemtime(__WWW_DIR__ . "/" . $item)
                        : time();
                } else {
                    $filemtime = time();
                }
                $str .= '<script src="' . $item . (App::$config->get('IS_DEBUG', false) ? '?v=' . $filemtime : '') . '"></script>' . "\n";
            }
        }
        return $str;
    }

    /**
     * @param string|string[] $js
     * @return void
     */
    public function firstInJsStack($js)
    {
        if (!is_array($js)) {
            $js = [$js];
        }
        self::$JS_STACK = array_merge($js, self::$JS_STACK);
    }

    /**
     * @param string|string[] $js
     * @return void
     */
    public function putInJsStack($js)
    {
        if (!is_array($js)) {
            $js = [$js];
        }
        self::$JS_STACK = array_merge(self::$JS_STACK, $js);
    }
}