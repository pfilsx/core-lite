<?php


namespace core\console\controllers;


use Core;
use core\components\Controller;
use core\console\App;
use core\exceptions\ErrorException;
use core\helpers\Console;
use core\helpers\FileHelper;

class AssetController extends Controller
{
    public $bundles = [];
    /**
     * @var string|callable JavaScript file compressor.
     * If a string, it is treated as shell command template, which should contain
     * placeholders {from} - source file name - and {to} - output file name.
     * Otherwise, it is treated as PHP callback, which should perform the compression.
     *
     * Default value relies on usage of "Closure Compiler"
     * @see https://developers.google.com/closure/compiler/
     */
    public $jsCompressor = 'java -jar compiler.jar --js {from} --js_output_file {to}';
    /**
     * @var string|callable CSS file compressor.
     * If a string, it is treated as shell command template, which should contain
     * placeholders {from} - source file name - and {to} - output file name.
     * Otherwise, it is treated as PHP callback, which should perform the compression.
     *
     * Default value relies on usage of "YUI Compressor"
     * @see https://github.com/yui/yuicompressor/
     */
    public $cssCompressor = 'java -jar yuicompressor.jar --type css {from} -o {to}';

    /**
     * @var bool whether to delete asset source files after compression.
     * @since 2.0.10
     */
    public $deleteSource = false;

    /**
     * @var \core\base\AssetManager [[\core\base\AssetManager]] instance, which will be used
     * for assets processing.
     */
    private $_assetManager;

    /**
     * Returns the asset manager instance.
     * @throws ErrorException on invalid configuration.
     * @return \core\base\AssetManager asset manager instance.
     */
    public function getAssetManager()
    {
        if (!is_object($this->_assetManager)) {
            $options = $this->_assetManager;
            if (!isset($options['class'])) {
                $options['class'] = 'core\\base\\AssetManager';
            }
            if (!isset($options['basePath'])) {
                throw new ErrorException("Please specify 'basePath' for the 'assetManager' option.");
            }
            if (!isset($options['baseUrl'])) {
                throw new ErrorException("Please specify 'baseUrl' for the 'assetManager' option.");
            }
            if (!isset($options['forceCopy'])) {
                $options['forceCopy'] = true;
            }
            $this->_assetManager = new $options['class']($options);
        }
        return $this->_assetManager;
    }


    public function actionIndex()
    {
        return $this->actionCompress();
    }

    public function actionCompress()
    {
        if (!isset(App::$instance->request->args[0])){
            throw new ErrorException("Missed configuration file param");
        }
        if (!isset(App::$instance->request->args[1])){
            throw new ErrorException("Missed bundle file param");
        }
        $configFile = FileHelper::normalizePath(Core::getAlias('@app').'/'.App::$instance->request->args[0]);
        $bundleFile = FileHelper::normalizePath(Core::getAlias('@app').'/'.App::$instance->request->args[1]);

        $this->loadConfiguration($configFile);
        Console::output("Configuration loaded");
    }

    /**
     * Applies configuration from the given file to self instance.
     * @param string $configFile configuration file name.
     * @throws ErrorException on failure.
     */
    protected function loadConfiguration($configFile)
    {
        Console::output("Loading configuration from '{$configFile}'...");
        $config = require $configFile;
        foreach ($config as $name => $value) {
            if (property_exists($this, $name) || $this->canSetProperty($name)) {
                $this->$name = $value;
            } else {
                throw new ErrorException("Unknown configuration option: $name");
            }
        }

        $this->getAssetManager(); // check if asset manager configuration is correct
    }

}