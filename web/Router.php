<?php

namespace core\web;

use Core;
use core\base\BaseObject;
use core\base\BaseRouter;
use core\components\Controller;
use core\components\Module;
use core\components\UrlRule;
use core\exceptions\NotFoundException;
use core\helpers\FileHelper;
use core\helpers\Inflector;

/**
 * Class Router
 * @package core\web
 */
final class Router extends BaseRouter
{
    public function init(){
        $defaults = isset($this->_config['defaultPath'])
            ? explode('/', $this->_config['defaultPath'])
            : ['Default', 'Index'];
        $this->_defaultController = isset($defaults[0]) ? $defaults[0] : 'Default';
        $this->_defaultAction = isset($defaults[1]) ? $defaults[1] : 'Index';
        $this->_controllersNamespace = isset($this->_config['controllersNamespace'])
            ? $this->_config['controllersNamespace']
            : 'app\\controllers';
        $controllersPath = (substr($this->_controllersNamespace,0,1) != '@')
            ? '@'.$this->_controllersNamespace : $this->_controllersNamespace;
        $this->_controllersPath = FileHelper::normalizePath(Core::getAlias($controllersPath));
        if (isset($this->_config['rules'])){
            foreach ($this->_config['rules'] as $key => $value){
                $this->_rules[] = new UrlRule($this, $key, $value);
            }
        }
        $this->addRules([
            'validator' => ['route' => 'core/validator/validate', 'class' => 'core\components\CoreController'],
            'validator/<action>' => ['route' => 'core/validator/<action>', 'class' => 'core\components\CoreController'],
        ]);
    }

    /**
     * @return Response
     */
    public function route()
    {
        $this->parseRequest();
        foreach ($this->_rules as $rule){
            if ($rule->parseRequest()){
                if (($result = $rule->resolve()) !== true){
                    return $result;
                }
                break;
            }
        }
        $this->parseRoute();
        if ($this->_module == null){
            return $this->defaultResolve();
        } else {
            $this->_moduleClass = App::$instance->getModule($this->module);
            $this->_actionMethod = 'action'.$this->_action;
            return $this->_moduleClass->runAction($this->_actionMethod, App::$instance->request->request);
        }
    }

    protected function parseRequest()
    {
        $request = $_SERVER['REQUEST_URI'];
        $requestParts = explode('?', $request);
        $this->_baseRoute = rtrim($requestParts[0], '/');
        $this->_route = ltrim(str_replace(App::$instance->request->getBaseUrl(), '',$this->_baseRoute), '/');
        $this->parseRoute();
    }
}