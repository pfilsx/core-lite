<?php


namespace core\validators;


use core\base\BaseObject;

class BooleanValidator extends BaseObject implements ValidatorInterface
{
    public $trueValue = '1';

    public $falseValue = '0';

    public $strict = false;

    /**
     * @param mixed $value
     * @return boolean
     */
    function validateValue($value)
    {
        if ($this->strict){
            return $value === $this->trueValue || $value === $this->falseValue;
        } else {
            return $value == $this->trueValue;
        }
    }
}