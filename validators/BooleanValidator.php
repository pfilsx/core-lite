<?php


namespace core\validators;


use core\base\App;
use core\base\BaseObject;

class BooleanValidator extends BaseObject implements ValidatorInterface
{
    public $trueValue = '1';

    public $falseValue = '0';

    public $strict = false;

    public $message;
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = App::$instance->translate('crl', '{attribute} must be either "{true}" or "{false}"', [
                    'true' => $this->trueValue === true ? 'true' : $this->trueValue,
                    'false' => $this->falseValue === false ? 'false' : $this->falseValue,
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    function validateValue($value)
    {
        if ($this->strict) {
            $valid = $value === $this->trueValue || $value === $this->falseValue;
        } else {
            $valid = $value == $this->trueValue || $value == $this->falseValue;
        }
        if (!$valid) {
            return $this->message;
        }
        return true;
    }
}