<?php


namespace core\exceptions;


use Throwable;

class WarningException extends \Exception
{
    public function __construct($message = "", $code = 0, $file = null, $line = null, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        if ($file !== null){
            $ref = new \ReflectionProperty('Exception', 'file');
            $ref->setAccessible(true);
            $ref->setValue($this, $file);
        }
        if ($line !== null){
            $ref = new \ReflectionProperty('Exception', 'line');
            $ref->setAccessible(true);
            $ref->setValue($this, $line);
        }
    }
}