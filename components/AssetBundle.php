<?php

namespace core\components;

use Core;
use core\base\AssetManager;
use core\helpers\FileHelper;
use core\web\App;
use core\base\BaseObject;

abstract class AssetBundle extends BaseObject
{
    const POS_HEAD = 0;
    const POS_BODY_BEGIN = 1;
    const POS_BODY_END = 2;

    public $basePath;
    public $baseUrl;

    public $sourcePath;

    protected $_jsAssets = [];
    protected $_cssAssets = [];

    /**
     * @return array
     */
    public function jsAssets()
    {
        return [];
    }

    /**
     * @return array
     */
    public function cssAssets()
    {
        return [];
    }

    /**
     * @return array
     */
    public function depends(){
        return [];
    }

    /**
     * @return array
     */
    public function includedBundles(){
        return [];
    }

    public final static function register()
    {
        $className = get_called_class();
        if (in_array($className, App::$instance->assetManager->registeredBundles)){
            return;
        }
        /**
         * @var AssetBundle $bundle
         */
        $bundle = new $className();
        foreach ($bundle->depends() as $depend){
            if (class_exists($depend) && is_subclass_of($depend, AssetBundle::className())){
                $depend::register();
            }
        }
        $bundle->publish(Core::$app->assetManager);
        $bundle->registerBundle(App::$instance->view);
        Core::$app->assetManager->registeredBundles[] = $className;
    }

    /**
     * @param AssetManager $am
     */
    public final function publish($am){
        if ($this->sourcePath !== null){
            $publishResult = $am->publish($this->sourcePath);
            $this->basePath = $publishResult[0];
            $this->baseUrl = $publishResult[1];
            $this->prepareSource();
        } else {
            if ($this->basePath !== null){
                $this->basePath = FileHelper::normalizePath(Core::getAlias($this->basePath));
            }
            $this->prepareFiles($am);
        }
    }

    protected function prepareSource(){
        if (isset($this->jsAssets()[0])){
            foreach ($this->jsAssets() as $jsPath){
                $this->_jsAssets[$this->basePath.'/'.ltrim($jsPath, '/')] = [
                    $this->baseUrl.'/'.ltrim($jsPath, '/'),
                    View::POS_BODY_END
                ];
            }
        } else {
            foreach ($this->jsAssets() as $jsPath => $position){
                $this->_jsAssets[$this->basePath.'/'.ltrim($jsPath, '/')] = [
                    $this->baseUrl.'/'.ltrim($jsPath, '/'),
                    $position
                ];
            }
        }
        if (isset($this->cssAssets()[0])){
            foreach ($this->cssAssets() as $cssPath){
                $this->_cssAssets[$this->basePath.'/'.ltrim($cssPath, '/')] = [
                    $this->baseUrl.'/'.ltrim($cssPath, '/'),
                    View::POS_HEAD
                ];
            }
        } else {
            foreach ($this->cssAssets() as $cssPath => $position){
                $this->_cssAssets[$this->basePath.'/'.ltrim($cssPath, '/')] = [
                    $this->baseUrl.'/'.ltrim($cssPath, '/'),
                    $position
                ];
            }
        }
    }

    /**
     * @param AssetManager $am
     */
    protected function prepareFiles($am){
        if (isset($this->jsAssets()[0])){
            foreach ($this->jsAssets() as $jsPath){
                $publishResult = $am->publish($this->basePath.'/'.ltrim(Core::getAlias($jsPath), '/'));
                $this->_jsAssets[$publishResult[0]] = [
                    $publishResult[1],
                    View::POS_BODY_END
                ];
            }
        } else {
            foreach ($this->jsAssets() as $jsPath => $position){
                $publishResult = $am->publish($this->basePath.'/'.ltrim(Core::getAlias($jsPath), '/'));
                $this->_jsAssets[$publishResult[0]] = [
                    $publishResult[1],
                    $position
                ];
            }
        }
        if (isset($this->cssAssets()[0])){
            foreach ($this->cssAssets() as $cssPath){
                $publishResult = $am->publish($this->basePath.'/'.ltrim(Core::getAlias($cssPath), '/'));
                $this->_cssAssets[$publishResult[0]] = [
                    $publishResult[1],
                    View::POS_HEAD
                ];
            }
        } else {
            foreach ($this->cssAssets() as $cssPath => $position){
                $publishResult = $am->publish($this->basePath.'/'.ltrim(Core::getAlias($cssPath), '/'));
                $this->_cssAssets[$publishResult[0]] = [
                    $publishResult[1],
                    $position
                ];
            }
        }
    }

    /**
     * @param View $view
     */
    protected final function registerBundle($view){
        if ($view == null){ return; }
        foreach ($this->_jsAssets as $asset){
            $view->registerJsFile($asset[0], $asset[1]);
        }
        foreach ($this->_cssAssets as $asset){
            $view->registerCssFile($asset[0], $asset[1]);
        }
    }
}