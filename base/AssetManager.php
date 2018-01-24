<?php


namespace core\base;

use Core;
use core\components\AssetBundle;
use core\exceptions\ErrorException;
use core\helpers\FileHelper;
use core\web\App;

class AssetManager extends BaseObject
{

    private $_bundles = [];

    /**
     * List of registered bundles
     * @var array
     */
    public $registeredBundles = [];

    /**
     * Directory access mode for assets directories
     * @var int
     */
    public $dirMode = 0775;

    private $destPath;

    private $_baseUrl;

    public $beforeCopy;
    /**
     * File access mode for assets files in assets directory
     * @var int
     */
    public $fileMode = 0777;

    public $afterCopy;

    /**
     * AssetManager constructor.
     * @param array $config
     */
    function __construct($config = [])
    {
        $this->destPath = FileHelper::normalizePath(Core::getAlias('@webroot/assets'));
        $this->_baseUrl = Core::getAlias('@web/assets');
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
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
    /**
     * Register predefined asset bundles
     */
    public function registerBundles()
    {
        if (!empty($this->_bundles) && App::$instance->view != null) {
            foreach ($this->_bundles as $bundle) {
                $bundle::register();
            }
        }
    }
    /**
     * Clear all predefined bundles
     */
    public function clearBundles()
    {
        $this->_bundles = [];
    }

    /**
     * Register specified asset bundle
     * @param $className
     */
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
        $cssAssets = $bundle->cssAssets();
        $jsAssets = $bundle->jsAssets();
        if (!empty($depends)) {
            foreach ($depends as $subBundle) {
                if (class_exists($subBundle) && is_subclass_of($subBundle, AssetBundle::className())) {
                    $this->registerBundle($subBundle);
                }
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

    private $_published = [];

    public function publish($path, $options = []){
        $path = Core::getAlias($path);
        if (isset($this->_published[$path])) {
            return $this->_published[$path];
        }
        if (!is_string($path) || ($src = realpath($path)) === false) {
            throw new ErrorException("The file or directory to be published does not exist: $path");
        }
        if (is_file($src)) {
            return $this->_published[$path] = $this->publishFile($src);
        }

        return $this->_published[$path] = $this->publishDirectory($src, $options);
    }

    /**
     * Publish file to assets directory
     * @param $path
     * @return bool|array
     */
    public function publishFile($path)
    {
        $path = FileHelper::normalizePath(Core::getAlias($path));
        if (is_file($path)) {
            //if file already in webroot directory
            $webrootPath = FileHelper::normalizePath(Core::getAlias('@webroot'));
            if (substr($path, 0, strlen($webrootPath)) == $webrootPath){
                return [
                    $path,
                    Core::getAlias('@web').'/'.ltrim(str_replace([$webrootPath, '\\'],['','/'], $path), '/')
                ];
            } else {
                $md5 = md5_file($path);
                $baseName = basename($path);
                $assetPath = FileHelper::normalizePath("{$this->destPath}/$md5/$baseName");
                if (is_file($assetPath)) {
                    if (md5_file($assetPath) !== $md5) {
                        copy($path, $assetPath);
                        @chmod($assetPath, $this->fileMode);
                    }
                } else {
                    if (FileHelper::createDirectory(dirname($assetPath), $this->dirMode)) {
                        copy($path, $assetPath);
                        @chmod($assetPath, $this->fileMode);
                    }
                }
                return [$assetPath, "{$this->_baseUrl}/$md5/$baseName"];
            }
        }
        return false;
    }

    /**
     * Publish directory to web accessible place
     * @param $path
     * @param $options
     * @return bool|array
     */
    public function publishDirectory($path, $options)
    {
        if (is_dir($path)) {
            $md5 = md5($path);
            if (!is_dir($this->destPath)) {
                if (!FileHelper::createDirectory($this->destPath, $this->dirMode)) {
                    return false;
                }
            }
            if (is_dir($this->destPath)) {
                $destPath = FileHelper::normalizePath("{$this->destPath}/$md5");
                if ($options['forceCopy'] || !is_dir($destPath)){
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
                return [$destPath, "$this->_baseUrl/$md5"];
            }
        }
        return false;
    }
}