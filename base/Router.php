<?php

namespace core\base;

use Core;
use core\components\Controller;
use core\components\Module;
use core\components\UrlRule;
use core\helpers\FileHelper;
use core\helpers\Inflector;

/**
 * Class Router
 * @package core\base
 *
 * @property string controller
 * @property string action
 * @property string route
 * @property string baseRoute
 * @property string module
 */
final class Router extends BaseObject
{
    private $_defaultController = 'Default';

    private $_defaultAction = 'Index';

    private $_controller;

    private $_action;

    private $_module;

    private $_route;

    private $_baseRoute;

    /**
     * @var UrlRule[]
     */
    private $_rules = [];

    private $_controllersPath;
    private $_controllersNamespace;

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
     * @throws \Exception
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
            $module = App::$instance->getModule($this->module);
            $action = 'action'.$this->_action;
            return $module->runAction($action, App::$instance->request->request);
        }
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function defaultResolve(){
        $controller = $this->_controller.'Controller';
        $action = 'action'.$this->_action;
        $controllerPath = Core::getAlias($this->_controllersPath).DIRECTORY_SEPARATOR.$controller.'.php';
        if (file_exists($controllerPath)) {
            $className = $this->_controllersNamespace.'\\' . $controller;
            /**
             * @var Controller $controllerClass
             */
            $controllerClass = new $className();
            return $controllerClass->runAction($action, App::$instance->request->request);
        } else {
            if (CRL_DEBUG === true) {
                throw new \Exception("Controller {$this->_controller} does not exist");
            } else {
                return App::$instance->getResponse()->redirect('/');
            }
        }
    }

    public function parseRequest()
    {
        $request = $_SERVER['REQUEST_URI'];
        $requestParts = explode('?', $request);
        $this->_baseRoute = $requestParts[0];
        $this->_route = str_replace(App::$instance->request->getBaseUrl().'/', '',$requestParts[0]);
        $this->parseRoute();
    }


    public function parseRoute()
    {
        $pathParts = explode('/', $this->_route);
        if (count($pathParts) == 3){
            $this->_module = Inflector::id2camel((!empty($pathParts[0]) ? $pathParts[0] : ''));
            $this->_controller = Inflector::id2camel((!empty($pathParts[1]) ? $pathParts[1] : $this->_defaultController));
            $this->_action = Inflector::id2camel((!empty($pathParts[2]) ? $pathParts[2] : $this->_defaultAction));
        } else {
            $this->_controller = Inflector::id2camel((!empty($pathParts[0]) ? $pathParts[0] : $this->_defaultController));
            $this->_action = Inflector::id2camel((!empty($pathParts[1]) ? $pathParts[1] : $this->_defaultAction));
        }
    }

    public function getAction(){
        return Inflector::camel2id($this->_action);
    }

    public function getController(){
        return Inflector::camel2id($this->_controller);
    }

    public function getModule(){
        return Inflector::camel2id($this->_module);
    }

    public function getRoute(){
        return $this->_route;
    }
    public function getBaseRoute(){
        return $this->_baseRoute;
    }

    public function setRoute($value){
        $this->_route = $value;
    }

    /**
     * Add a custom rule to routing
     * @param array $rules
     *
     * @usage
     * App::$instance->router->addRules(['my-custom-path' => ['route' => 'default/route']])
     */
    public function addRules(array $rules){
        foreach ($rules as $pattern => $options){
            $this->_rules[] = new UrlRule($this, $pattern, $options);
        }
    }
}