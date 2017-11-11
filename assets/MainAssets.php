<?php


namespace core\assets;


use core\components\AssetBundle;

class MainAssets extends AssetBundle
{
    public static function cssAssets()
    {
        return [
            '@crl/assets/crl.style.css' => static::POS_HEAD
        ];
    }

    public static function jsAssets()
    {
        return [
            '@crl/assets/crl.main.js' => static::POS_BODY_END
        ];
    }
}