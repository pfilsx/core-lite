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
        //App::$instance->assetManager->registerBundle(get_called_class());
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
        $bundle->publish(App::$instance->assetManager);
        $bundle->registerBundle(App::$instance->view);
        App::$instance->assetManager->registeredBundles[] = $className;
    }

    /**
     * @param AssetManager $am
     */
    public final function publish($am){
        if ($this->sourcePath !== null){
            $publishResult = $am->publish($this->sourcePath);
            $this->basePath = $publishResult[0];
            $this->baseUrl = $publishResult[1];
            foreach ($this->jsAssets() as $jsPath){
                $this->_jsAssets[$this->basePath.'/'.ltrim($jsPath, '/')] = $this->baseUrl.'/'.ltrim($jsPath, '/');
            }
            foreach ($this->cssAssets() as $cssPath){
                $this->_cssAssets[$this->basePath.'/'.ltrim($cssPath, '/')] = $this->baseUrl.'/'.ltrim($cssPath, '/');
            }
        } else {
            if ($this->basePath !== null){
                $this->basePath = FileHelper::normalizePath(Core::getAlias($this->basePath));
            }
            foreach ($this->jsAssets() as $i => $jsPath){
                $publishResult = $am->publish($this->basePath.'/'.ltrim($jsPath, '/'));
                $this->_jsAssets[$publishResult[0]] = $publishResult[1];
            }
            foreach ($this->cssAssets() as $cssPath){
                $publishResult = $am->publish($this->basePath.'/'.ltrim($cssPath, '/'));
                $this->_cssAssets[$publishResult[0]] = $publishResult[1];
            }
        }
    }

    /**
     * @param View $view
     */
    protected final function registerBundle($view){
        if ($view == null){ return; }
        foreach ($this->_jsAssets as $asset){
            $view->registerJsFile($asset);
        }
        foreach ($this->_cssAssets as $asset){
            $view->registerCssFile($asset);
        }
    }
}