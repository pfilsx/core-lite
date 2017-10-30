<?php


namespace core\translations;


use core\translate\TranslateSource;

class DefaultSource extends TranslateSource
{

    /**
     * @return string
     */
    public function getName()
    {
        return 'crl';
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return [
            'en' => [

            ],
            'ru' => [

            ],
        ];
    }
}