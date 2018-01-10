<?php


namespace core\validators;


use core\web\App;
use core\helpers\StringHelper;

class NumberValidator extends Validator implements ValidatorInterface
{
    public $integerOnly = false;

    public $max;

    public $min;

    /**
     * @var string user-defined error message used when the value is bigger than [[max]].
     */
    public $tooBig;
    /**
     * @var string user-defined error message used when the value is smaller than [[min]].
     */
    public $tooSmall;

    public $integerPattern = '/^\s*[+-]?\d+\s*$/';

    public $numberPattern = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';

    /**
     * @inheritdoc
     */
    public function init(){
        if ($this->message === null) {
            $this->message = $this->integerOnly
                ? App::$instance->translate('crl', '{attribute} must be an integer')
                : App::$instance->translate('crl', '{attribute} must be a number');
        }
        if ($this->min !== null && $this->tooSmall === null) {
            $this->tooSmall = App::$instance->translate('crl', '{attribute} must be no less than {min}', [
                'min' => $this->min
            ]);
        }
        if ($this->max !== null && $this->tooBig === null) {
            $this->tooBig = App::$instance->translate('crl', '{attribute} must be no greater than {max}', [
                'max' => $this->max
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function validate($value)
    {
        if (is_array($value) || is_object($value)) {
            return $this->message;
        }
        $pattern = $this->integerOnly ? $this->integerPattern : $this->numberPattern;
        if (!preg_match($pattern, StringHelper::normalizeNumber($value))) {
            return $this->message;
        } elseif ($this->min !== null && $value < $this->min) {
            return $this->tooSmall;
        } elseif ($this->max !== null && $value > $this->max) {
            return $this->tooBig;
        }
        return true;
    }
}