<?php
namespace Core\Exceptions;

use  Exception;

class ConfigException extends Exception
{
    public function __construct($message = "", $code = 0, $previous = null)
    {
        $code === 0 && $code = 400;
        parent::__construct($message, $code, $previous);
    }
}
