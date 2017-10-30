<?php


namespace core\console;


use Core;
use core\base\BaseObject;

class Router extends BaseObject
{
    private $_controller;
    private $_action;

    private $_controllersPath;
    private $_controllersNamespace = 'core\console\controllers';

    public function init(){
        $routeParts = explode('/',App::$instance->request->getRoute());
        $this->_controller = ucfirst($routeParts[0]).'Controller';
        $this->_action = 'action'.ucfirst(isset($routeParts[1]) ? $routeParts[1] : 'index');
        $this->_controllersPath = Core::getAlias('@crl/console/controllers');
    }

    public function route(){
        $controllerPath = $this->_controllersPath.DIRECTORY_SEPARATOR.$this->_controller.'.php';
        if (file_exists($controllerPath)) {
            $className = $this->_controllersNamespace.'\\' . $this->_controller;
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
                    call_user_func_array([$controller, 'beforeAction'],$_params_);
                    $content = call_user_func_array([$controller, $this->_action],$_params_);
                } else {
                    $controller->beforeAction();
                    $content = $controller->{$this->_action}();
                }
                return $content;
            } else {
                if (CRL_DEBUG === true){
                    throw new \Exception("Action {$this->_action} does not exist in {$this->_controller}");
                } else {
                    return 1;
                }
            }
        } else {
            if (CRL_DEBUG === true) {
                throw new \Exception("Controller {$this->_controller} does not exist");
            } else {
                return 1;
            }
        }
    }
}