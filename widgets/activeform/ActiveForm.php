<?php


namespace core\widgets\activeform;

use core\components\Widget;
use core\helpers\ArrayHelper;
use core\web\Html;

class ActiveForm extends Widget
{
    public $method = 'post';

    public $action = null;

    public $ajaxValidation = false;

    public $options = [];

    private $_fields;

    public function run(){
        $content = ob_get_clean();
        $html = Html::beginForm($this->action, $this->method, ArrayHelper::merge_recursive($this->options, [
            'class' => 'crl-active-form '.($this->ajaxValidation ? ' with-validation ' : '')
        ]));
        $html .= $content;
        $html .= Html::endForm();
        return $html;
    }

    public function init(){
        parent::init();
        if ($this->assetsEnabled){
            ActiveFormAssets::register();
        }
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * @param \core\components\Model $model
     * @param string $attribute
     * @return \core\widgets\activeform\ActiveField || null
     * @throws \Exception
     */
    public function field($model, $attribute){

        if ($model->hasProperty($attribute)){
            $field = new ActiveField($model, $attribute, $this);
            $this->_fields[] = $field;
            return $field;
        }
        throw new \Exception("Trying to call field() on nonexistent attribute $attribute");
    }

}