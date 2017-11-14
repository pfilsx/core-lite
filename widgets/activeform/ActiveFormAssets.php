<?php


namespace core\widgets\activeform;


use core\components\AssetBundle;

class ActiveFormAssets extends AssetBundle
{
    public static function jsAssets()
    {
        return [
          '@crl/assets/crl.activeForm.js' => static::POS_BODY_END
        ];
    }
    public static function depends()
    {
        return [
            'core\assets\MainAssets'
        ];
    }
}