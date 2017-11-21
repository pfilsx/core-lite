<?php

namespace core\base;

use Core;

use core\components\Module;
use core\components\View;
use core\db\Connection;
use core\translate\TranslateManager;
use core\web\Session;

/**
 * Class App
 * @property Request request
 * @property Response response
 * @property Connection db
 * @property string basePath
 * @property Security security
 * @property array config
 * @property AssetManager assetManager
 * @property BaseUser user
 * @property string charset
 * @property Router router
 * @property Session session
 * @package core\base
 */
final class App extends BaseObject
{
    /**
     * @var App
     */
    public static $instance;

    /**
     * @var Connection
     */
    private $_db;

    /**
     * @var Router
     */
    private $_router;

    /**
     * @var string
     */
    private $_basePath;

    /**
     * @var AssetManager
     */
    private $_assetManager;

    /**
     * @var Request
     */
    private $_request;

    /**
     * @var Response
     */
    private $_response;

    /**
     * @var Security
     */
    private $_security;
    /**
     * @var Session
     */
    private $_session;

    /**
     * @var TranslateManager
     */
    private $_translateManager;

    /**
     * @var BaseUser
     */
    private $_user;
    /**
     * @var Module[]
     */
    private $_loadedModules = [];
    /**
     * @var string
     */
    private $_vendorPath;

    /**
     * @var null|View
     */
    public $view = null;


    /**
     * @var string
     */
    private $_charset = 'UTF-8';

    public function __construct($config = [])
    {
        try {
            $this->preInit($config);
            parent::__construct($config);
            unset($config);
        } catch (\Exception $ex) {
            echo (new ExceptionManager())->renderException($ex);
        }
    }

    public function run()
    {
        try {
            $this->_response = new Response();
            $response = $this->_router->route();
            $response->send();
            return $response->exitStatus;
        } catch (\Exception $ex) {
            $response = new Response();
            $response->content = (new ExceptionManager())->renderException($ex);
            $response->send();
            return $ex->getCode();
        }
    }

    private function preInit($config)
    {
        Core::$app = static::$instance = $this;
        if (isset($config['basePath'])) {
            $this->setBasePath($config['basePath']);
        } else {
            throw new \Exception('Invalid configuration. Missed required basePath in configuration');
        }
        if (!isset($config['routing']['layout'])) {
            $this->_config['routing']['layout'] = '@app/layouts/default';
        }
    }

    public function init()
    {
        $this->_security = new Security();
        $this->_request = new Request();
        $this->_router = new Router(isset($this->_config['routing']) ? $this->_config['routing'] : []);
        if (isset($this->_config['db'])){
            $this->_db = new Connection($this->_config['db']);
            unset($this->_config['db']);
        }
        Core::setAlias('@web', $this->_request->baseUrl);
        Core::setAlias('@webroot', dirname($this->_request->scriptFile));
        Core::setAlias('@crl', CRL_PATH);
        if (isset($this->_config['vendorPath'])){
            $this->setVendorPath($this->_config['vendorPath']);
            unset($this->_config['vendorPath']);
        } else {
            $this->getVendorPath();
        }
        $this->_translateManager = new TranslateManager();
        if (isset($this->_config['assets'])){
            $this->_assetManager = new AssetManager($this->_config['assets']);
        } else {
            $this->_assetManager = new AssetManager();
        }
        $this->_session = new Session();
        if (isset($this->_config['auth'])){
            $instance = new $this->_config['auth']();
            if (!$instance instanceof BaseUser){
                throw new \Exception('Invalid configuration. Auth class must be a BaseUser instance');
            }
            $this->setUser($instance);
        }
        if (isset($this->_config['modules'])){
            foreach ($this->_config['modules'] as $id => $options){
                if (isset($options['class'])){
                    $className = $options['class'];
                    unset($options['class']);
                    $module = new $className();
                    if ($module instanceof Module){
                        $module->setId($id);
                        $module->initializeModule($options);
                        $this->_loadedModules[$id] = $module;
                    }
                }
            }
        }
    }

    public function getDb()
    {
        return $this->_db;
    }

    public function getRouter()
    {
        return $this->_router;
    }

    public function getBasePath()
    {
        return $this->_basePath;
    }

    private function setBasePath($path)
    {
        $this->_basePath = $path;
        Core::setAlias('@app', $this->getBasePath());
    }

    public function getAssetManager()
    {
        return $this->_assetManager;
    }
    public function getSession(){
        return $this->_session;
    }
    public function getVendorPath()
    {
        if ($this->_vendorPath === null) {
            $this->setVendorPath($this->getBasePath() . DIRECTORY_SEPARATOR . 'vendor');
        }

        return $this->_vendorPath;
    }

    public function setVendorPath($path)
    {
        $this->_vendorPath = Core::getAlias($path);
        Core::setAlias('@vendor', $this->_vendorPath);
        Core::setAlias('@bower', $this->_vendorPath . DIRECTORY_SEPARATOR . 'bower');
        Core::setAlias('@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm');
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @return Response
     */
    public function getResponse(){
        return $this->_response;
    }

    public function getUser()
    {
        return $this->_user;
    }
    public function setUser($value){
        $this->_user = $value;
    }

    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * @return Security
     */
    public function getSecurity(){
        return $this->_security;
    }

    /**
     * @param $id
     * @return Module|null
     */
    public function getModule($id){
        if (array_key_exists($id, $this->_loadedModules)){
            return $this->_loadedModules[$id];
        }
        return null;
    }

    /**
     * @param string $dictionary
     * @param string $message
     * @param array $params
     * @return string
     */
    public function translate($dictionary, $message, $params = []){
        $placeholders = [];
        foreach ($params as $key => $value){
            $placeholders['{'.$key.'}'] = $value;
        }
        if ($this->_translateManager == null){
            return strtr($message, $placeholders);
        }
        return $this->_translateManager->translate($dictionary, $message, $placeholders, $this->request->userLanguage);
    }
}