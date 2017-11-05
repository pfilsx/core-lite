<?php


namespace core\widgets\gridview;


use core\web\Html;

class SerialColumn extends BaseColumn
{
    private $_counter = 1;

    public function init(){
        parent::init();
        $this->_config['class'] = 'col-sm-1 text-center '.(isset($this->_config['class']) ? ' '.$this->_config['class'] : '');
    }

    public function getLabel()
    {
        return '#';
    }

    public function getContent($data = null)
    {
        return $this->_counter++;
    }
}