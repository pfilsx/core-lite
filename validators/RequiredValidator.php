<?php


namespace core\validators;


use core\base\App;
use core\base\BaseObject;

class RequiredValidator extends BaseObject implements ValidatorInterface
{

    public $skipOnEmpty = false;

    public $strict = false;

    public $message;

    public function init(){
        parent::init();
        if ($this->message === null) {
            $this->message = App::$instance->translate('crl', '{attribute} cannot be blank');
        }
    }
    /**
     * @param mixed $value
     * @return true|string
     */
    function validateValue($value)
    {
        if ($this->strict && $value !== null || !$this->strict && !empty(is_string($value) ? trim($value) : $value)) {
            return true;
        }
        return $this->message;
    }
}