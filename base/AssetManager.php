<?php


namespace core\base;

use Core;
use core\helpers\FileHelper;

class AssetManager extends BaseObject
{

    private $_jsAssets = [];

    private $_cssAssets = [];

    protected $_config = [
        'js' => [],
        'css' => [],
        'depends' => [
            '@crl/assets/crl.style.css'
        ]
    ];

    function __construct($config = [])
    {
        parent::__construct($config);
    }

    public function init(){
        $this->_jsAssets = $this->_config['js'];
        $this->_cssAssets = $this->_config['css'];
        if ($this->_config !== false){
            $this->replaceDepends();
        }
    }

    public function registerJsAssets()
    {
        $result = '';
        foreach ($this->_jsAssets as $asset) {
            $result .= '<script type="text/javascript" src="' . App::$instance->request->getBaseUrl() .'/'. $asset . '"></script>';
        }
        return $result;
    }

    public function registerCssAssets()
    {
        $result = '';
        foreach ($this->_cssAssets as $asset) {
            $result .= '<link rel="stylesheet" href="' . App::$instance->request->getBaseUrl() .'/'. $asset . '">';
        }
        return $result;
    }
    public function addDepend($path){
        $path = FileHelper::normalizePath(Core::getAlias($path));
        if (is_file($path)){
            $newPath = FileHelper::normalizePath(Core::getAlias('@webroot').'\\assets\\'.basename($path));
            if (is_file($newPath)){
                if (md5_file($newPath) !== md5_file($path)){
                    copy($path, $newPath);
                }
            } else {
                if (FileHelper::createDirectory(dirname($newPath))){
                    copy($path, $newPath);
                }
            }
            $fileName = 'assets/'.basename($newPath);
            if (!in_array($fileName,$this->_cssAssets) && !in_array($fileName, $this->_jsAssets)){
                $ext = pathinfo($newPath, PATHINFO_EXTENSION);
                if ($ext == 'js'){
                    $this->_jsAssets[] = $fileName;
                } else {
                    $this->_cssAssets[] = $fileName;
                }
            }
        }
    }

    private function replaceDepends(){
        if (is_array($this->_config['depends']) && !empty($this->_config['depends'])){
            foreach ($this->_config['depends'] as $depend){
                $dependPath = Core::getAlias($depend);
                if (is_file($dependPath)){
                    $newPath = FileHelper::normalizePath(Core::getAlias('@webroot').'\\assets\\'.basename($dependPath));
                    if (is_file($newPath)){
                        if (md5_file($newPath) !== md5_file($dependPath)){
                            copy($dependPath, $newPath);
                        }
                    } else {
                        if (FileHelper::createDirectory(dirname($newPath))){
                            copy($dependPath, $newPath);
                        }
                    }
                    $fileName = 'assets/'.basename($newPath);
                    if (!in_array($fileName,$this->_cssAssets) && !in_array($fileName, $this->_jsAssets)){
                        $ext = pathinfo($newPath, PATHINFO_EXTENSION);
                        if ($ext == 'js'){
                            $this->_jsAssets[] = $fileName;
                        } else {
                            $this->_cssAssets[] = $fileName;
                        }
                    }
                }
            }
        }
    }
}