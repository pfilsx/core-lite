<?php


namespace core\validators;


use core\base\App;

class MaskValidator extends Validator implements ValidatorInterface
{
    public $pattern;


    public $not;

    /**
     * @inheritdoc
     */
    public function init(){
        if ($this->pattern == null){
            throw new \Exception('The "pattern" property must be set');
        }
        if ($this->message == null){
            $this->message = App::$instance->translate('crl', '{attribute} is invalid');
        }
    }

    /**
     * @inheritdoc
     */
    function validateValue($value)
    {
        $valid = !is_array($value) &&
            (!$this->not && preg_match($this->pattern, $value)
                || $this->not && !preg_match($this->pattern, $value));
        return $valid ? true : $this->message;
    }
}