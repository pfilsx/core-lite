<?php


namespace core\db\mysql;

class MigrationColumnBuilder extends \core\db\MigrationColumnBuilder
{

    public function string($length = 255)
    {
        if ($length > 255){
            $this->_query = 'TEXT';
        } else {
            $this->_query = 'VARCHAR('.intval($length).')';
        }
        return $this;
    }

    public function integer($length = 6)
    {
        $this->_query = 'INT('.intval($length).')';
        return $this;
    }

    public function timestamp($length = 6)
    {
        $this->_query = "TIMESTAMP($length)";
        return $this;
    }

    public function comment($text)
    {
        $this->_comment = "COMMENT '$text'";
        return $this;
    }
    public function defaultValue($value)
    {
        $this->_default = "DEFAULT '$value'";
        return $this;
    }

    public function defaultExpression($expression)
    {
        $this->_default = "DEFAULT $expression";
        return $this;
    }

    public function getQuery()
    {
        $query = $this->_query;
        if ($this->_notNull){
            $query .= ' NOT NULL ';
        }
        if ($this->_autoIncrement){
            $query .= ' AUTO_INCREMENT ';
        }
        if (!empty($this->_default)){
            $query .= ' '.$this->_default;
        }
        if (!empty($this->_comment)){
            $query .= ' '.$this->_comment;
        }
        return $query;
    }
}