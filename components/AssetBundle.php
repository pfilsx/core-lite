<?php

namespace core\components;

use core\base\App;
use core\base\AssetManager;
use core\base\BaseObject;

abstract class AssetBundle extends BaseObject
{
    const POS_HEAD = 0;
    const POS_BODY_BEGIN = 1;
    const POS_BODY_END = 2;


    public static function jsAssets()
    {
        return [];
    }

    public static function cssAssets()
    {
        return [];
    }

    public static function depends(){
        return [];
    }

    public final static function registerAssets()
    {
        $view = App::$instance->view;
        if ($view == null || in_array(static::className(), App::$instance->assetManager->registeredBundles)) {
            return;
        }
        $cssAssets = static::cssAssets();
        $jsAssets = static::jsAssets();
        $depends = static::depends();
        if (!empty($depends)){
            static::_registerDepends($depends);
        }
        if (!empty($cssAssets)) {
            static::_registerCssAssets($view, $cssAssets);
        }
        if (!empty($jsAssets)){
            static::_registerJsAssets($view, $jsAssets);
        }
        App::$instance->assetManager->registeredBundles[] = static::className();
    }
    /**
     * @param View $view
     * @param array $assets
     */
    private static function _registerCssAssets($view, array $assets){
        if (isset($assets[0])){
            foreach ($assets as $path){
                if (($assetPath = App::$instance->assetManager->registerAsset($path)) != null){
                    $view->registerCssFile($assetPath, AssetBundle::POS_HEAD);
                }
            }
        } else {
            foreach ($assets as $path => $position){
                if (($assetPath = App::$instance->assetManager->registerAsset($path)) != null){
                    $view->registerCssFile($assetPath, $position);
                }
            }
        }
    }
    /**
     * @param View $view
     * @param array $assets
     */
    private static function _registerJsAssets($view, array $assets){
        if (isset($assets[0])){
            foreach ($assets as $path){
                if (($assetPath = App::$instance->assetManager->registerAsset($path)) != null){
                    $view->registerJsFile($assetPath, AssetBundle::POS_HEAD);
                }
            }
        } else {
            foreach ($assets as $path => $position){
                if (($assetPath = App::$instance->assetManager->registerAsset($path)) != null){
                    $view->registerJsFile($assetPath, $position);
                }
            }
        }
    }
    /**
     * @param array $bundles
     */
    private static function _registerDepends(array $bundles){
        foreach ($bundles as $bundle){
            if (new $bundle() instanceof AssetBundle){
                $bundle::registerAssets();
            }
        }
    }
}