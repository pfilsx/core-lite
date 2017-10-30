<?php


namespace core\translate;


use core\base\BaseObject;

abstract class TranslateSource extends BaseObject implements TranslateSourceInterface
{
    /**
     * @return string
     */
    public abstract function getName();
    /**
     * @return array
     */
    public abstract function getMessages();

    /**
     * @param string $message
     * @param string $language
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function translate($message, $language, $params = [])
    {
        $messages = $this->getMessages();
        if (!is_array($messages)){
            throw new \Exception('Message source must be an array');
        }
        if (!isset($messages[$language]) || !isset($messages[$language][$message])){
            return strtr($message, $params);
        }
        return strtr($messages[$language][$message], $params);
    }
}