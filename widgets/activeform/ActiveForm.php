<?php


namespace core\widgets\activeform;

use core\base\Widget;
use core\web\Html;

class ActiveForm extends Widget
{
    public $method = 'post';

    public $action = null;

    public $options = [];

    private $_fields;

    public function run(){
        $content = ob_get_clean();
        echo Html::beginForm($this->action, $this->method, $this->options);
        echo $content;
        echo Html::endForm();
    }

    public function init(){
        ActiveFormAssets::registerAssets();
        foreach ($this->_config as $key => $value){
            if (property_exists($this, $key)){
                $this->$key = $value;
            }
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