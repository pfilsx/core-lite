<?php

namespace core\components;

use core\web\App;
use core\base\BaseObject;

abstract class AssetBundle extends BaseObject
{
    const POS_HEAD = 0;
    const POS_BODY_BEGIN = 1;
    const POS_BODY_END = 2;

    public $basePath;

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
    public function fonts(){
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
}