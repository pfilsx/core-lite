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


    public function jsAssets()
    {
        return [];
    }

    public function cssAssets()
    {
        return [];
    }

    public function depends(){
        return [];
    }
    public function fonts(){
        return [];
    }

    public final static function register()
    {
        App::$instance->assetManager->registerBundle(get_called_class());
    }
}