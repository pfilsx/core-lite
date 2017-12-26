<?php


namespace core\components;


use Core;
use core\base\App;
use core\base\BaseObject;
use core\exceptions\ErrorException;
use core\exceptions\NotFoundException;
use core\helpers\FileHelper;
use core\web\Html;

class View extends BaseObject
{
    private $_viewName;

    private $_controller;

    private $_layout;

    private $_viewsPath;

    private $_params;

    public $title = '';

    private $_content;

    private $_metaTags = [];

    private $_jsHead = [];

    private $_jsBodyBegin = [];
    private $_jsBodyEnd = [];

    private $_cssHead = [];

    private $_cssBodyBegin = [];
    private $_cssBodyEnd = [];

    const META = '<![CDATA[CRL-BLOCK-META]]>';
    const HEAD = '<![CDATA[CRL-BLOCK-HEAD]]>';
    const BODY_END = '<![CDATA[CRL-BLOCK-BODY-END]]>';
    const BODY_BEGIN = '<![CDATA[CRL-BLOCK-BODY-BEGIN]]>';

    const POS_HEAD = 0;
    const POS_BODY_BEGIN = 1;
    const POS_BODY_END = 2;

    const EVENT_BEFORE_RENDER = 'view_before_render';
    const EVENT_AFTER_RENDER = 'view_after_render';
    const EVENT_BEFORE_PREPARE = 'view_before_prepare';
    const EVENT_AFTER_PREPARE = 'view_after_prepare';

    public static $viewRenderer = null;
    public static $defaultExtension = 'php';

    /**
     * @var null|IViewRenderer
     */
    private $_renderer = null;

    /**
     * View constructor.
     * @param Controller $controller
     * @param string $view
     * @param array $config
     */
    public function __construct($controller = null, $view = null, array $config = [])
    {
        if ($controller != null && $view != null){
            $this->_controller = $controller;
            $this->_viewName = str_replace('/', DIRECTORY_SEPARATOR, $view);
            $this->_layout = Core::getAlias($controller->layout . '.php');
            $this->_viewsPath = $controller->viewsPath;
        }
        $config = empty($config) ? App::$instance->config['view'] : $config;
        parent::__construct($config);
        if (static::$viewRenderer != null){
            $this->_renderer = new static::$viewRenderer();
        }
    }

    public function getContent(array $_params_ = []){
        if ($this->_controller == null || $this->_viewName == null){
            throw new ErrorException('View::getContent can be called only from controller. Use View::render instead');
        }
        if (file_exists($this->_layout)){
            $this->_params = $_params_;
            $this->_content = $this->renderFile($this->_layout, $_params_);
            $this->registerJs(
                "window.crl.baseUrl = '".App::$instance->request->baseUrl."';
                     $('.crl_remove').remove();
                    ",
                self::POS_BODY_END, [
                'class' => 'crl_remove'
            ]);
            $this->invoke(self::EVENT_BEFORE_PREPARE, ['content' => &$this->_content]);
            $this->prepareContent();
            $this->invoke(self::EVENT_AFTER_PREPARE, ['content' => &$this->_content]);
            return $this->_content;
        }
        return null;
    }

    public function getViewContent(){
        $file = $this->getViewFile($this->_viewName);
        return $this->renderFile($file, $this->_params, $this->_viewName);
    }


    public function render($view, array $_params_ = []){
        $file = $this->getViewFile($view);
        return $this->renderFile($file, $_params_, $view);
    }

    public static function renderPartial($viewName, array $_params_ = []){
        $file = FileHelper::normalizePath(Core::getAlias($viewName));
        $view = new View();
        return $view->renderFile($file, $_params_);
    }

    public function registerMetaTag($options, $key = null){
        if ($key == null){
            $this->_metaTags[] = Html::tag('meta', '', $options);
        } else {
            $this->_metaTags[$key] = Html::tag('meta', '', $options);
        }
    }

    public function registerJs($js, $position = View::POS_BODY_END, $options = []){
        $content = Html::tag('script', $js, array_merge($options, ['type' => 'text/javascript']));
        if ($position == View::POS_HEAD){
            $this->_jsHead[] = $content;
        } else if ($position == View::POS_BODY_BEGIN) {
            $this->_jsBodyBegin[] = $content;
        } else {
            $this->_jsBodyEnd[] = $content;
        }
    }
    public function registerJsFile($file, $position = View::POS_BODY_END, $options = []){
        $content = Html::tag('script','', array_merge($options, ['src' => $file, 'type' => 'text/javascript']));
        if ($position == View::POS_HEAD){
            $this->_jsHead[] = $content;
        } else if ($position == View::POS_BODY_BEGIN) {
            $this->_jsBodyBegin[] = $content;
        } else {
            $this->_jsBodyEnd[] = $content;
        }
    }

