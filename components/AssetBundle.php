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
        App::$instance->assetManager->registerBundle(get_called_class());
    }

    /**
     * @param AssetManager $am
     */
    public final function publish($am){
        if ($this->sourcePath !== null){
            $publishResult = $am->publish($this->sourcePath);
            $this->basePath = $publishResult[0];
            $this->baseUrl = $publishResult[1];
        } else {
            if ($this->basePath !== null){
                $this->basePath = FileHelper::normalizePath(Core::getAlias($this->basePath));
            }
            foreach ($this->jsAssets() as $i => $jsPath){
                $publishResult = $am->publish($this->basePath.'/'.ltrim($jsPath, '/'));
                
            }
            foreach ($this->cssAssets() as $cssPath){

            }
        }
    }
}