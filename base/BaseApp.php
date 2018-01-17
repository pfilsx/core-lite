<?php


namespace core\base;


use Core;
use core\components\Module;
use core\db\Connection;
use core\exceptions\ErrorException;
use core\translate\TranslateManager;
/**
    * @property \core\web\Request|\core\console\Request request
    * @property \core\web\Response response
    * @property Connection db
    * @property string basePath
    * @property Security security
    * @property array config
    * @property BaseUser user
    * @property string charset
    * @property \core\web\Router|\core\console\Router router
*/
abstract class BaseApp extends BaseObject
{
    /**
     * @var \core\web\App|\core\console\App
     */
    public static $instance;
    /**
     * @var Connection
     */
    protected $_db;
    /**
     * @var \core\web\Router|\core\console\Router
     */
    protected $_router;
    /**
     * @var string
     */
    protected $_basePath;
    /**
     * @var \core\web\Request|\core\console\Request
     */
    protected $_request;
    /**
     * @var \core\web\Response
     */
    protected $_response;
    /**
     * @var Security
     */
    protected $_security;
    /**
     * @var TranslateManager
     */
    protected $_translateManager;
    /**
     * @var Module[]
     */
    protected $_loadedModules = [];
    /**
     * @var array
     */
    protected $_components = [];
    /**
     * @var string
     */
    protected $_vendorPath;
    /**
     * @var string
     */
    protected $_charset = 'UTF-8';

    /**
     * @var ExceptionManager
     */
    protected $_exceptionManager = null;

    public function __construct(array $config = [])
    {
        $this->registerExceptionManager();
        $this->preInit($config);
        parent::__construct($config);
        unset($config);
    }

    /**
     * Registering exception manager for handling errors
     */
    protected function registerExceptionManager()
    {
        $this->_exceptionManager = new ExceptionManager();
        $this->_exceptionManager->register();
    }

    protected function preInit($config)
    {
        Core::$app = static::$instance = $this;
        if (!isset($config['basePath'])) {
            throw new \Exception('Invalid configuration. Missed required basePath in configuration');
        }
    }
    public function init(){
        $this->_security = new Security();
        if (isset($this->_config['db'])) {
            $this->_db = new Connection($this->_config['db']);
            unset($this->_config['db']);
        }
        Core::setAlias('@crl', CRL_PATH);
        if (isset($this->_config['vendorPath'])) {
            $this->setVendorPath($this->_config['vendorPath']);
            unset($this->_config['vendorPath']);
        } else {
            $this->getVendorPath();
        }
        $this->_translateManager = new TranslateManager();

        if (isset($this->_config['modules']) && is_array($this->_config['modules'])) {
            foreach ($this->_config['modules'] as $options) {
                $module = $this->createComponent($options);
                if ($module != null && $module instanceof Module) {
                    $module->initializeModule();
                    $this->_loadedModules[$module->getId()] = $module;
                }
            }
        }
        if (isset($this->_config['components']) && is_array($this->_config['components'])) {
            foreach ($this->_config['components'] as $key => $options) {
                $component = $this->createComponent($options);
                if ($component != null) {
                    $this->_components[$key] = $component;
                }
            }
        }
    }
    public abstract function run();
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

    private function createComponent($options)
    {
        if (isset($options['class'])) {
            $className = $options['class'];
            unset($options['class']);
            if (class_exists($className)) {
                return new $className($options);
            }
        }
        return null;
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
     * @return \core\web\Router|\core\console\Router
     */
    public function getRouter()
    {
        return $this->_router;
    }
    /**
     * Get Request instance
     * @return \core\web\Request|\core\console\Request
     */
    public function getRequest()
    {
        return $this->_request;
    }
    /**
     * Get Response instance
     * @return \core\web\Response
     */
    public function getResponse()
    {
        return $this->_response;
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
    public function getSecurity()
    {
        return $this->_security;
    }
    /**
     * Get module by id or null if module does not exist
     * @param $id
     * @return Module|null
     */
    public function getModule($id)
    {
        if (array_key_exists($id, $this->_loadedModules)) {
            return $this->_loadedModules[$id];
        }
        return null;
    }
    /**
     * @return ExceptionManager
     */
    public function getExceptionManager(){
        return $this->_exceptionManager;
    }

    /**
     * Translate string line by specified dictionary
     * @param string $dictionary - dictionary name
     * @param string $message - message template
     * @param array $params - params for replacing
     * @return string - translated message
     */
    public function translate($dictionary, $message, $params = [])
    {
        $placeholders = [];
        foreach ($params as $key => $value) {
            $placeholders['{' . $key . '}'] = $value;
        }
        if ($this->_translateManager == null) {
            return strtr($message, $placeholders);
        }
        return $this->_translateManager->translate($dictionary, $message, $placeholders, $this->request->userLanguage);
    }

    //region magic

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (isset($this->_components[$name])) {
            return $this->_components[$name];
        } elseif (method_exists($this, 'set' . ucfirst($name))) {
            throw new ErrorException('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new ErrorException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (isset($this->_components[$name])) {
            $this->_components[$name] = $value;
        } elseif (method_exists($this, 'get' . ucfirst($name))) {
            throw new ErrorException('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new ErrorException('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } elseif (isset($this->_components[$name])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function __unset($name)
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } elseif (isset($this->_components[$name])) {
            $this->_components[$name] = null;
        } elseif (method_exists($this, 'get' . ucfirst($name))) {
            throw new ErrorException('Unsetting read-only property: ' . get_class($this) . '::' . $name);
        }
    }

    //endregion
}