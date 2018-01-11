<?php


namespace core\base;


use Core;
use core\components\Controller;
use core\components\Module;
use core\components\UrlRule;
use core\exceptions\NotFoundException;
use core\helpers\FileHelper;
use core\helpers\Inflector;
use core\web\App;
use core\web\Response;

/**
 * Class BaseRouter
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
abstract class BaseRouter extends BaseObject
{
    /**
     * @var string - default controller name
     */
    protected $_defaultController = 'Default';
    /**
     * @var string - path to controllers folder based on namespace
     */
    protected $_controllersPath;
    /**
     * @var string - default controllers namespace
     */
    protected $_controllersNamespace;
    /**
     * @var string - current active controller name
     */
    protected $_controller;
    /**
     * @var Controller - current active controller class
     */
    protected $_controllerClass;

    /**
     * @var string - default action name
     */
    protected $_defaultAction = 'Index';
    /**
     * @var string - current active action name
     */
    protected $_action;
    /**
     * @var string - current active action method name
     */
    protected $_actionMethod;
    /**
     * @var string - current active module name
     */
    protected $_module;
    /**
     * @var Module - current active module class
     */
    protected $_moduleClass;

    /**
     * @var string|null - additional param
     */
    protected $_param = null;
    /**
     * @var string - current active route
     */
    protected $_route;
    /**
     * @var string - route requested by user
     */
    protected $_baseRoute;
    /**
     * @var UrlRule[] - routing rules
     */
    protected $_rules = [];

    protected function clearData(){
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

    public abstract function route();

    protected abstract function parseRequest();

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

    /**
     * @return Response
     * @throws NotFoundException
     */
    protected function defaultResolve(){
        $controller = $this->_controller.'Controller';
        $this->_actionMethod = 'action'.$this->_action;
        $controllerPath = FileHelper::normalizePath(Core::getAlias($this->_controllersPath)."/$controller.php");
        if (file_exists($controllerPath)) {
            $className = $this->_controllersNamespace.'\\' . $controller;
            $this->_controllerClass = new $className();
            return $this->_controllerClass->runAction($this->_actionMethod, Core::$app->request->request);
        } else {
            if (CRL_DEBUG === true || Core::$app instanceof \core\console\App) {
                throw new NotFoundException("Controller {$this->_controller} does not exist");
            } else {
                return App::$instance->getResponse()->redirect('/');
            }
        }
    }

}