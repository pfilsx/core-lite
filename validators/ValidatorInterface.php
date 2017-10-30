<?php

namespace core\validators;


interface ValidatorInterface {

    /**
     * @param mixed $value
     * @return boolean
     */
    function validateValue($value);
}