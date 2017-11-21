<?php


namespace core\console;


use Core;
use core\base\BaseObject;
use core\db\Connection;
use core\helpers\Console;


defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));
defined('STDERR') or define('STDERR', fopen('php://stderr', 'w'));

/**
 * Class App
 * @property Request request
 * @property Connection db
 * @package core\console
 */
final class App extends BaseObject
{

    /**
     * @var App
     */
    public static $instance;

    private $_request;

    private $_db;

    public function run(){
        try {
            Core::$app = static::$instance = $this;
            $this->setBasePath($this->_config['basePath']);
            if (isset($this->_config['db'])){
                $this->_db = new Connection($this->_config['db']);
            }
            $this->_request = new Request();
            $router = new Router();
            return $router->route();

        } catch (\Exception $ex) {
            Console::output($ex->getMessage());
            Console::output($ex->getTraceAsString());
        }
        return 1;
    }

    public function getRequest(){
        return $this->_request;
    }
    public function getDb(){
        return $this->_db;
    }

    private function setBasePath($path)
    {
        Core::setAlias('@app', $path);
    }
}