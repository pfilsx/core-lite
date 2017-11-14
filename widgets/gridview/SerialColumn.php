<?php


namespace core\widgets\gridview;


class SerialColumn extends BaseColumn
{
    private $_counter = 1;

    public function init(){
        parent::init();
        $this->_config['class'] = 'col-sm-1 text-center '.(isset($this->_config['class']) ? ' '.$this->_config['class'] : '');
    }

    public function getLabel()
    {
        return '#';
    }

    /**
     * @param null $data
     * @return int
     */
    public function getContent($data = null)
    {
        if ($this->_gridView->paginationEnabled){
            return ($this->_gridView->pagination->currentPage-1)*$this->_gridView->pagination->pageSize + $this->_counter++;
        }
        return $this->_counter++;
    }
}