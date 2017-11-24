<?php

namespace core\validators;


interface ValidatorInterface {

    /**
     * @param mixed $value
     * @return boolean|string
     */
    function validateValue($value);

    /**
     * @param string $attribute
     * @return boolean|string
     */
    function validateAttribute($attribute);
}