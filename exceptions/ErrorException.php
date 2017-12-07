<?php


namespace core\exceptions;


class ErrorException extends \Exception
{
    public function __construct($message = "", $code = 500, $file = null, $line = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        if ($file !== null){
            $ref = new \ReflectionProperty('Exception', 'file');
            $ref->setAccessible(true);
            $ref->setValue($this, $file);
        }
        $ref = new \ReflectionProperty('Exception', 'line');
        $ref->setAccessible(true);
        $ref->setValue($this, $line);
    }
}