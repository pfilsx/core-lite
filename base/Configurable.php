<?php


namespace core\base;


use core\helpers\ArrayHelper;

abstract class Configurable
{

    protected $_config = [];

    public function __construct($config = [])
    {
        if (!empty($config)){
            $this->_config = ArrayHelper::merge($this->_config, $config);
        }
    }

    public function getConfig(){
        return $this->_config;
    }
}