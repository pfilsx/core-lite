<?php


namespace core\widgets\gridview;


use core\base\BaseObject;
use core\web\Html;

/**
 * @property string content
 * @property string label
 * @property string name
 */
abstract class BaseColumn extends BaseObject
{
    protected $_name;
    /**
     * @var GridView
     */
    protected $_gridView;

    protected $_needSort = false;

    public $sortMethod = null;

    public function __construct($gridview, array $config = [])
    {
        $this->_gridView = $gridview;
        if (isset($config['name'])){
            $this->_name = $config['name'];
        } elseif (isset($config['field'])){
            $this->_name = $config['field'];
        } else {
            $this->_name = '';
        }
        parent::__construct($config);
    }

    public function afterInit(){

    }

    public function getHeader(){
        return Html::startTag('th', ['class' => (isset($this->_config['class']) ? $this->_config['class'] : false)])
            .$this->label
            .Html::endTag('th');
    }
    public function getBody($data = null){
        return Html::startTag('td', ['class' => (isset($this->_config['class']) ? $this->_config['class'] : false)])
            .$this->getContent($data)
            .Html::endTag('td');
    }

    public function getName(){
        return $this->_name;
    }

    public function getNeedSort(){
        return $this->_needSort;
    }

    public function sort($obj1, $obj2){
        $result = $this->_config['sort']($obj1, $obj2);
        if ($this->_gridView->orderDirection == 'ASC'){
            $result = -1*$result;
        }
        return $result;
    }

    public abstract function getLabel();
    public abstract function getContent($data = null);
}