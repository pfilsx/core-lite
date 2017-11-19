<?php


namespace core\widgets\activeform;


use core\base\BaseObject;
use core\helpers\ArrayHelper;
use core\web\Html;

class ActiveField extends BaseObject
{

    /**
     * @var \core\components\Model
     */
    private $_model;
    /**
     * @var string
     */
    private $_attribute;
    /**
     * @var \core\widgets\activeform\ActiveForm
     */
    private $_form;

    /**
     * @var string
     */
    private $_label;
    private $_labelOptions = [];

    /**
     * @var array
     */
    private $_elements = [];

    /**
     * @var bool
     */
    private $_enclosedByLabel = false;

    private $_fieldName;

    private $_mergablesAttrs = [
        'class'
    ];


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
                $this->getFieldName();
                $this->_elements[] = Html::input($this->getFieldName(), $type, $this->_model->{$this->_attribute},
                    $this->mergeOptions($options, [
                        'data-field' => $this->_attribute
                    ]));
                $this->_elements[] = Html::startTag('span', ['class' => $this->_attribute.'_help help-block']).Html::endTag('span');
                return $this;
        }
    }

    public function checkbox($options = [], $enclosedByLabel = true)
    {
//        $html = Html::startTag('div', ['class' => 'crl-checkbox-group']);
//        if ($enclosedByLabel) {
//            $html .= Html::startTag('label', [
//                'for' => $this->getFieldName()
//                ]).Html::checkbox($this->getFieldName(), $this->_model->{$this->_attribute},
//                    $this->mergeOptions($options,[
//                        'data-field' => $this->_attribute
//                    ]))
//                .$this->_model->getAttributeLabel($this->_attribute)
//                .Html::endTag('label');
//        } else {
//            $html .= Html::checkbox($this->getFieldName(), $this->_model->{$this->_attribute},
//                $this->mergeOptions($options, [
//                    'data-field' => $this->_attribute
//                ]));
//        }
        $html = Html::checkbox($this->getFieldName(), $this->_model->{$this->_attribute},
            $this->mergeOptions($options, [
                'data-field' => $this->_attribute
            ]));
//        $html .= Html::endTag('div');
        $this->_elements[] = $html;

        $this->_enclosedByLabel = $enclosedByLabel;
        return $this;
    }

    public function radio($options = [], $enclosedByLabel = true){
        $html = Html::startTag('div', ['class' => 'crl-radio-group']);
//        if ($enclosedByLabel) {
//            $html .= Html::startTag('label', [
//                    'for' => $this->getFieldName()
//                ]).Html::radio($this->getFieldName(), $this->_model->{$this->_attribute},
//                    $this->mergeOptions($options, [
//                        'data-field' => $this->_attribute
//                    ]))
//                .$this->_model->getAttributeLabel($this->_attribute)
//                .Html::endTag('label');
//        } else {
//            $html .= Html::radio($this->getFieldName(), $this->_model->{$this->_attribute},
//                $this->mergeOptions($options,[
//                    'data-field' => $this->_attribute
//                ]));
//        }
        $html .= Html::radio($this->getFieldName(), $this->_model->{$this->_attribute},
            $this->mergeOptions($options,[
                'data-field' => $this->_attribute
            ]));
        $html .= Html::endTag('div');
        $this->_elements[] = $html;
        $this->_enclosedByLabel = $enclosedByLabel;
        return $this;
    }

//    TODO setting values
//    public function checkboxGroup($items, $options = []){
//        if (!is_array($items)){
//            throw new \Exception('Parameter $items must be array');
//        }
//        $name = $this->_attribute.'[]';
//        foreach ($items as $key => $value){
//            $html = Html::startTag('div', ['class' => 'crl-checkbox-group']);
//            $html .= Html::startTag('label', [
//                    'for' => $name
//                ]).Html::checkbox($name, $this->_model->{$this->_attribute}, array_merge([
//                ], $options)).$value.Html::endTag('label');
//            $html .= Html::endTag('div');
//            $this->_elements[] = $html;
//        }
//
//        return $this;
//    }

    public function select($items, $options = []){
        $this->_elements[] = Html::select($this->getFieldName(), $items, $this->_model->{$this->_attribute},
            $this->mergeOptions($options, [
                'data-field' => $this->_attribute
            ]));
        return $this;
    }

    public function textarea($options = [])
    {
        $this->_elements[] = Html::textarea($this->getFieldName(), $this->_model->{$this->_attribute},
            $this->mergeOptions($options, [
                'data-field' => $this->_attribute
            ]));
        return $this;
    }

    public function fileInput($options = [])
    {
        if (!isset($this->_form->options['enctype'])) {
            $this->_form->options['enctype'] = 'multipart/form-data';
        }
        $this->_elements[] = Html::fileInput($this->getFieldName(), null, $this->mergeOptions($options, [
            'data-field' => $this->_attribute
        ]));
        return $this;
    }

    public function hiddenInput($options = [])
    {
        $this->_elements[] = Html::hiddenInput($this->getFieldName(), $this->_model->{$this->_attribute}, array_merge($options, [
            'data-field' => $this->_attribute
        ]));
        return $this;
    }

    public function label($text, $options = [])
    {
        $this->_label = $text;
        $this->_labelOptions = $options;
        return $this;
    }

    private function render()
    {
        $html = Html::startTag('div', ['class' => 'crl-active-form-group']);
        if ($this->_label == null) {
            $this->label($this->_model->getAttributeLabel($this->_attribute));
        }
        if ($this->_enclosedByLabel){
            $html .= Html::startTag('label', ArrayHelper::merge_recursive($this->_labelOptions, [
                'class' => 'control-label',
                'for' => $this->getFieldName()
            ]));
            $html .= implode(PHP_EOL, $this->_elements);
            $html .= $this->_label.Html::endTag('label');
        } else {
            $html .= Html::label($this->_label, $this->getFieldName(), ArrayHelper::merge_recursive($this->_labelOptions,[
                'class' => 'control-label'
            ]));
            $html .= implode(PHP_EOL, $this->_elements);
        }
        $html .= Html::endTag('div');

        return $html;
    }

    private function mergeOptions($defaults, $options){
        foreach ($options as $key => $value){
            if (array_key_exists($key, $defaults)){
                if (in_array(trim($key), $this->_mergablesAttrs)){
                    if (gettype($defaults[$key]) == gettype($options[$key])){
                        $defaults[$key] = $defaults[$key].' '.$options[$key];
                        continue;
                    }
                }
            }
            $defaults[$key] = $options[$key];
        }
        return $defaults;
    }

    private function getFieldName(){
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