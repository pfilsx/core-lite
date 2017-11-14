<?php


namespace core\components;


use Core;
use core\base\App;
use core\base\BaseObject;
use core\helpers\FileHelper;

class View extends BaseObject
{
    private $_viewName;

    private $_controller;

    private $_layout;

    private $_viewsPath;

    private $_params;

    public $title = '';

    private $_content;

    private $_jsHead = [];

    private $_jsBodyBegin = [];
    private $_jsBodyEnd = [];

    private $_cssHead = [];

    private $_cssBodyBegin = [];
    private $_cssBodyEnd = [];

    const HEAD = '<![CDATA[CRL-BLOCK-HEAD]]>';
    const BODY_END = '<![CDATA[CRL-BLOCK-BODY-END]]>';
    const BODY_BEGIN = '<![CDATA[CRL-BLOCK-BODY-BEGIN]]>';

    const POS_HEAD = 0;
    const POS_BODY_BEGIN = 1;
    const POS_BODY_END = 2;

    /**
     * View constructor.
     * @param Controller $controller
     * @param string $view
     * @param array $config
     */
    public function __construct($controller, $view, array $config = [])
    {
        $this->_controller = $controller;
        $this->_viewName = str_replace('/', DIRECTORY_SEPARATOR, $view);
        $this->_layout = $filePath = Core::getAlias($controller->layout . '.php');
        $config = App::$instance->config['routing'];
        if (isset($config['viewsPath'])){
            $this->_viewsPath = FileHelper::normalizePath(Core::getAlias($config['viewsPath']));
        } else {
            $this->_viewsPath = Core::getAlias('@app/views');
        }
        parent::__construct($config);
    }

    public function getContent(array $_params_ = []){
        if (file_exists($this->_layout)){
            $this->_params = $_params_;
            $_obInitialLevel_ = ob_get_level();
            ob_start();
            ob_implicit_flush(false);
            extract($_params_, EXTR_OVERWRITE);
            try {
                require $this->_layout;
                $this->_content = ob_get_clean();
                $this->prepareContent();
                return $this->_content;
            }
            catch (\Exception $ex){
                while (ob_get_level() > $_obInitialLevel_) {
                    if (!@ob_end_clean()) {
                        ob_clean();
                    }
                }
                throw $ex;
            }
        }
        return null;
    }

    private function getViewContent(){
        $filePath = $this->getViewPath();
        if (file_exists($filePath)) {
            $_params_ = $this->_params;
            ob_start();
            ob_implicit_flush(false);
            extract($_params_, EXTR_OVERWRITE);
            require($filePath);
            return ob_get_clean();
        }
        return null;
    }


    public function render($view, array $_params_ = []){
        $filePath = $this->getViewPath($view);
        if (file_exists($filePath)) {
            ob_start();
            ob_implicit_flush(false);
            extract($_params_, EXTR_OVERWRITE);
            require($filePath);
            return ob_get_clean();
        }
        return null;
    }

    public function registerJs($js, $position = View::POS_BODY_END){
        $content = "<script>$js</script>";
        if ($position == View::POS_HEAD){
            $this->_jsHead[] = $content;
        } else if ($position == View::POS_BODY_BEGIN) {
            $this->_jsBodyBegin[] = $content;
        } else {
            $this->_jsBodyEnd[] = $content;
        }
    }
    public function registerJsFile($file, $position = View::POS_BODY_END){
        $path = App::$instance->request->getBaseUrl().'/'.$file;
        $content = "<script src='$path'></script>";
        if ($position == View::POS_HEAD){
            $this->_jsHead[] = $content;
        } else if ($position == View::POS_BODY_BEGIN) {
            $this->_jsBodyBegin[] = $content;
        } else {
            $this->_jsBodyEnd[] = $content;
        }
    }

    public function registerCss($css, $position = View::POS_HEAD){
        $content = "<style>$css</style>";
        if ($position == View::POS_BODY_BEGIN){
            $this->_cssBodyBegin[] = $content;
        } else if ($position == View::POS_BODY_END){
            $this->_cssBodyEnd[] = $content;
        } else {
            $this->_cssHead[] = $content;
        }
    }
    public function registerCssFile($file, $position = View::POS_HEAD){
        $path = App::$instance->request->getBaseUrl().'/'.$file;
        $content = "<link rel='stylesheet' href='$path'/>";
        if ($position == View::POS_BODY_BEGIN){
            $this->_cssBodyBegin[] = $content;
        } else if ($position == View::POS_BODY_END){
            $this->_cssBodyEnd[] = $content;
        } else {
            $this->_cssHead[] = $content;
        }
    }

    public function clear(){
        $this->_jsHead = [];
        $this->_jsBodyBegin = [];
        $this->_jsBodyEnd = [];
        $this->_cssHead = [];
        $this->_cssBodyBegin = [];
        $this->_cssBodyEnd = [];
    }

    public function head(){
        echo View::HEAD;
    }
    public function beginBody(){
        echo View::BODY_BEGIN;
    }

    public function endBody(){
        echo View::BODY_END;
    }

    public function endPage(){
        $content = ob_get_clean();
        return $content;
    }

    private function getViewPath($viewName = null){
        $viewName = ($viewName == null ? $this->_viewName : $viewName);
        $classWithNamespace = get_class($this->_controller);
        $className = explode('\\', $classWithNamespace);
        $viewFolder = strtolower(str_replace('Controller', '', array_pop($className)));
        return $this->_viewsPath . DIRECTORY_SEPARATOR . $viewFolder
            . DIRECTORY_SEPARATOR . $viewName . '.php';
    }

    private function prepareContent(){
        $preparedHead = implode(PHP_EOL, $this->_cssHead).PHP_EOL.implode(PHP_EOL, $this->_jsHead);
        $preparedBodyBegin = implode(PHP_EOL, $this->_cssBodyBegin).PHP_EOL.implode(PHP_EOL, $this->_jsBodyBegin);
        $preparedBodyEnd = implode(PHP_EOL, $this->_cssBodyEnd).PHP_EOL.implode(PHP_EOL, $this->_jsBodyEnd);
        $this->_content = str_replace([
            View::HEAD,
            View::BODY_BEGIN,
            View::BODY_END
        ], [
            $preparedHead,
            $preparedBodyBegin,
            $preparedBodyEnd
        ], $this->_content);
    }


}