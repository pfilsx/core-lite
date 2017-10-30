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
    public $dbType;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }
}