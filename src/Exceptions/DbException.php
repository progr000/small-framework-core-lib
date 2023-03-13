<?php
namespace Core\Exceptions;

use  Exception;

class DbException extends Exception
{
    public function __construct($message = "", $code = 0, $previous = null)
    {
        $code === 0 && $code = 500;
        parent::__construct($message, $code, $previous);
    }
}
