<?php


namespace core\widgets\activeform;


use core\base\BaseObject;
use core\components\Model;
use core\helpers\ArrayHelper;
use core\web\Html;

class ActiveField extends BaseObject
{

    /**
     * @var \core\components\Model
     */
    protected $_model;
    /**
     * @var string
     */
    protected $_attribute;
    /**
     * @var \core\widgets\activeform\ActiveForm
     */
    protected $_form;

    /**
     * @var string
     */
    protected $_label;
    protected $_labelOptions = [];

    /**
     * @var array
     */
    protected $_elements = [];

    /**
     * @var bool
     */
    protected $_enclosedByLabel = false;

    protected $_fieldName;


    /**
     * ActiveField constructor.
     * @param Model $model
     * @param string $attribute
     * @param ActiveForm $form
     */
    public function __construct($model, $attribute, $form)
    {
        $this->_model = $model;
        $this->_attribute = $attribute;
        $this->_form = $form;
        parent::__construct([]);
    }

    public function input($type = 'text', $options = [])
    {
        switch ($type) {
            case 'file':
                return $this->fileInput($options);
            case 'hidden':
                return $this->hiddenInput($options);
            case 'checkbox':
                return $this->checkbox($options, true);
            default:
                return $this->defaultInput($type, $options);
        }
    }

    protected function defaultInput($type, $options = []){
        $this->_elements[] = Html::input($this->getFieldName(), $type, $this->_model->{$this->_attribute},
            array_merge($options, [
                'data-attribute' => $this->_attribute
            ]));
        return $this;
    }


    public function checkbox($options = [], $enclosedByLabel = true)
    {
        $html = Html::checkbox($this->getFieldName(), $this->_model->{$this->_attribute},
            array_merge($options, [
                'data-attribute' => $this->_attribute
            ]));
        $this->_elements[] = $html;

        $this->_enclosedByLabel = $enclosedByLabel;
        return $this;
    }

    public function radio($options = [], $enclosedByLabel = true){
        $html = Html::radio($this->getFieldName(), $this->_model->{$this->_attribute},
            array_merge($options,[
                'data-attribute' => $this->_attribute
            ]));
        $this->_elements[] = $html;
        $this->_enclosedByLabel = $enclosedByLabel;
        return $this;
    }

    public function select($items, $options = []){
        $this->_elements[] = Html::select($this->getFieldName(), $items, $this->_model->{$this->_attribute},
            array_merge($options, [
                'data-attribute' => $this->_attribute
            ]));
        return $this;
    }

    public function textarea($options = [])
    {
        $this->_elements[] = Html::textarea($this->getFieldName(), $this->_model->{$this->_attribute},
            array_merge($options, [
                'data-attribute' => $this->_attribute
            ]));
        return $this;
    }

    public function fileInput($options = [])
    {
        if (!isset($this->_form->options['enctype'])) {
            $this->_form->options['enctype'] = 'multipart/form-data';
        }
        $this->_elements[] = Html::fileInput($this->getFieldName(), null, array_merge($options, [
            'data-attribute' => $this->_attribute
        ]));
        return $this;
    }

    public function hiddenInput($options = [])
    {
        $this->_elements[] = Html::hiddenInput($this->getFieldName(), $this->_model->{$this->_attribute},
            array_merge($options, [
                'data-attribute' => $this->_attribute
            ])
        );
        return $this;
    }

    public function label($text, $options = [])
    {
        $this->_label = $text;
        $this->_labelOptions = $options;
        return $this;
    }

    protected function render()
    {
        $html = Html::startTag('div', ['class' => 'crl-active-form-group '
            .($this->_model->hasError($this->_attribute) ? 'has-error' : '')]);
        if ($this->_label == null) {
            $this->label($this->_model->getAttributeLabel($this->_attribute));
        }
        if ($this->_enclosedByLabel){
            $html .= Html::startTag('label', ArrayHelper::merge($this->_labelOptions, [
                'for' => $this->getFieldName()
            ]));
            $html .= implode(PHP_EOL, $this->_elements);
            $html .= $this->_label.Html::endTag('label');
        } else {
            $html .= Html::label($this->_label, $this->getFieldName(), $this->_labelOptions);
            $html .= implode(PHP_EOL, $this->_elements);
        }
        $html .= Html::tag('span', ($this->_model->hasError($this->_attribute)
            ? $this->_model->getErrors($this->_attribute)[0] : null),
            ['class' => $this->_attribute.'_help help-block']);
        $html .= Html::endTag('div');

        return $html;
    }

    protected function getFieldName(){
        if ($this->_fieldName == null){
            $this->_fieldName = $this->_model->getAttributeName($this->_attribute);
        }
        return $this->_fieldName;
    }

    public function __toString()
    {
        return $this->render();
    }


}