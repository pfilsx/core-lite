<?php

namespace core\web;

use Core;
use core\base\BaseObject;
use core\components\Controller;
use core\components\Module;
use core\components\UrlRule;
use core\exceptions\NotFoundException;
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
 * @property Controller controllerClass
 * @property string param
 */
final class Router extends BaseObject
{
    private $_defaultController = 'Default';

    private $_defaultAction = 'Index';

    private $_controller;

    /**
     * @var Module
     */
    private $_moduleClass;
    /**
     * @var Controller
     */
    private $_controllerClass;
    /**
     * @var string
     */
    private $_actionMethod;

    private $_action;

    private $_module;

    private $_param = null;

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
            $this->_moduleClass = App::$instance->getModule($this->module);
            $this->_actionMethod = 'action'.$this->_action;
            return $this->_moduleClass->runAction($this->_actionMethod, App::$instance->request->request);
        }
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function defaultResolve(){
        $controller = $this->_controller.'Controller';
        $this->_actionMethod = 'action'.$this->_action;
        $controllerPath = Core::getAlias($this->_controllersPath).DIRECTORY_SEPARATOR.$controller.'.php';
        if (file_exists($controllerPath)) {
            $className = $this->_controllersNamespace.'\\' . $controller;
            $this->_controllerClass = new $className();
            return $this->_controllerClass->runAction($this->_actionMethod, App::$instance->request->request);
        } else {
            if (CRL_DEBUG === true) {
                throw new NotFoundException("Controller {$this->_controller} does not exist");
            } else {
                return App::$instance->getResponse()->redirect('/');
            }
        }
    }

    public function parseRequest()
    {
        $request = $_SERVER['REQUEST_URI'];
        $requestParts = explode('?', $request);
        $this->_baseRoute = rtrim($requestParts[0], '/');
        $this->_route = ltrim(str_replace(App::$instance->request->getBaseUrl(), '',$this->_baseRoute), '/');
        $this->parseRoute();
    }


    public function parseRoute()
    {
        $this->clearData();
        $pathParts = explode('/', $this->_route);
        $partsCount = count($pathParts);
        if ($partsCount == 3){
            $this->_module = Inflector::id2camel((!empty($pathParts[0]) ? $pathParts[0] : ''));
            $this->_controller = Inflector::id2camel((!empty($pathParts[1]) ? $pathParts[1] : $this->_defaultController));
            $this->_action = Inflector::id2camel((!empty($pathParts[2]) ? $pathParts[2] : $this->_defaultAction));
        } elseif ($partsCount == 2) {
            $this->_controller = Inflector::id2camel((!empty($pathParts[0]) ? $pathParts[0] : $this->_defaultController));
            $this->_action = Inflector::id2camel((!empty($pathParts[1]) ? $pathParts[1] : $this->_defaultAction));
        } elseif ($partsCount == 1){
            $this->_controller = Inflector::id2camel((!empty($pathParts[0]) ? $pathParts[0] : $this->_defaultController));
            $this->_action = $this->_defaultAction;
        } else {
            $this->_module = Inflector::id2camel((!empty($pathParts[0]) ? $pathParts[0] : ''));
            $this->_controller = Inflector::id2camel((!empty($pathParts[1]) ? $pathParts[1] : $this->_defaultController));
            $this->_action = Inflector::id2camel((!empty($pathParts[2]) ? $pathParts[2] : $this->_defaultAction));
            $this->_param = (!empty($pathParts[3]) ? $pathParts[3] : null);
        }
    }

    private function clearData(){
        $this->_module = $this->_controller = $this->_action
            = $this->_moduleClass = $this->_actionMethod = $this->_controllerClass = null;
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

    public function getParam(){
        return $this->_param;
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

    public function getControllerClass(){
        return $this->_controllerClass;
    }
    public function setControllerClass($controller){
        $this->_controllerClass = $controller;
    }
    public function getActionMethod(){
        return $this->_actionMethod;
    }
    public function getModuleClass(){
        return $this->_moduleClass;
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