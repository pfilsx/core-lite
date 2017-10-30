<?php


namespace core\console;


use core\base\BaseObject;

/**
 * @property array args
 */
class Request extends BaseObject
{
    private $_args;

    private $_route;

    public function init(){
        $args = $_SERVER['argv'];
        array_shift($args);
        if (empty($args)){
            die();
        }
        $this->_route = array_shift($args);
        foreach ($args as $arg){
            if (substr($arg, 0,2) == '--'){
                $argParts = explode('=',substr($arg,2));
                if (count($argParts) != 2){
                    throw new \Exception("Exception in {$argParts[0]} parameter. Missed value");
                }
                $this->_args[$argParts[0]] = $argParts[1];
            } else if (substr($arg,0,1) == '-'){
                $this->_args[substr($arg,1)] = true;
            } else {
                $this->_args[] = $arg;
            }

        }
    }

    public function getArgs(){
        return $this->_args;
    }
    public function getRoute(){
        return $this->_route;
    }
}