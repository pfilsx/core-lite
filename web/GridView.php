<?php


namespace core\web;


use core\base\App;
use core\base\BaseObject;
use core\components\ActiveModel;
use core\db\QueryBuilder;
use core\helpers\Url;

class GridView extends BaseObject
{
    private $_columns = [];
    private $_dataProvider;

    private $_pagination;
    private $_limit;
    private $_offset;
    private $_pageCount;

    private $_paginationEnable = false;

    private $_data;

    public static function widget(array $config = []){
        $widget = new GridView($config);
        $widget->run();
    }

    public function __construct(array $config = [])
    {
        $this->_columns = $config['columns'];
        $this->_dataProvider = $config['provider'];
        if (isset($config['pagination'])){
            $this->_pagination = $config['pagination'];
            $this->_limit = (int)$this->_pagination['pageSize'];

            $this->_offset = isset(App::$instance->request->get['page'])
                ? ((int)App::$instance->request->get['page']-1)*$this->_limit
                : 0;
            $this->_paginationEnable = true;
        }
        if ($this->_dataProvider instanceof QueryBuilder){
            if ($this->_paginationEnable){
                $provider = clone ($this->_dataProvider);
                $total = $provider->count();
                $this->_pageCount = ceil($total/$this->_limit);
                $this->_dataProvider->limit($this->_limit)->offset($this->_offset);
            }
            $this->_data = $this->_dataProvider->queryAll();
        } else {
            if ($this->_paginationEnable){
                $total = count($this->_dataProvider);
                $this->_pageCount = ceil($total/$this->_limit);
                $this->_data = array_slice($this->_dataProvider, $this->_offset, $this->_limit);
            } else {
                $this->_data = $this->_dataProvider;
            }
        }
        parent::__construct($config);
    }

    private function run(){
        $this->printHeader();
        $this->printContent();
        $this->printFooter();
        $this->printPagination();
    }

    private function printHeader(){
        echo Html::startTag('table', ['class' => 'table table-bordered table-responsive']);
        echo Html::startTag('thead');
        echo Html::startTag('tr');
        foreach ($this->_columns as $column){
            echo Html::startTag('th');
            if ($column['type'] == 'dataColumn'){
                echo isset($column['label']) ? $column['label'] : '';
            } elseif ($column['type'] == 'serialColumn') {
                echo '#';
            }
            echo Html::endTag('th');
        }
        echo Html::endTag('tr');
        echo Html::endTag('thead');
    }

    private function printContent(){
        echo Html::startTag('tbody');
        foreach ($this->_data as $key => $row){
            $this->printRow($key, $row);
        }
        echo Html::endTag('tbody');
    }

    private function printFooter(){
        echo Html::endTag('table');
    }

    /**
     * @param $idx
     * @param ActiveModel $row
     */
    private function printRow($idx, $row){
        echo Html::startTag('tr');
        foreach ($this->_columns as $column){
            if ($column['type'] == 'dataColumn'){
                echo Html::startTag('td');
                if (isset($column['value'])){
                    if (is_callable($column['value'])){
                        echo call_user_func($column['value'], $row);
                    } else {
                        echo $column['value'];
                    }
                } elseif (isset($column['field'])) {
                    $fieldName = $column['field'];
                    if ($row->hasProperty($fieldName)){
                        echo $row->$fieldName;
                    }
                }
            } elseif ($column['type'] == 'serialColumn') {
                echo Html::startTag('td', ['class' => 'col-md-1']);
                echo ($idx+1);
            } elseif ($column['type'] == 'actionColumn'){
                echo Html::startTag('td', ['class' => 'text-center']);
                if (isset($column['field'])){
                    if (!isset($column['buttons'])){
                        $column['buttons'] = ['view','update', 'delete'];
                    }
                    $fieldName = $column['field'];
                    foreach ($column['buttons'] as $type => $button){
                        switch ($type){
                            case 'update':
                                $class = 'glyphicon-pencil';
                                break;
                            case 'view':
                                $class = 'glyphicon-eye-open';
                                break;
                            case 'delete':
                                $class = 'glyphicon-remove';
                                break;
                            default:
                                $class = '';
                        }
                        echo Html::startTag('a', [
                            'class' => 'btn btn-default',
                            'href' => Url::toRoute((isset($button['route']) ? $button['route'] : $type).'?id='.$row->$fieldName)
                        ]);
                        echo Html::startTag('span', ['class' => 'glyphicon '.$class]).Html::endTag('span');
                        echo Html::endTag('a');
                    }
                }
            }
            echo Html::endTag('td');
        }
        echo Html::endTag('tr');
    }

    private function printPagination(){
        if (!$this->_paginationEnable){
            return;
        }
        $currentPage = isset(App::$instance->request->get['page']) ? (int)App::$instance->request->get['page'] : 1;
        echo Html::startTag('nav');
        echo Html::startTag('ul', ['class' => 'pagination']);
        for ($i = 0; $i < $this->_pageCount; $i++){
            echo Html::startTag('li', ['class' => ($currentPage == ($i+1) ? 'active' : '')]);
            $href = '?page='.($i+1);
            echo Html::startTag('a', ['href' => $href]);
            echo ($i+1);
            echo Html::endTag('a');
            echo Html::endTag('li');
        }
        echo Html::endTag('ul');
        echo Html::endTag('nav');

//        <nav aria-label="Page navigation">
//  <ul class="pagination">
//    <li>
//      <a href="#" aria-label="Previous">
//        <span aria-hidden="true">&laquo;</span>
//      </a>
//    </li>
//    <li><a href="#">1</a></li>
//    <li><a href="#">2</a></li>
//    <li><a href="#">3</a></li>
//    <li><a href="#">4</a></li>
//    <li><a href="#">5</a></li>
//    <li>
//      <a href="#" aria-label="Next">
//        <span aria-hidden="true">&raquo;</span>
//      </a>
//    </li>
//  </ul>
//</nav>
    }
}