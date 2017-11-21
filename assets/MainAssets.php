<?php


namespace core\assets;


use core\components\AssetBundle;

class MainAssets extends AssetBundle
{
    public $basePath = '@crl/assets';

    public function cssAssets()
    {
        return [
            'crl.style.css' => static::POS_HEAD
        ];
    }

    public function jsAssets()
    {
        return [
            'crl.main.js' => static::POS_BODY_END
        ];
    }
}