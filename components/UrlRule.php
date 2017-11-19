<?php


namespace core\components;


use core\base\BaseObject;
use core\base\Router;

class UrlRule extends BaseObject
{
    /**
     * @var string
     */
    private $_pattern;
    /**
     * @var string|array
     */
    private $_options;

    /**
     * @var Router
     */
    private $_router;

    /**
     * UrlRule constructor.
     * @param Router $router
     * @param string $pattern
     * @param array $options
     */
    public function __construct($router, $pattern, array $options)
    {
        $this->_router = $router;
        $this->_pattern = $pattern;
        $this->_options = $options;
        parent::__construct([]);
    }

    /**
     * @return bool
     */
    public function parseRequest(){
        if (strtr($this->_pattern, [
                '<module>' => $this->_router->module,
                '<controller>' => $this->_router->controller,
                '<action>' => $this->_router->action
            ]) === $this->_router->route){
            return true;
        }
        return false;
    }

    public function resolve(){
        if (isset($this->_options['route'])){
            $this->_router->route = strtr($this->_options['route'], [
                '<controller>' => $this->_router->controller,
                '<action>' => $this->_router->action
            ]);
        }
    }

}