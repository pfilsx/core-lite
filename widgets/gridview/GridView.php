<?php


namespace core\widgets\gridview;


use core\base\App;
use core\base\BaseObject;
use core\base\Widget;
use core\components\ActiveModel;
use core\db\QueryBuilder;
use core\helpers\Url;
use core\web\Html;
use core\widgets\pagination\Pagination;

/**
 * Class GridView
 * @package core\widgets\gridview
 * @property string orderDirection
 * @property array|QueryBuilder dataProvider
 * @property string orderBy
 */
class GridView extends Widget
{
    private $_columns = [];
    /**
     * @var array|QueryBuilder
     */
    private $_dataProvider;

    private $_pagination;
    private $_limit;
    private $_offset;
    private $_pageCount;
    private $_currentPage;

    private $_orderBy = null;
    private $_orderDirection = 'ASC';

    private $_paginationEnable = false;

    private $_data;

    public function __construct(array $config = [])
    {
        $this->_dataProvider = $config['provider'];

        if (isset($config['pagination'])){
            $this->_pagination = $config['pagination'];
            $this->_limit = (int)$this->_pagination['pageSize'];
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

        foreach ($config['columns'] as $column){
            $className = 'core\widgets\gridview\\'.$column['type'];
            $columnClass = new $className($this, $column);
            $this->_columns[] = $columnClass;
        }

        if ($this->_dataProvider instanceof QueryBuilder){
            $this->_data = $this->_dataProvider->queryAll();
        } else {
            $this->_data = $this->_dataProvider;
        }
        foreach ($this->_columns as $column){
            if ($column->needSort){
                usort($this->_data, $column->sortMethod);
                break;
            }
        }
        if ($this->_paginationEnable){
            $total = count($this->_data);
            $this->_pageCount = ceil($total/$this->_limit);
            $this->_currentPage = isset(App::$instance->request->get['page']) ? (int)App::$instance->request->get['page'] : 1;
            if ($this->_currentPage > $this->_pageCount){
                $this->_currentPage = 1;
            }
            $this->_offset = $this->_currentPage - 1;
            $this->_data = array_slice($this->_data, $this->_offset, $this->_limit);
        }
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
        echo Pagination::widget([
            'currentPage' => $this->_currentPage,
            'pageCount' => $this->_pageCount
        ]);
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
}