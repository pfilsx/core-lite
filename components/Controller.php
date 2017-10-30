<?php


namespace core\components;

use \core\base\App;
use Core;
use core\base\BaseObject;
use core\helpers\FileHelper;

abstract class Controller extends BaseObject
{
    protected $layout;
    private $_basePath;

    private $_viewsPath;

    private $viewName;
    private $viewParams;

    protected $action;

    function __construct()
    {
        $config = App::$instance->config['routing'];
        if (empty($this->layout)){
            $this->layout = $config['layout'];
        }
        $this->_basePath = Core::getAlias('@app');
        if (isset($config['viewsPath'])){
            $this->_viewsPath = FileHelper::normalizePath(Core::getAlias($config['viewsPath']));
        } else {
            $this->_viewsPath = Core::getAlias('@app/views');
        }
        parent::__construct([]);
    }

    public abstract function actionIndex();

    public function beforeAction($action){
        $this->action = $action;
    }

    public final function render($viewName, $_params_ = [])
    {
        $filePath = Core::getAlias($this->layout . '.php');
        if (file_exists($filePath)) {
            $this->viewName = $viewName;
            $this->viewParams = $_params_;
            ob_start();
            ob_implicit_flush(false);
            extract($_params_, EXTR_OVERWRITE);
            require($filePath);
            return ob_get_clean();
        }
        return null;
    }

    public final function renderView($viewName, $_params_ = [])
    {
        $filePath = $this->getViewPath($viewName);
        if (file_exists($filePath)) {
            ob_start();
            ob_implicit_flush(false);
            extract($_params_, EXTR_OVERWRITE);
            require($filePath);
            return ob_get_clean();
        }
        return null;
    }

    public final function redirect($url)
    {
        return App::$instance->getResponse()->redirect($url);
    }

    public final function goHome(){
        return $this->redirect('/');
    }

    public final function registerJsAssets()
    {
        return App::$instance->assetManager->registerJsAssets();
    }

    public final function registerJsFile($path){
        $path = App::$instance->request->getBaseUrl().'/'.$path;
        return '<script type="text/javascript" src="' . $path . '"></script>';
    }

    public final function registerCssAssets()
    {
        return App::$instance->assetManager->registerCssAssets();
    }

    public final function registerCssFile($path){
        $path = App::$instance->request->getBaseUrl().'/'.$path;
        return '<link rel="stylesheet" href="' . $path . '">';
    }

    public final function registerJs($js)
    {
        if (gettype($js) !== 'string') {
            return '';
        }
        return "<script>$js</script>";
    }


    private final function getViewContent()
    {
        $filePath = $this->getViewPath();
        if (file_exists($filePath)) {
            $_params_ = $this->viewParams;
            ob_start();
            ob_implicit_flush(false);
            extract($_params_, EXTR_OVERWRITE);
            require($filePath);
            return ob_get_clean();
        }
        return null;
    }

    private final function getViewPath($viewName = null)
    {
        $viewName = ($viewName == null ? $this->viewName : $viewName);
        $classWithNamespace = get_called_class();
        $className = explode('\\', $classWithNamespace);
        $viewFolder = strtolower(str_replace('Controller', '', array_pop($className)));
        return $this->_viewsPath . DIRECTORY_SEPARATOR . $viewFolder
            . DIRECTORY_SEPARATOR . $viewName . '.php';
    }
}