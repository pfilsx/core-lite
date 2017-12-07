<?php


namespace core\base;


final class ExceptionManager
{
    public $memoryReserveSize = 262144;

    private $_memoryReserve;


    public function register(){
        ini_set('display_errors', false);
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        if ($this->memoryReserveSize > 0) {
            $this->_memoryReserve = str_repeat('x', $this->memoryReserveSize);
        }
        register_shutdown_function([$this, 'handleFatalError']);
    }

    public function unregister()
    {
        restore_error_handler();
        restore_exception_handler();
    }


    public function handleError($code, $message, $file, $line){
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

    public function handleException($exception){
        $this->unregister();

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
            $msg .= (string) $e;
            $msg .= "\nPrevious exception:\n";
            $msg .= (string) $exception;
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

    public function handleFatalError(){
        unset($this->_memoryReserve);

        if (!class_exists('\core\exceptions\ErrorException', false)) {
            require_once(__DIR__ . '/../exceptions/ErrorException.php');
        }
        $error = error_get_last();
        if ($this->isFatalError($error)){
            $exception = new \core\exceptions\ErrorException($error['message'], $error['type'], $error['file'], $error['line']);
            $this->clearOutput();
            $this->renderException($exception);
            exit(1);
        }
    }

    /**
     * @param \Exception $exception
     */
    public function renderException($exception){
        if (CRL_DEBUG === true){
            $_params_ = ['exception' => $exception];
            ob_start();
            ob_implicit_flush(false);
            extract($_params_, EXTR_OVERWRITE);
            require(CRL_PATH.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'exception.php');
            echo ob_get_clean();
        }
        echo '';
    }

    public function renderFileLines($file, $line, $visible = false){
        $line--;
        $lines = $this->getFileLines($file, $line);
        $_params_ = ['lines' => $lines, 'line' => $line, 'visible' => $visible];
        ob_start();
        ob_implicit_flush(false);
        extract($_params_, EXTR_OVERWRITE);
        require(CRL_PATH.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'exceptionFile.php');
        return ob_get_clean();
    }

    public function renderRequest(){
        ob_start();
        ob_implicit_flush(false);
        require(CRL_PATH.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'exceptionRequest.php');
        return ob_get_clean();
    }

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
     * @param $file
     * @param $line
     * @return array
     * @internal param \Exception $exception
     */
    private function getFileLines($file, $line){
        $res = [];
        $firstLine = $line-7;
        $lastLine = $line+5;
        if (is_file($file)){
            $lines = @file($file);
            for ($i = $firstLine; $i <= $lastLine; $i ++){
                if (array_key_exists($i, $lines)){
                    $res[$i] = $lines[$i];
                }
            }
        }
        return $res;
    }
}