<?php


namespace core\providers;


use core\base\BaseObject;
use core\components\ActiveModel;

abstract class DataProvider extends BaseObject
{

    protected $enableCustomSort = false;
    protected $customSortMethod = null;

    public function init(){
        foreach ($this->_config as $key => $value){
            if ($this->hasProperty($key)){
                $this->$key = $value;
            }
        }
    }

    /**
     * @return array
     */
    public abstract function getData();

    /**
     * @param string $field
     * @param string $direction
     */
    public abstract function sort($field, $direction);

    /**
     * @param int $page
     * @param int $perPage
     * @return int Count of pages
     */
    public abstract function pagination($page, $perPage);

    /**
     * @param callable $method
     */
    public function customSort(callable $method)
    {
        $this->enableCustomSort = true;
        $this->customSortMethod = $method;
    }

    /**
     * @param string $field
     * @return string
     */
    public abstract function getAttributeLabel($field);

    /**
     * @param string $field
     * @param array|ActiveModel $row
     * @return mixed
     */
    public abstract function getField($field, $row);

    public abstract function getCountRows();
}