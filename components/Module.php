<?php


namespace core\components;


use Core;
use core\base\App;
use core\base\BaseObject;
use core\helpers\Inflector;


/**
 * @property string id
 */
abstract class Module extends BaseObject
{
    public $controllerNamespace;

    public $defaultController = 'default';

    private $_basePath;

    private $_viewPath;

    private $_layoutPath;

    protected $controllerMap = [];

    public function init()
    {
        if ($this->controllerNamespace === null) {
            $class = get_class($this);
            if (($pos = strrpos($class, '\\')) !== false) {
                $this->controllerNamespace = substr($class, 0, $pos) . '\\controllers';
            }
        }
    }

    public abstract function getId();

    public abstract function initializeModule();

    public function getBasePath()
    {
        if ($this->_basePath === null) {
            $class = new \ReflectionClass($this);
            $this->_basePath = dirname($class->getFileName());
        }
        return $this->_basePath;
    }

    public function setBasePath($path)
    {
        $path = Core::getAlias($path);
        $p = strncmp($path, 'phar://', 7) === 0 ? $path : realpath($path);
        if ($p !== false && is_dir($p)) {
            $this->_basePath = $p;
        } else {
            throw new \Exception("The directory does not exist: $path");
        }
    }

    public function getControllerPath()
    {
        return Core::getAlias('@' . str_replace('\\', '/', $this->controllerNamespace));
    }

    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            $this->_viewPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'views';
        }
        return $this->_viewPath;
    }

    public function setViewPath($path)
    {
        $this->_viewPath = Core::getAlias($path);
    }

    public function getLayoutPath()
    {
        if ($this->_layoutPath === null) {
            $this->_layoutPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'layouts';
        }

        return $this->_layoutPath;
    }

    public function setLayoutPath($path)
    {
        $this->_layoutPath = Core::getAlias($path);
    }

    public function runAction($action, $params = [])
    {
        $controller = $this->createController(App::$instance->router->controller);
        if ($controller instanceof Controller) {
            App::$instance->router->controllerClass = $controller;
            /* @var $controller Controller */
            $controller->viewsPath = $this->getViewPath();
            $controller->layout = $this->getLayoutPath().DIRECTORY_SEPARATOR.$controller->layout;
            $result = $controller->runAction($action, $params);
            return $result;
        }
        throw new \Exception('Unable to resolve the request "' . App::$instance->router->route . '".');
    }

    public function createController($controller)
    {
        if (empty($controller)) {
            $controller = $this->defaultController;
        }
        $controller = Inflector::id2camel($controller);

        // module and controller map take precedence
        if (isset($this->controllerMap[$controller])) {
            $controller = new $this->controllerMap[$controller]();
            return $controller;
        }
        $className = str_replace(' ', '', $controller) . 'Controller';
        $className = ltrim($this->controllerNamespace . '\\' . $className, '\\');
        if (class_exists($className) && is_subclass_of($className, 'core\components\Controller')){
            $controller = new $className();
            return $controller;
        }
        return null;
    }
}