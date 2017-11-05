<?php


namespace core\web;


use core\base\App;
use core\base\Widget;

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
        App::$instance->assetManager->addDepend('@crl/assets/crl.activeForm.js');
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
     * @return \core\web\ActiveField || null
     * @throws \Exception
     */
    public function field($model, $attribute){

        if ($model->hasProperty($attribute)){
            return new ActiveField($model, $attribute, $this);
        }
        throw new \Exception("Trying to call field() on nonexistent attribute $attribute");
    }

}