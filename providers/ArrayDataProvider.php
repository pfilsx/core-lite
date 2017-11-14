<?php


namespace core\providers;


class ArrayDataProvider extends DataProvider
{

    private $_data;

    public function getData()
    {
        return $this->_data;
    }

    public function sort($field, $direction)
    {
        usort($this->_data, $this->customSortMethod);
    }

    public function pagination($page, $perPage)
    {
        $total = $this->getCountRows();
        $pageCount = ceil($total/$perPage);
        if ($page > $pageCount){
            $page = 1;
        }
        $this->_data = array_slice($this->_data, $perPage*($page-1), $perPage);
        return $pageCount;
    }

    public function customSort(callable $method)
    {
        parent::customSort($method);
        $this->sort(null, null);
    }

    public function getAttributeLabel($field)
    {
        return $field;
    }

    public function setData(array $value){
        $this->_data = $value;
    }

    /**
     * @param string $field
     * @param array $row
     * @return mixed
     */
    public function getField($field, $row)
    {
        return $row[$field];
    }

    public function getCountRows()
    {
        return count($this->_data);
    }
}