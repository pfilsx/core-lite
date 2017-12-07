<?php


namespace core\validators;


use core\base\BaseObject;
use core\components\Model;

abstract class Validator extends BaseObject implements ValidatorInterface
{

    public $message;

    protected $_validatorAttributes = [];
    protected $_model;

    /**
     * Validator constructor.
     * @param array $config
     * @param Model|null $model
     * @param array $attributes
     */
    public function __construct(array $config = [], $model = null, array $attributes = [])
    {
        parent::__construct($config);
        $this->_validatorAttributes = $attributes;
        $this->_model = $model;
    }

    /**
     * @param $validatorName
     * @param array $params
     * @return Validator|null
     */
    public static function createValidator($validatorName, $params = []){
        if (array_key_exists(strtolower($validatorName), static::$_validatorsList)){
            $validatorClass = static::$_validatorsList[strtolower($validatorName)];
            return new $validatorClass($params);
        }
        return null;
    }

    /**
     * @param Model $model
     * @param String[] $attributes
     * @param string $validatorName
     * @param array $params
     * @return Validator|null
     */
    public static function createModelValidator($model, array $attributes, $validatorName, $params){
        if (array_key_exists(strtolower($validatorName), static::$_validatorsList)){
            $validatorClass = static::$_validatorsList[strtolower($validatorName)];
            return new $validatorClass($params, $model, $attributes);
        }
        return null;
    }

    /**
     * @param string $attribute - attribute name for validating by this validator
     * @return bool|string
     */
    public function validateAttribute($attribute)
    {
        if ($this->_model == null || !in_array($attribute, $this->_validatorAttributes)){
            return true;
        }
        $value = $this->_model->$attribute;
        $result = $this->validate($value);
        if ($result !== true){
            $result = strtr($result, ['{attribute}' => $this->_model->getAttributeLabel($attribute)]);
        }
        return $result;
    }
    public function validateValue($value)
    {
        $result = $this->validate($value);
        if ($result !== true){
            $result = strtr($result, ['{attribute}' => '']);
        }
        return $result;
    }

    protected function validate($value){
        return true;
    }

    /**
     * Returns array of attribute's names for this validator instance
     * @return array
     */
    public function getValidatorAttributes(){
        return $this->_validatorAttributes;
    }

    private static $_validatorsList = [
        'boolean' => '\core\validators\BooleanValidator',
        'bool' => '\core\validators\BooleanValidator',
        'number' => '\core\validators\NumberValidator',
        'email' => '\core\validators\EmailValidator',
        'required' => '\core\validators\RequiredValidator',
        'string' => '\core\validators\StringValidator',
        'mask' => '\core\validators\MaskValidator',
    ];
}