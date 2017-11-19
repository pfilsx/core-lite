<?php


namespace core\validators;


use core\base\App;
use core\base\BaseObject;

class StringValidator extends BaseObject implements ValidatorInterface
{

    public $length;
    /**
     * @var int maximum length. If not set, it means no maximum length limit.
     */
    public $max;
    /**
     * @var int minimum length. If not set, it means no minimum length limit.
     */
    public $min;
    /**
     * @var string the encoding of the string value to be validated (e.g. 'UTF-8').
     */
    public $encoding;

    public $message;
    /**
     * @var string user-defined error message used when the length of the value is smaller than [[min]].
     */
    public $tooShort;
    /**
     * @var string user-defined error message used when the length of the value is greater than [[max]].
     */
    public $tooLong;
    /**
     * @var string user-defined error message used when the length of the value is not equal to [[length]].
     */
    public $notEqual;
    /**
     * @inheritdoc
     */
    public function init(){
        parent::init();
        if (is_array($this->length)) {
            if (isset($this->length[0])) {
                $this->min = $this->length[0];
            }
            if (isset($this->length[1])) {
                $this->max = $this->length[1];
            }
            $this->length = null;
        }
        if ($this->encoding === null) {
            $this->encoding = App::$instance ? App::$instance->charset : 'UTF-8';
        }
        if ($this->message === null) {
            $this->message = App::$instance->translate('crl', '{attribute} must be a string');
        }
        if ($this->min !== null && $this->tooShort === null) {
            $this->tooShort = App::$instance->translate('crl', '{attribute} should contain at least {min} characters',[
                'min' => $this->min
            ]);
        }
        if ($this->max !== null && $this->tooLong === null) {
            $this->tooLong = App::$instance->translate('crl', '{attribute} should contain at most {max} characters', [
                'max' => $this->max
            ]);
        }
        if ($this->length !== null && $this->notEqual === null) {
            $this->notEqual = App::$instance->translate('crl', '{attribute} should contain {length} characters', [
                'length' => $this->length
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    function validateValue($value)
    {
        if (!is_string($value)) {
            return $this->message;
        }
        $length = mb_strlen($value, $this->encoding);
        if ($this->min !== null && $length < $this->min) {
            return $this->tooShort;
        }
        if ($this->max !== null && $length > $this->max) {
            return $this->tooLong;
        }
        if ($this->length !== null && $length !== $this->length) {
            return $this->notEqual;
        }
        return true;
    }
}