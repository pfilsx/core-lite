<?php

namespace core\validators;


interface ValidatorInterface {

    /**
     * @param mixed $value
     * @return boolean|string
     */
    function validateValue($value);
}