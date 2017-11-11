<?php


namespace core\db;


use core\base\BaseObject;

/**
 * @property bool primaryKey
 * @property bool unique
 */
abstract class MigrationColumnBuilder extends BaseObject
{
    protected $_query;

    protected $_notNull = false;

    protected $_autoIncrement = false;

    protected $_primaryKey = false;

    protected $_unique = false;

    protected $_default;

    protected $_comment;

    /**
     * @param int $length
     * @return MigrationColumnBuilder
     */
    public abstract function string($length = 255);
    /**
     * @param int $length
     * @return MigrationColumnBuilder
     */
    public abstract function integer($length = 6);

    /**
     * * @param int $length
     * @return MigrationColumnBuilder
     */
    public abstract function timestamp($length = 6);
    /**
     * @return MigrationColumnBuilder
     */
    public function notNull()
    {
        $this->_notNull = true;
        return $this;
    }
    /**
     * @return MigrationColumnBuilder
     */
    public function primaryKey()
    {
        $this->notNull();
        $this->_primaryKey = true;
        return $this;
    }
    /**
     * @return MigrationColumnBuilder
     */
    public function unique(){
        $this->_unique = true;
        return $this;
    }
    /**
     * @return MigrationColumnBuilder
     */
    public function autoIncrement()
    {
        $this->_autoIncrement = true;
        $this->comment('auto incremented');
        return $this;
    }

    /**
     * @param $value
     * @return MigrationColumnBuilder
     */
    public abstract function defaultValue($value);

    /**
     * @param $expression
     * @return MigrationColumnBuilder
     */
    public abstract function defaultExpression($expression);

    /**
     * @param string $text
     * @return MigrationColumnBuilder
     */
    public abstract function comment($text);

    /**
     * @return string
     */
    public function getQuery(){
        return $this->_query;
    }

    public function getPrimaryKey(){
        return $this->_primaryKey;
    }
    public function getUnique(){
        return $this->_unique;
    }
}