<?php


namespace core\db;


use core\base\BaseObject;

class ColumnSchema extends BaseObject
{
    public $name;
    public $allowNull;
    public $isPrimaryKey;
    public $autoIncrement;
    public $comment;
    public $phpType;
    public $dbType;
    public $type;
    public $enumValues;
    public $precision;
    public $size;
    public $scale;
    public $unsigned;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }
}