<?php


namespace core\widgets\pjax;


use core\components\AssetBundle;

class PjaxAssets extends AssetBundle
{


    public function jsAssets()
    {
        return [
            '@crl/assets/crl.jquery.pjax.js' => static::POS_BODY_END
        ];
    }
    public function depends()
    {
        return [
            'core\assets\MainAssets'
        ];
    }
}