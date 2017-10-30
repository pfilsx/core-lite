<?php


namespace core\validators;


use core\base\BaseObject;
use core\helpers\StringHelper;

class NumberValidator extends BaseObject implements ValidatorInterface
{
    public $integerOnly = false;

    public $max;

    public $min;

    public $integerPattern = '/^\s*[+-]?\d+\s*$/';

    public $numberPattern = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';


    /**
     * @param mixed $value
     * @return boolean
     */
    function validateValue($value)
    {
        if (is_array($value) || is_object($value)) {
            return false;
        }
        $pattern = $this->integerOnly ? $this->integerPattern : $this->numberPattern;
        if (!preg_match($pattern, StringHelper::normalizeNumber($value))) {
            return false;
        } elseif ($this->min !== null && $value < $this->min) {
            return false;
        } elseif ($this->max !== null && $value > $this->max) {
            return false;
        }
        return true;
    }
}