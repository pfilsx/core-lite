<?php


namespace core\validators;


use core\base\BaseObject;

abstract class Validator extends BaseObject
{

    public static function createValidator($validatorName, $params = []){
        if (array_key_exists(strtolower($validatorName), static::$_validatorsList)){
            $validatorClass = static::$_validatorsList[strtolower($validatorName)];
            return new $validatorClass($params);
        }
        return null;
    }

    private static $_validatorsList = [
        'boolean' => '\core\validators\BooleanValidator',
        'bool' => '\core\validators\BooleanValidator',
        'number' => '\core\validators\NumberValidator',
        'email' => '\core\validators\EmailValidator',
        'required' => '\core\validators\RequiredValidator',
        'string' => '\core\validators\StringValidator',
    ];
}