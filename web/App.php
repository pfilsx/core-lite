<?php

namespace core\web;

use Core;

use core\base\AssetManager;
use core\base\BaseApp;
use core\base\BaseObject;
use core\base\BaseUser;
use core\base\ExceptionManager;
use core\base\Security;
use core\components\Module;
use core\components\View;
use core\db\Connection;
use core\exceptions\ErrorException;
use core\translate\TranslateManager;

/**
 * Class App
 * @property AssetManager assetManager
 * @property Session session
 * @package core\web
 */
final class App extends BaseApp
{
    /**
     * @var AssetManager
     */
    private $_assetManager;
    /**
     * @var Session
     */
    private $_session;
    /**
     * @var BaseUser
     */
    private $_user;
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
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        $this->registerExceptionManager();
        parent::__construct($config);
    }

    /**
     * Registering exception manager for handling errors
     */
    private function registerExceptionManager()
    {
        $this->_exceptionManager = new ExceptionManager();
        $this->_exceptionManager->register();
    }

    /**
     * Main function of Application.
     * Call it for run app.
     *  (new \core\web\App($config))->run()
     * @return int
     * @throws \Exception
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
    protected function preInit($config)
    {
        parent::preInit($config);
        if (!isset($config['view']['layout'])) {
            $this->_config['view']['layout'] = '@app/layouts/default';
        }
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->_request = new Request((isset($this->_config['request']) ? $this->_config['request'] : []));
        $this->_router = new Router(isset($this->_config['routing']) ? $this->_config['routing'] : []);
        Core::setAlias('@web', $this->_request->baseUrl);
        Core::setAlias('@webroot', dirname($this->_request->scriptFile));
        $this->_assetManager = new AssetManager(isset($this->_config['assets']) ? $this->_config['assets'] : []);
        $this->_session = new Session();
        parent::init();
        if (isset($this->_config['auth'])) {
            $instance = new $this->_config['auth']();
            if (!$instance instanceof BaseUser) {
                throw new ErrorException('Invalid configuration. Auth class must be a BaseUser subclass');
            }
            $this->setUser($instance);
        }
        if (isset($this->_config['view']['renderer'])) {
            View::$viewRenderer = $this->_config['view']['renderer'];
        }
        if (isset($this->_config['view']['extension'])) {
            View::$defaultExtension = $this->_config['view']['extension'];
        }
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
    public function getSession()
    {
        return $this->_session;
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
    public function setUser($value)
    {
        $this->_user = $value;
    }
    /**
     * @return ExceptionManager
     */
    public function getExceptionManager(){
        return $this->_exceptionManager;
    }

}