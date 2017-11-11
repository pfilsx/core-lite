<?php

namespace core\base;

use Core;
use core\components\Controller;
use core\helpers\FileHelper;
use core\helpers\Inflector;

final class Router extends BaseObject
{
    private $_defaultController = 'Default';

    private $_defaultAction = 'Index';

    private $_controller;

    private $_action;

    private $_rules = [];

    private $_controllersPath;
    private $_controllersNamespace;

    public function init(){
        $defaults = isset($this->_config['defaultPath'])
            ? explode('/', $this->_config['defaultPath'])
            : ['Default', 'Index'];
        $this->_defaultController = isset($defaults[0]) ? $defaults[0] : 'Default';
        $this->_defaultAction = isset($defaults[1]) ? $defaults[1] : 'Index';
        if (isset($this->_config['controllersPath'])){
            $this->_controllersPath = FileHelper::normalizePath($this->_config['controllersPath']);
            $this->_controllersNamespace = str_replace(['@app', '/', '-'], ['', '\\', '_'], $this->_config['controllersPath']);
        } else {
            $this->_controllersPath = '@app'.DIRECTORY_SEPARATOR.'controllers';
            $this->_controllersNamespace = '\\app\\controllers';
        }
        if (isset($this->_config['rules'])){
            $this->_rules = $this->_config['rules'];
        }
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function route()
    {
        $this->parseRequest();
        $controllerPath = Core::getAlias($this->_controllersPath).DIRECTORY_SEPARATOR.$this->_controller.'.php';
        if (file_exists($controllerPath)) {
            $className = $this->_controllersNamespace.'\\' . $this->_controller;
            /**
             * @var Controller $controller
             */
            $controller = new $className();
            if (method_exists($controller, $this->_action)) {
                $ref = new \ReflectionMethod($controller, $this->_action);
                if (!empty($ref->getParameters())) {
                    $_params_ = [];
                    foreach ($ref->getParameters() as $param) {
                        if (array_key_exists($param->name, App::$instance->request->request)) {
                            $_params_[$param->name] = App::$instance->request->request[$param->name];
                        } else if (!$param->isOptional()) {
                            throw new \Exception("Required parameter $param->name is missed");
                        } else {
                            $_params_[$param->name] = $param->getDefaultValue();
                        }
                    }
                    $content = $controller->beforeAction($this->_action);
                    if ($content !== false){
                        $content = call_user_func_array([$controller, $this->_action],$_params_);
                    }
                } else {
                    $content = $controller->beforeAction($this->_action);
                    if ($content !== false){
                        $content = $controller->{$this->_action}();
                    }
                }
                if ($content instanceof Response){
                    return $content;
                } else {
                    $response = App::$instance->response;
                    if ($content !== null){
                        $response->data = $content;
                    }
                    return $response;
                }
            } else {
                if (CRL_DEBUG === true){
                    throw new \Exception("Action {$this->_action} does not exist in {$this->_controller}");
                } else {
                    return App::$instance->getResponse()->redirect('/');
                }
            }
        } else {
            if (CRL_DEBUG === true) {
                throw new \Exception("Controller {$this->_controller} does not exist");
            } else {
                return App::$instance->getResponse()->redirect('/');
            }
        }
    }

    private function parseRequest()
    {
        $request = $_SERVER['REQUEST_URI'];
        $requestParts = explode('?', $request);
        $route = str_replace(App::$instance->request->getBaseUrl().'/', '',$requestParts[0]);
        if (array_key_exists($route, $this->_rules)){
            $route = $this->_rules[$route];
        }
        $this->getControllerAndAction($route);
    }


    private function getControllerAndAction($request)
    {
        $pathParts = explode('/', $request);
        if (count($pathParts) == 3){
            $this->_controller = Inflector::id2camel(ucfirst(!empty($pathParts[1]) ? $pathParts[1] : $this->_defaultController)) . 'Controller';
            $this->_action = 'action' . Inflector::id2camel(ucfirst(!empty($pathParts[2]) ? $pathParts[2] : $this->_defaultAction));
        } else {
            $this->_controller = Inflector::id2camel(ucfirst(!empty($pathParts[0]) ? $pathParts[0] : $this->_defaultController)) . 'Controller';
            $this->_action = 'action' . Inflector::id2camel(ucfirst(!empty($pathParts[1]) ? $pathParts[1] : $this->_defaultAction));
        }
    }

    public function getAction(){
        return $this->_action;
    }

    public function getController(){
        return $this->_controller;
    }
}