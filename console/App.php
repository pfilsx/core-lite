<?php


namespace core\console;


use Core;
use core\base\BaseApp;
use core\base\BaseObject;
use core\db\Connection;
use core\helpers\Console;


defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));
defined('STDERR') or define('STDERR', fopen('php://stderr', 'w'));

/**
 * Class App
 * @package core\console
 */
final class App extends BaseApp
{

    public function init(){
        $this->_request = new Request();
        $this->_router = new Router();
        parent::init();
    }
    /**
     * Main function of Application.
     * Call it for run app.
     *  (new \core\console\App($config))->run()
     * @return int
     */
    public function run(){
        try {
            return $this->_router->route();
        } catch (\Exception $ex) {
            Console::output($ex->getMessage());
            Console::output($ex->getTraceAsString());
        }
        return 1;
    }
}