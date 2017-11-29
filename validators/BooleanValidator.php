<?php


namespace core\validators;


use core\base\App;

class BooleanValidator extends Validator implements ValidatorInterface
{
    public $trueValue = '1';

    public $falseValue = '0';

    public $strict = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
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
    protected function validate($value)
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