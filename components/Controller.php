<?php


namespace core\components;

use \core\base\App;
use Core;
use core\base\BaseObject;
use core\helpers\FileHelper;

abstract class Controller extends BaseObject
{
    public $layout;

    protected $action;

    function __construct()
    {
        $config = App::$instance->config['routing'];
        if (empty($this->layout)){
            $this->layout = $config['layout'];
        }
        parent::__construct([]);
    }

    public abstract function actionIndex();

    public function beforeAction($action){
        $this->action = $action;
    }

    public final function render($viewName, $_params_ = [])
    {
        $view = new View($this, $viewName);
        return $view->getContent($_params_);
    }

    public final function redirect($url)
    {
        return App::$instance->getResponse()->redirect($url);
    }

    public final function goHome(){
        return $this->redirect('/');
    }


}