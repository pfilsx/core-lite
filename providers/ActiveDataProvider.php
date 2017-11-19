<?php


namespace core\providers;


use core\components\ActiveModel;
use core\db\QueryBuilder;

/**
 * @property QueryBuilder query
 */
class ActiveDataProvider extends DataProvider
{
    /**
     * @var QueryBuilder
     */
    private $_query;

    private $_data = null;


    public function init(){
        parent::init();
        if (!$this->query instanceof QueryBuilder){
            throw new \Exception('Unsupported parameter "query". Must be a QueryBuilder instance');
        }
    }

    public function getData()
    {
        if ($this->_data !== null){
            return $this->_data;
        } else {
            $this->_data = $this->query->queryAll();
            if ($this->enableCustomSort){
                usort($this->_data, $this->customSortMethod);
            }
        }
        return $this->_data;
    }

    public function sort($field, $direction)
    {
        if (($modelName = $this->query->model) != null){
             $model = $modelName::instance();
             if ($model instanceof ActiveModel){
                 if (array_key_exists($field, $model->tableSchema->columns)){
                     $this->query->orderBy([$field => $direction]);
                 }
                 return;
             }
        }
        $this->query->orderBy([$field => $direction]);
    }

    public function pagination($page, $perPage)
    {
        $totalRows = $this->getCountRows();
        $pagesCount = ceil($totalRows/$perPage);
        if ($page > $pagesCount){
            $page = 1;
        }
        if ($this->enableCustomSort){
            $this->_data = $this->query->queryAll();
            usort($this->_data, $this->customSortMethod);
            $this->_data = array_slice($this->_data, $page-1, $perPage);
        } else {
            $this->query->limit($perPage)->offset($page-1);
        }
        return $pagesCount;
    }

    public function getQuery(){
        return $this->_query;
    }
    public function setQuery($value){
        $this->_query = $value;
    }

    public function getAttributeLabel($field){
        $label = $field;
        if ($this->_query->model !== null){
            $className = $this->_query->model;
            /**
             * @var ActiveModel $model
             */
            $model = $className::instance();
            $label = $model->getAttributeLabel($field);
        }
        return $label;
    }

    /**
     * @param string $field
     * @param ActiveModel $row
     * @return mixed
     */
    public function getField($field, $row)
    {
        return $row->$field;
    }

    public function getCountRows()
    {
        $query = clone $this->query;
        return $query->count();
    }
}