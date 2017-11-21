<?php


namespace core\base;

use Core;
use core\components\AssetBundle;
use core\helpers\FileHelper;

class AssetManager extends BaseObject
{

    private $_bundles = [];

    public $registeredBundles = [];

    function __construct($config = [])
    {
        parent::__construct($config);
    }

    public function init()
    {
        foreach ($this->_config as $bundle) {
            if (new $bundle() instanceof AssetBundle) {
                $this->_bundles[] = $bundle;
            }
        }
    }


    private function placeFonts($path)
    {
        $fontPath = FileHelper::normalizePath(Core::getAlias($path));
        if (is_file($fontPath)) {
            $fontPath = dirname($fontPath);
        }
        if (is_dir($fontPath)) {
            $newPath = FileHelper::normalizePath(Core::getAlias('@webroot') . '\\assets\\fonts\\');
            if (!is_dir($newPath)) {
                if (!FileHelper::createDirectory($newPath)) {
                    return;
                }
            }
            foreach (FileHelper::findFiles($fontPath) as $file) {
                $newFilePath = $newPath . DIRECTORY_SEPARATOR . basename($file);
                if (!is_file($newFilePath)) {
                    copy($file, $newFilePath);
                }
            }
        }
    }

    public function registerBundles()
    {
        if (!empty($this->_bundles) && App::$instance->view != null) {
            foreach ($this->_bundles as $bundle) {
                if (is_subclass_of($bundle, AssetBundle::className()))
                    $bundle::register();
            }
        }
    }

    public function clearBundles()
    {
        $this->_bundles = [];
    }

    public function registerBundle($className)
    {
        /**
         * @var AssetBundle $bundle
         */
        $bundle = new $className();
        $view = App::$instance->view;
        if ($view == null || !$bundle instanceof AssetBundle || in_array($bundle::className(), $this->registeredBundles)) {
            return;
        }
        if (!empty($bundle->basePath)) {
            $bundle->basePath = FileHelper::normalizePath(Core::getAlias($bundle->basePath));
        }
        $depends = $bundle->depends();
        $fonts = $bundle->fonts();
        $cssAssets = $bundle->cssAssets();
        $jsAssets = $bundle->jsAssets();
        if (!empty($depends)) {
            foreach ($depends as $subBundle) {
                if (is_subclass_of($subBundle, AssetBundle::className())) {
                    /**
                     * @var AssetBundle $subBundle
                     */
                    $subBundle::register();
                }
            }
        }
        if (!empty($fonts)) {
            $fontsPaths = (array)$fonts;
            foreach ($fontsPaths as $path) {
                if (!empty($bundle->basePath)) {
                    $path = $bundle->basePath . DIRECTORY_SEPARATOR . $path;
                }
                $this->placeFonts($path);
            }
        }
        if (!empty($cssAssets)) {
            if (isset($cssAssets[0])) {
                foreach ($cssAssets as $path) {
                    if (!empty($bundle->basePath)) {
                        $path = $bundle->basePath . DIRECTORY_SEPARATOR . $path;
                    }
                    if (($assetPath = $this->registerAsset($path)) != null) {
                        $view->registerCssFile($assetPath, AssetBundle::POS_HEAD);
                    }
                }
            } else {
                foreach ($cssAssets as $path => $position) {
                    if (!empty($bundle->basePath)) {
                        $path = $bundle->basePath . DIRECTORY_SEPARATOR . $path;
                    }
                    if (($assetPath = $this->registerAsset($path)) != null) {
                        $view->registerCssFile($assetPath, $position);
                    }
                }
            }
        }
        if (!empty($jsAssets)) {
            if (isset($jsAssets[0])) {
                foreach ($jsAssets as $path) {
                    if (!empty($bundle->basePath)) {
                        $path = $bundle->basePath . DIRECTORY_SEPARATOR . $path;
                    }
                    if (($assetPath = $this->registerAsset($path)) != null) {
                        $view->registerJsFile($assetPath, AssetBundle::POS_BODY_END);
                    }
                }
            } else {
                foreach ($jsAssets as $path => $position) {
                    if (!empty($bundle->basePath)) {
                        $path = $bundle->basePath . DIRECTORY_SEPARATOR . $path;
                    }
                    if (($assetPath = $this->registerAsset($path)) != null) {
                        $view->registerJsFile($assetPath, $position);
                    }
                }
            }
        }
        $this->registeredBundles[] = $bundle::className();
    }

    private function registerAsset($path)
    {
        $path = FileHelper::normalizePath(Core::getAlias($path));
        if (is_file($path)) {
            $assetPath = FileHelper::normalizePath(Core::getAlias('@webroot') . '\\assets\\' . basename($path));
            if (is_file($assetPath)) {
                if (md5_file($assetPath) !== md5_file($path)) {
                    copy($path, $assetPath);
                }
            } else {
                if (FileHelper::createDirectory(dirname($assetPath))) {
                    copy($path, $assetPath);
                }
            }
            return 'assets/' . basename($assetPath);
        }
        return null;
    }
}