    public function registerCss($css, $position = View::POS_HEAD, $options = []){
        $content = Html::tag('style', $css, $options);
        if ($position == View::POS_BODY_BEGIN){
            $this->_cssBodyBegin[] = $content;
        } else if ($position == View::POS_BODY_END){
            $this->_cssBodyEnd[] = $content;
        } else {
            $this->_cssHead[] = $content;
        }
    }
    public function registerCssFile($file, $position = View::POS_HEAD, $options = []){
        $content = Html::tag('link', '', array_merge($options, ['rel' => 'stylesheet', 'href' => $file]));
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
        $this->_metaTags = [];
        $this->_jsBodyBegin = [];
        $this->_jsBodyEnd = [];
        $this->_cssHead = [];
        $this->_cssBodyBegin = [];
        $this->_cssBodyEnd = [];
    }

    public function metaTags(){
        echo View::META;
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

    private function renderFile($file, $_params_ = [], $view = null) {
        $this->invoke(self::EVENT_BEFORE_RENDER, [
            'file' => $file,
            'params' => $_params_,
            'renderer' => static::$viewRenderer != null ? static::$viewRenderer : 'PHP Renderer'
        ]);
        if (file_exists($file)) {
            if ($this->_renderer != null){
                $content = $this->_renderer->renderFile($this, $file, $_params_);
            } else {
                $content = $this->renderPhpFile($file, $_params_);
            }
            $this->invoke(self::EVENT_AFTER_RENDER, [
                'file' => $file,
                'params' => $_params_,
                'renderer' => static::$viewRenderer != null ? static::$viewRenderer : 'PHP Renderer',
                'content' => $content
            ]);
            return $content;
        }
        if ($view != null){
            throw new NotFoundException("Unable to locate view file for view '$view'.");
        } else {
            throw new NotFoundException("Unable to locate view file '$file'");
        }
    }

    private function renderPhpFile($file, $_params_ = []){
        $_obInitialLevel_ = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        extract($_params_, EXTR_OVERWRITE);
        try {
            require $file;
            return ob_get_clean();
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
    private function getViewPath($viewName = null){
        $viewName = ($viewName == null ? $this->_viewName : $viewName);
        $classWithNamespace = get_class($this->_controller);
        $className = explode('\\', $classWithNamespace);
        $viewFolder = strtolower(str_replace('Controller', '', array_pop($className)));
        return $this->_viewsPath . DIRECTORY_SEPARATOR . $viewFolder
            . DIRECTORY_SEPARATOR . $viewName . '.php';
    }

    private function getViewFile($view){
        if (strncmp($view, '@', 1) === 0){
            $file =Core::getAlias($view);
        } elseif (is_file($view)){
          return $view;
        } else {
            if ($this->_controller !== null) {
                $classWithNamespace = get_class($this->_controller);
                $className = explode('\\', $classWithNamespace);
                $viewFolder = strtolower(str_replace('Controller', '', array_pop($className)));
                $file = $this->_viewsPath . DIRECTORY_SEPARATOR . $viewFolder
                    . DIRECTORY_SEPARATOR. ltrim($view, '/');
            } else {
                throw new NotFoundException("Unable to locate view file for view '$view': no active controller.");
            }
        }
        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        $path = $file . '.' . static::$defaultExtension;
        if (static::$defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }
        return $path;
    }

    private function prepareContent(){
        $this->invoke(self::EVENT_BEFORE_PREPARE, ['content' => &$this->_content]);
        $preparedMeta = implode(PHP_EOL, $this->_metaTags);
        $preparedHead = implode(PHP_EOL, $this->_cssHead).PHP_EOL.implode(PHP_EOL, $this->_jsHead);
        $preparedBodyBegin = implode(PHP_EOL, $this->_cssBodyBegin).PHP_EOL.implode(PHP_EOL, $this->_jsBodyBegin);
        $preparedBodyEnd = implode(PHP_EOL, $this->_cssBodyEnd).PHP_EOL.implode(PHP_EOL, $this->_jsBodyEnd);
        $this->_content = strtr($this->_content, [
            View::META => $preparedMeta,
            View::HEAD => $preparedHead,
            View::BODY_BEGIN => $preparedBodyBegin,
            View::BODY_END => $preparedBodyEnd
        ]);
        $this->invoke(self::EVENT_AFTER_PREPARE, ['content' => &$this->_content]);
    }


}