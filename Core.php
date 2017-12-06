<?php

require(__DIR__.DIRECTORY_SEPARATOR.'exceptions'.DIRECTORY_SEPARATOR.'WarningException.php');
require(__DIR__ . DIRECTORY_SEPARATOR .'BaseCore.php');


class Core extends \core\BaseCore
{

}

defined('CRL_PATH') or define('CRL_PATH', __DIR__);

defined('CRL_DEBUG') or define('CRL_DEBUG', true);
defined('CRL_ENV') or define('CRL_ENV', 'dev');

Core::$classMap = require(__DIR__ . '/classMap.php');

spl_autoload_register(['Core', 'autoload'], true, true);

ini_set('display_errors', false);
//set_exception_handler([$this, 'handleException']);
set_error_handler(['Core', 'handleError']);
register_shutdown_function(['Core', 'handleFatalError']);