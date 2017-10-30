<?php


namespace core\base;


final class ExceptionManager
{
    /**
     * @param \Exception $exception
     * @return null|string
     */
    public function renderException($exception){
        if (CRL_DEBUG === true){
            $lines = $this->getFileLines($exception);
            $_params_ = ['exception' => $exception, 'lines' => $lines];
            ob_start();
            ob_implicit_flush(false);
            extract($_params_, EXTR_OVERWRITE);
            require(CRL_PATH.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'exception.php');
            return ob_get_clean();
        }
        return null;
    }

    /**
     * @param \Exception $exception
     * @return array
     */
    private function getFileLines($exception){
        $filePath = $exception->getFile();
        $res = [];
        $firstLine = $exception->getLine()-5;
        $lastLine = $exception->getLine()+5;
        if (is_file($filePath)){
            $lines = file($filePath);
            for ($i = $firstLine; $i <= $lastLine; $i ++){
                if (array_key_exists($i-1, $lines)){
                    $res[$i] = $lines[$i -1];
                }
            }
        }
        return $res;
    }
}