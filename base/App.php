<?php

namespace core\base;

use Core;

use core\components\Module;
use core\components\View;
use core\db\Connection;
use core\exceptions\ErrorException;
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
     * @var ExceptionManager
     */
    private $_exceptionManager = null;
    /**
     * @var string
     */
    private $_charset = 'UTF-8';

    /**
     * App constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->registerExceptionManager();
        $this->preInit($config);
        parent::__construct($config);
        unset($config);
    }
    /**
     * Registering exception manager for handling errors
     */
    private function registerExceptionManager(){
        $this->_exceptionManager = new ExceptionManager();
        $this->_exceptionManager->register();
    }
    /**
     * Main function of Application.
     * Call it for run app.
     *  (new \core\base\App($config))->run()
     * @return int
     */
    public function run()
    {
        $this->_response = new Response(isset($this->_config['response']) ? $this->_config['response'] : []);
        $response = $this->_router->route();
        $response->send();
        return $response->exitStatus;
    }
    /**
     * PreInitialize application. Setting default layout if not set and checking basePath
     * @param $config
     * @throws \Exception
     */
    private function preInit($config)
    {
        Core::$app = static::$instance = $this;
        if (!isset($config['basePath'])) {
            throw new \Exception('Invalid configuration. Missed required basePath in configuration');
        }
        if (!isset($config['view']['layout'])) {
            $this->_config['view']['layout'] = '@app/layouts/default';
        }
    }
    /**
    * @inheritDoc
    */
    public function init()
    {
        $this->_security = new Security();
        $this->_request = new Request((isset($this->_config['request']) ? $this->_config['request'] : []));
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
        $this->_assetManager = new AssetManager(isset($this->_config['assets']) ? $this->_config['assets']: []);
        $this->_session = new Session();

        if (isset($this->_config['auth'])){
            $instance = new $this->_config['auth']();
            if (!$instance instanceof BaseUser){
                throw new ErrorException('Invalid configuration. Auth class must be a BaseUser subclass');
            }
            $this->setUser($instance);
        }
        if (isset($this->_config['modules'])){
            foreach ($this->_config['modules'] as $options){
                if (isset($options['class'])){
                    $className = $options['class'];
                    unset($options['class']);
                    $module = new $className($options);
                    if ($module instanceof Module){
                        $module->initializeModule();
                        $this->_loadedModules[$module->getId()] = $module;
                    }
                }
            }
        }
        if (isset($this->_config['view']['renderer'])){
            View::$viewRenderer = $this->_config['view']['renderer'];
        }
        if (isset($this->_config['view']['extension'])){
            View::$defaultExtension = $this->_config['view']['extension'];
        }
    }
    /**
     * Get Connection instance
     * @return Connection
     */
    public function getDb()
    {
        return $this->_db;
    }
    /**
     * Get Router instance
     * @return Router
     */
    public function getRouter()
    {
        return $this->_router;
    }
    /**
     * Get basePath of application
     * @return string
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }
    /**
     * Set basePath of application. In best way must be called only from init() of application.
     * Change it on your own risk
     * @param string $path
     */
    public function setBasePath($path)
    {
        $this->_basePath = $path;
        Core::setAlias('@app', $this->getBasePath());
    }
    /**
     * Get AssetManager instance
     * @return AssetManager
     */
    public function getAssetManager()
    {
        return $this->_assetManager;
    }
    /**
     * Get Session instance
     * @return Session
     */
    public function getSession(){
        return $this->_session;
    }
    /**
     * Get full path to vendor directory
     * @return string
     */
    public function getVendorPath()
    {
        if ($this->_vendorPath === null) {
            $this->setVendorPath($this->getBasePath() . DIRECTORY_SEPARATOR . 'vendor');
        }

        return $this->_vendorPath;
    }
    /**
     * Set full path to vendor directory
     * @param $path - path to vendor dir
     */
    public function setVendorPath($path)
    {
        $this->_vendorPath = Core::getAlias($path);
        Core::setAlias('@vendor', $this->_vendorPath);
        Core::setAlias('@bower', $this->_vendorPath . DIRECTORY_SEPARATOR . 'bower');
        Core::setAlias('@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm');
    }
    /**
     * Get Request instance
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }
    /**
     * Get Response instance
     * @return Response
     */
    public function getResponse(){
        return $this->_response;
    }
    /**
     * Get BaseUser instance
     * @return BaseUser
     */
    public function getUser()
    {
        return $this->_user;
    }
    /**
     * Set BaseUser instance
     * @param BaseUser $value
     */
    public function setUser($value){
        $this->_user = $value;
    }
    /**
     * Get charset of application(default UTF-8)
     * @return string
     */
    public function getCharset()
    {
        return $this->_charset;
    }
    /**
     * Get Security instance
     * @return Security
     */
    public function getSecurity(){
        return $this->_security;
    }
    /**
     * Get module by id or null if module does not exist
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
     * Translate string line by specified dictionary
     * @param string $dictionary - dictionary name
     * @param string $message - message template
     * @param array $params - params for replacing
     * @return string - translated message
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