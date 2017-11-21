<?php


namespace core\widgets\activeform;


use core\components\AssetBundle;

class ActiveFormAssets extends AssetBundle
{
    public $basePath = '@crl/assets';

    public function cssAssets()
    {
        return [
            'crl.activeForm.css' => static::POS_HEAD
        ];
    }

    public function jsAssets()
    {
        return [
          'crl.activeForm.js' => static::POS_BODY_END
        ];
    }
    public function depends()
    {
        return [
            'core\assets\MainAssets'
        ];
    }
}