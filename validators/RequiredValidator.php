<?php


namespace core\validators;


use core\base\BaseObject;

class RequiredValidator extends BaseObject implements ValidatorInterface
{

    /**
     * @param mixed $value
     * @return boolean
     */
    function validateValue($value)
    {
        $valid = true;
        if ($value === null || (is_string($value) && trim($value) == '') || (is_array($value) && empty($value))){
            $valid = false;
        }

        return $valid;
    }
}