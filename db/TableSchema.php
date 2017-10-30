<?php


namespace core\db;


use core\base\BaseObject;

class TableSchema extends BaseObject
{
    private $_name;

    public $columns;

    public $primaryKey = [];

    public $sequenceName;

    public function __construct($name)
    {
        $this->_name = $name;
        parent::__construct([]);
    }

    public function getName(){
        return $this->_name;
    }
}