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
            $webrootPath = realpath(FileHelper::normalizePath(Core::getAlias('@webroot')));
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
                if ((!empty($options['forceCopy']) && $options['forceCopy']) || !is_dir($destPath)){
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