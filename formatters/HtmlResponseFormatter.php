<?php


namespace core\formatters;


use core\base\Response;

class HtmlResponseFormatter implements ResponseFormatterInterface
{
    public $contentType = 'text/html';

    /**
     * @param Response $response
     */
    public function format($response)
    {
        if (stripos($this->contentType, 'charset') === false) {
            $this->contentType .= '; charset=' . $response->charset;
        }
        $response->getHeaders()->set('Content-Type', $this->contentType);
        if ($response->data !== null) {
            $response->content = $response->data;
        }
    }
}