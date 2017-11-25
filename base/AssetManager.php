<?php


namespace core\base;

use Core;
use core\components\AssetBundle;
use core\helpers\FileHelper;

class AssetManager extends BaseObject
{

    private $_bundles = [];

    public $registeredBundles = [];

    public $dirMode = 0775;

    public $destPath;

    public $beforeCopy;

    public $fileMode = 0777;
    private $afterCopy;

    function __construct($config = [])
    {
        $this->destPath = FileHelper::normalizePath(Core::getAlias('@webroot') . '\\assets\\');
        parent::__construct($config);
    }

    public function init()
    {
        if (isset($this->_config['bundles'])){
            foreach ($this->_config['bundles'] as $bundle) {
                if (is_subclass_of($bundle, AssetBundle::className())) {
                    $this->_bundles[] = $bundle;
                }
            }
        }
    }


    private function placeFonts($path)
    {
        $fontPath = FileHelper::normalizePath(Core::getAlias($path));
        if (is_file($fontPath)) {
            $fontPath = dirname($fontPath);
        }
        $this->publishDirectory($fontPath, []);
    }

    public function registerBundles()
    {
        if (!empty($this->_bundles) && App::$instance->view != null) {
            foreach ($this->_bundles as $bundle) {
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
                    if (($assetPath = $this->publishFile($path)) !== false) {
                        $view->registerCssFile($assetPath, AssetBundle::POS_HEAD);
                    }
                }
            } else {
                foreach ($cssAssets as $path => $position) {
                    if (!empty($bundle->basePath)) {
                        $path = $bundle->basePath . DIRECTORY_SEPARATOR . $path;
                    }
                    if (($assetPath = $this->publishFile($path)) !== false) {
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
                    if (($assetPath = $this->publishFile($path)) !== false) {
                        $view->registerJsFile($assetPath, AssetBundle::POS_BODY_END);
                    }
                }
            } else {
                foreach ($jsAssets as $path => $position) {
                    if (!empty($bundle->basePath)) {
                        $path = $bundle->basePath . DIRECTORY_SEPARATOR . $path;
                    }
                    if (($assetPath = $this->publishFile($path)) !== false) {
                        $view->registerJsFile($assetPath, $position);
                    }
                }
            }
        }
        $this->registeredBundles[] = $bundle::className();
    }

    public function publishFile($path)
    {
        $path = FileHelper::normalizePath(Core::getAlias($path));
        if (is_file($path)) {
            $assetPath = $this->destPath . DIRECTORY_SEPARATOR . basename($path);
            if (is_file($assetPath)) {
                if (md5_file($assetPath) !== md5_file($path)) {
                    copy($path, $assetPath);
                    @chmod($path, $this->fileMode);
                }
            } else {
                if (FileHelper::createDirectory(dirname($assetPath), $this->dirMode)) {
                    copy($path, $assetPath);
                    @chmod($path, $this->fileMode);
                }
            }
            return 'assets/' . basename($assetPath);
        }
        return false;
    }

    public function publishDirectory($path, $options)
    {
        if (is_dir($path)) {
            if (!is_dir($this->destPath)) {
                if (!FileHelper::createDirectory($this->destPath, $this->dirMode)) {
                    return false;
                }
            }
            if (is_dir($this->destPath)) {
                $destPath = $this->destPath . DIRECTORY_SEPARATOR . basename($path);
                $opts = array_merge(
                    $options,
                    [
                        'dirMode' => $this->dirMode,
                        'fileMode' => $this->fileMode,
                    ]
                );
                if (!isset($opts['beforeCopy'])) {
                    if ($this->beforeCopy !== null) {
                        $opts['beforeCopy'] = $this->beforeCopy;
                    } else {
                        $opts['beforeCopy'] = function ($from, $to) {
                            return strncmp(basename($from), '.', 1) !== 0;
                        };
                    }
                }
                if (!isset($opts['afterCopy']) && $this->afterCopy !== null) {
                    $opts['afterCopy'] = $this->afterCopy;
                }
                FileHelper::copyDirectory($path, $destPath, $opts);
            }
        }
        return false;
    }
}