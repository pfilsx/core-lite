<?php


namespace core\base;

use Core;
use core\components\AssetBundle;
use core\helpers\FileHelper;

class AssetManager extends BaseObject
{

    private $_basicFontsPath = '@crl/assets/fonts';

    private $_bundles = [];

    function __construct($config = [])
    {
        parent::__construct($config);
    }

    public function init(){
        $this->placeFonts();
        foreach ($this->_config as $bundle){
            if (new $bundle() instanceof AssetBundle){
                $this->_bundles[] = $bundle;
            }
        }
    }


    private function placeFonts(){
        $dependPath = FileHelper::normalizePath(Core::getAlias($this->_basicFontsPath));
        if (is_dir($dependPath)){
            $newPath = FileHelper::normalizePath(Core::getAlias('@webroot').'\\assets\\fonts\\');
            if (!is_dir($newPath)){
                if (!FileHelper::createDirectory($newPath)){
                    return;
                }
            }
            foreach (FileHelper::findFiles($dependPath) as $file){
                $newFilePath = $newPath.DIRECTORY_SEPARATOR.basename($file);
                if (!is_file($newFilePath)){
                    copy($file, $newFilePath);
                }
            }
        }
    }

    public function registerAsset($path){
        $path = FileHelper::normalizePath(Core::getAlias($path));
        if (is_file($path)){
            $assetPath = FileHelper::normalizePath(Core::getAlias('@webroot').'\\assets\\'.basename($path));
            if (is_file($assetPath)){
                if (md5_file($assetPath) !== md5_file($path)){
                    copy($path, $assetPath);
                }
            } else {
                if (FileHelper::createDirectory(dirname($assetPath))){
                    copy($path, $assetPath);
                }
            }
             return 'assets/'.basename($assetPath);
        }
        return null;
    }

    public function registerBundles(){
        if (!empty($this->_bundles) && App::$instance->view != null){
            foreach ($this->_bundles as $bundle){
                $bundle::registerAssets();
            }
        }
    }

}