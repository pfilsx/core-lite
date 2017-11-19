<?php


namespace core\widgets\gridview;


use core\base\App;
use core\base\BaseObject;
use core\base\Widget;
use core\components\ActiveModel;
use core\db\QueryBuilder;
use core\helpers\Url;
use core\providers\ActiveDataProvider;
use core\providers\ArrayDataProvider;
use core\providers\DataProvider;
use core\web\Html;
use core\widgets\pagination\Pagination;

/**
 * Class GridView
 * @package core\widgets\gridview
 * @property string orderDirection
 * @property DataProvider dataProvider
 * @property string orderBy
 * @property array columns
 * @property bool paginationEnabled
 * @property Pagination|null pagination
 */
class GridView extends Widget
{
    private $_columns = [];
    /**
     * @var DataProvider
     */
    private $_dataProvider;

    private $_pagination;

    private $_orderBy = null;
    private $_orderDirection = 'ASC';

    private $_paginationEnable = false;

    private $_data;

    public function __construct(array $config = [])
    {
        $this->_dataProvider = $config['provider'];

        if (isset($config['pagination'])){
            $this->_pagination = Pagination::begin([
                'pageSize' => isset($config['pagination']['pageSize']) ? (int)$config['pagination']['pageSize'] : 10,
            ]);
            $this->_paginationEnable = true;
        }
        if (isset(App::$instance->request->get['sort'])){
            $get = App::$instance->request->get;
            $this->_orderBy = $get['sort'];
            if (isset($get['sort_direction'])){
                $this->_orderDirection = $get['sort_direction'] == 'DESC' ? 'DESC' : 'ASC';
            }
        }
        parent::__construct($config);

        if ($this->_dataProvider instanceof QueryBuilder){
            $query = clone $this->_dataProvider;
            $this->_dataProvider = new ActiveDataProvider(['query' => $query]);
        } elseif (is_array($this->_dataProvider)) {
            $data = clone $this->_dataProvider;
            $this->_dataProvider = new ArrayDataProvider(['data' => $data]);
        } else {
            throw new \Exception('"provider" must be a DataProvider|QueryBuilder|array instance');
        }
        foreach ($config['columns'] as $column){
            $className = 'core\widgets\gridview\\'.$column['type'];
            $columnClass = new $className($this, $column);
            $this->_columns[] = $columnClass;
            if ($columnClass->needSort){
                $this->_dataProvider->customSort($columnClass->sortMethod);
            }
        }
        if ($this->_paginationEnable){
            $this->_pagination->pageCount = $this->_dataProvider
                ->pagination($this->_pagination->currentPage, $this->_pagination->pageSize);
        }
        $this->_data = $this->_dataProvider->getData();
    }

    public function run(){
        ob_start();
        ob_implicit_flush(false);
        $this->printHeader();
        $this->printContent();
        $this->printFooter();
        $this->printPagination();
        return ob_get_clean();
    }

    private function printHeader(){
        echo Html::startTag('table', ['class' => 'table table-bordered table-responsive']);
        echo Html::startTag('thead');
        echo Html::startTag('tr');
        foreach ($this->_columns as $column){
            echo $column->getHeader();
        }
        echo Html::endTag('tr');
        echo Html::endTag('thead');
    }

    private function printContent(){
        echo Html::startTag('tbody');
        foreach ($this->_data as $row){
            $this->printRow($row);
        }
        echo Html::endTag('tbody');
    }

    private function printFooter(){
        echo Html::endTag('table');
    }

    /**
     * @param ActiveModel $row
     */
    private function printRow($row){
        echo Html::startTag('tr');
        foreach ($this->_columns as $column){
            echo $column->getBody($row);
        }
        echo Html::endTag('tr');
    }

    private function printPagination(){
        if (!$this->_paginationEnable){
            return;
        }
        Pagination::end();
    }

    public function getData(){
        return $this->_data;
    }
    public function getDataProvider(){
        return $this->_dataProvider;
    }
    public function getOrderDirection(){
        return $this->_orderDirection;
    }
    public function getOrderBy(){
        return $this->_orderBy;
    }
    public function getColumns(){
        return $this->_columns;
    }
    public function getPaginationEnabled(){
        return $this->_paginationEnable;
    }
    public function getPagination(){
        return $this->_pagination;
    }
}