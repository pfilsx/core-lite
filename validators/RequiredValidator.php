<?php


namespace core\validators;


use core\web\App;

class RequiredValidator extends Validator implements ValidatorInterface
{

    public $skipOnEmpty = false;

    public $strict = false;

    /**
     * @inheritdoc
     */
    public function init(){
        if ($this->message === null) {
            $this->message = App::$instance->translate('crl', '{attribute} cannot be blank');
        }
    }

    /**
     * @inheritdoc
     */
    protected function validate($value)
    {
        if ($this->strict && $value !== null || !$this->strict && !empty(is_string($value) ? trim($value) : $value)) {
            return true;
        }
        return $this->message;
    }
}