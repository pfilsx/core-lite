<?php


namespace core\base;


use core\helpers\ArrayHelper;

abstract class Configurable
{

    protected $_config = [];

    public function __construct($config = [])
    {
        if (is_array($config) && !empty($config)){
            $this->_config = ArrayHelper::merge($this->_config, $config);
        }
    }
    /**
     * Get configuration params
     * @return array
     */
    public function getConfig(){
        return $this->_config;
    }
}