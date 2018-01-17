<?php


namespace core\base;


use core\components\View;
use core\helpers\Console;
use core\web\App;
use core\web\Response;

final class ExceptionManager extends BaseObject
{
    /**
     * Memory size to reserve for fatal errors handling
     * @var int
     */
    public $memoryReserveSize = 262144;
    /**
     * @var null|\Exception
     */
    public $exception = null;

    private $_memoryReserve;

    const EVENT_BEFORE_RENDER = 'exception_before_render';
    const EVENT_AFTER_RENDER = 'exception_after_render';

    /**
     * Register default core exception|error handlers
     */
    public function register()
    {
        ini_set('display_errors', false);
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        if ($this->memoryReserveSize > 0) {
            $this->_memoryReserve = str_repeat('x', $this->memoryReserveSize);
        }
        register_shutdown_function([$this, 'handleFatalError']);
    }
    /**
     * Unregister error and exception core handlers
     */
    public function unregister()
    {
        restore_error_handler();
        restore_exception_handler();
    }
    /**
     * Handle errors and translate them to exception for handling
     * @param $code - error code
     * @param $message - error message
     * @param $file - error file
     * @param $line - error line
     * @return false - if error_reporting is disabled
     * @throws \core\exceptions\ErrorException - exception for handling
     */
    public function handleError($code, $message, $file, $line)
    {
        if (error_reporting() & $code) {
            // load ErrorException manually here because autoloading them will not work
            // when error occurs while autoloading a class
            if (!class_exists('\core\exceptions\ErrorException', false)) {
                require_once(__DIR__ . '/../exceptions/ErrorException.php');
            }
            $exception = new \core\exceptions\ErrorException($message, $code, $file, $line);

            // in case error appeared in __toString method we can't throw any exception
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
            foreach ($trace as $frame) {
                if ($frame['function'] === '__toString') {
                    $this->handleException($exception);
                    if (defined('HHVM_VERSION')) {
                        flush();
                    }
                    exit(1);
                }
            }
            throw $exception;
        }
        return false;
    }
    /**
     * Handle exceptions and print them to output
     * @param \Exception $exception
     */
    public function handleException($exception)
    {
        $this->unregister();
        $this->exception = $exception;

        if (PHP_SAPI !== 'cli') {
            http_response_code(500);
        }

        try {
            $this->clearOutput();
            $this->renderException($exception);
            exit(1);
        } catch (\Exception $e) {
            // an other exception could be thrown while displaying the exception
            $msg = "An Error occurred while handling another error:\n";
            $msg .= (string)$e;
            $msg .= "\nPrevious exception:\n";
            $msg .= (string)$exception;
            if (CRL_DEBUG) {
                if (PHP_SAPI === 'cli') {
                    echo $msg . "\n";
                } else {
                    echo '<pre>' . htmlspecialchars($msg, ENT_QUOTES, App::$instance->charset) . '</pre>';
                }
            } else {
                echo 'An internal server error occurred.';
            }
            error_log($msg);
            if (defined('HHVM_VERSION')) {
                flush();
            }
            exit(1);
        }
    }
    /**
     * Handle fatal errors and translating them into exception for displaying
     */
    public function handleFatalError()
    {
        unset($this->_memoryReserve);

        if (!class_exists('\core\exceptions\ErrorException', false)) {
            require_once(__DIR__ . '/../exceptions/ErrorException.php');
        }
        $error = error_get_last();
        if ($this->isFatalError($error)) {
            $this->exception = new \core\exceptions\ErrorException($error['message'], $error['type'], $error['file'], $error['line']);
            $this->clearOutput();
            $this->renderException($this->exception);
            exit(1);
        }
    }

    /**
     * Render exception to output if CRL_DEBUG enabled
     * @param \Exception $exception
     */
    public function renderException($exception)
    {
        if (\Core::$app instanceof \core\console\App){
            Console::output($exception->getMessage(), Console::FG_RED);
            Console::output('Stack trace:');
            Console::output($exception->getTraceAsString());
        } else {
            $response = new Response();
            $this->invoke(static::EVENT_BEFORE_RENDER, ['exception' => $exception, 'response' => $response]);
            if (CRL_DEBUG === true) {
                $_params_ = ['exception' => $exception];
                $response->content = View::renderPartial(CRL_PATH.'/view/exception.php', $_params_);
            } else {
                $response->content = '';
            }
            $this->invoke(static::EVENT_AFTER_RENDER, ['exception' => $exception, 'response' => $response]);
            $response->setStatusCode($exception->getCode() < 100 || $exception->getCode() > 600 ? 500 : $exception->getCode());
            $response->send();
        }
    }
    /**
     * Render lines from file with exception
     * @param $file - path to file
     * @param $line - line with exception
     * @param bool $visible - indicates whether block must be visible
     * @return string - rendered content
     */
    public static function renderFileLines($file, $line, $visible = false)
    {
        $line--;
        $lines = static::getFileLines($file, $line);
        $_params_ = ['lines' => $lines, 'line' => $line, 'visible' => $visible];
        return View::renderPartial(CRL_PATH.'/view/exceptionFile.php', $_params_);
    }
    /**
     * Indicates whether error is fatal
     * @param array $error
     * @return bool
     */
    private function isFatalError($error)
    {
        return isset($error['type']) && in_array($error['type'], [
                E_ERROR,
                E_PARSE,
                E_CORE_ERROR,
                E_CORE_WARNING,
                E_COMPILE_ERROR,
                E_COMPILE_WARNING
            ]);
    }

    /**
     * Clear current output buffers
     */
    private function clearOutput()
    {
        // the following manual level counting is to deal with zlib.output_compression set to On
        for ($level = ob_get_level(); $level > 0; --$level) {
            if (!@ob_end_clean()) {
                ob_clean();
            }
        }
    }

    /**
     * Get lines from file with exception
     * @param $file - path to file
     * @param $line - line with exception number
     * @return array - lines
     */
    private static function getFileLines($file, $line)
    {
        $res = [];
        $firstLine = $line - 7;
        $lastLine = $line + 5;
        if (is_file($file)) {
            $lines = @file($file);
            for ($i = $firstLine; $i <= $lastLine; $i++) {
                if (array_key_exists($i, $lines)) {
                    $res[$i] = $lines[$i];
                }
            }
        }
        return $res;
    }
}