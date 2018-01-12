<?php


namespace core\console;


use core\base\BaseObject;

/**
 * @property array args
 * @property array request
 * @property string route
 * @property string userLanguage
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
            if (substr($arg, 0,1) == '-'){
                $argParts = explode('=',substr($arg,1));
                if (count($argParts) == 2){
                    $this->_args[$argParts[0]] = $argParts[1];
                } elseif (count($argParts) == 1){
                    $this->_args[$argParts[0]] = true;
                } else {
                    throw new \Exception("Exception in {$arg} parameter. Unknown parameter type");
                }
            } else {
                $this->_args[] = $arg;
            }
        }
    }

    public function getArgs(){
        return $this->_args;
    }

    public function getRequest(){
        return $this->_args;
    }

    public function getRoute(){
        return $this->_route;
    }

    public function getUserLanguage(){
        return 'en';
    }
}