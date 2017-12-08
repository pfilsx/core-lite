<?php


namespace core\components;

use \core\base\App;
use Core;
use core\base\BaseObject;
use core\base\Response;
use core\exceptions\ErrorException;
use core\exceptions\NotFoundException;
use core\helpers\FileHelper;

abstract class Controller extends BaseObject
{
    public $layout;

    public $viewsPath;

    protected $action;

    function __construct()
    {
        $config = App::$instance->config['routing'];
        if (empty($this->layout)){
            $this->layout = $config['layout'];
        }
        if (empty($this->viewPath)){
            if (isset($config['viewsPath'])){
                $this->viewsPath = FileHelper::normalizePath(Core::getAlias($config['viewsPath']));
            } else {
                $this->viewsPath = Core::getAlias('@app/views');
            }
        }
        parent::__construct([]);
    }

    public abstract function actionIndex();

    public function beforeAction($action, $params = []){
        $this->action = $action;
    }

    public final function render($viewName, $_params_ = [])
    {
        App::$instance->view = new View($this, $viewName);
        App::$instance->assetManager->registerBundles();
        return App::$instance->view->getContent($_params_);
    }
    
    public final function renderPartial($view, $_params_ = []){
        return View::renderPartial($view, $_params_);
    }


    public final function redirect($url)
    {
        return App::$instance->getResponse()->redirect($url);
    }

    public final function goHome(){
        return $this->redirect('/');
    }

    public final function runAction($action, $params = []){
        if ($this->beforeAction($action, $params) !== false){
            if (method_exists($this, $action)) {
                $ref = new \ReflectionMethod($this, $action);
                if (!empty($ref->getParameters())) {
                    $_params_ = [];
                    foreach ($ref->getParameters() as $param) {
                        if (array_key_exists($param->name, $params)) {
                            $_params_[$param->name] = $params[$param->name];
                        } else if (!$param->isOptional()) {
                            throw new ErrorException("Required parameter {$param->name} is missed");
                        } else {
                            $_params_[$param->name] = $param->getDefaultValue();
                        }
                    }
                    $content = call_user_func_array([$this, $action],$_params_);
                } else {
                    $content = $this->{$action}();
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
                    $controllerClass = static::className();
                    throw new NotFoundException("Action {$action} does not exist in {$controllerClass}");
                } else {
                    return App::$instance->getResponse()->redirect('/');
                }
            }
        }
        return null;
    }


}