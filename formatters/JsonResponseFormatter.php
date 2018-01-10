<?php


namespace core\formatters;

use core\web\Response;
use core\exceptions\WarningException;
use core\helpers\Json;

class JsonResponseFormatter implements ResponseFormatterInterface
{

    public $useJsonp = false;

    public $encodeOptions = 320;

    public $prettyPrint = false;

    /**
     * @param Response $response
     * @throws WarningException
     */
    public function format($response)
    {
        if ($this->useJsonp) {
            $this->formatJsonp($response);
        } else {
            $this->formatJson($response);
        }
    }

    /**
     * @param Response $response
     */
    protected function formatJson($response)
    {
        $response->getHeaders()->set('Content-Type', 'application/json; charset=UTF-8');
        if ($response->data !== null) {
            $options = $this->encodeOptions;
            if ($this->prettyPrint) {
                $options |= JSON_PRETTY_PRINT;
            }
            $response->content = Json::encode($response->data, $options);
        }
    }

    /**
     * @param Response $response
     * @throws WarningException
     */
    protected function formatJsonp($response){
        $response->getHeaders()->set('Content-Type', 'application/javascript; charset=UTF-8');
        if (is_array($response->data) && isset($response->data['data'], $response->data['callback'])) {
            $response->content = sprintf('%s(%s);', $response->data['callback'], Json::htmlEncode($response->data['data']));
        } else if ($response->data !== null) {
            $response->content = '';
            throw
            new WarningException("The 'jsonp' response requires that the data be an array consisting of both 'data' and 'callback' elements.");
        }
    }
}