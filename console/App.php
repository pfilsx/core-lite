<?php


namespace core\console;


use Core;
use core\base\BaseApp;
use core\web\Response;


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
        $this->_response = new Response();
        parent::init();
    }
    /**
     * Main function of Application.
     * Call it for run app.
     *  (new \core\console\App($config))->run()
     * @return int
     */
    public function run(){
        return $this->_router->route();
    }
}