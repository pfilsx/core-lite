<?php

namespace core\validators;


interface ValidatorInterface {

    /**
     * @param mixed $value
     * @return boolean|null|array
     */
    function validateValue($value);
}