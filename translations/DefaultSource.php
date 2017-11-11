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
                '{attribute} cannot be blank' => '{attribute} cannot be blank.',
                '{attribute} must be an integer' => '{attribute} must be an integer.',
                '{attribute} must be a number' => '{attribute} must be a number.',
                '{attribute} must be no less than {min}' => '{attribute} must be no less than {min}.',
                '{attribute} must be no greater than {max}' => '{attribute} must be no greater than {max}.',
                '{attribute} must be either "{true}" or "{false}"' => '{attribute} must be either "{true}" or "{false}".',
                '{attribute} is not a valid email address' => '{attribute} is not a valid email address.',
                '{attribute} must be a string' => '{attribute} must be a string.',
                '{attribute} should contain at least {min} characters' => '{attribute} should contain at least {min} characters.',
                '{attribute} should contain at most {max} characters' => '{attribute} should contain at most {max} characters.',
                '{attribute} should contain {length} characters' => '{attribute} should contain {length} characters.'
            ],
            'ru' => [
                '{attribute} cannot be blank' => 'Поле {attribute} не может быть пустым.',
                '{attribute} must be an integer' => 'Поле {attribute} долно быть целым числом.',
                '{attribute} must be a number' => 'Поле {attribute} должно быть числом.',
                '{attribute} must be no less than {min}' => 'Поле {attribute} должно быть больше {min}.',
                '{attribute} must be no greater than {max}' => 'Поле {attribute} должно быть меньше {max}.',
                '{attribute} must be either "{true}" or "{false}"' => 'Поле {attribute} должно принимать значение "{true}" или "{false}".',
                '{attribute} is not a valid email address' => 'Поле {attribute} должно быть корректным email адресом.',
                '{attribute} must be a string' => 'Поле {attribute} должно быть строкой.',
                '{attribute} should contain at least {min} characters' => 'Поле {attribute} должно содержать как минимум {min} символов.',
                '{attribute} should contain at most {max} characters' => 'Поле {attribute} должно быть короче {max} символов.',
                '{attribute} should contain {length} characters' => 'Поле {attribute} должно содержать {length} символов.'
            ],
        ];
    }
}