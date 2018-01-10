<?php


namespace core\widgets\gridview;


use core\helpers\Url;
use core\providers\DataProvider;
use core\web\Html;

class DataColumn extends BaseColumn
{

    public function init(){
        parent::init();
        if ($this->_gridView->orderBy != null && $this->_gridView->orderBy === $this->name){
            if (isset($this->_config['sort']) && is_callable($this->_config['sort'])){
                $this->_needSort = true;
                $this->sortMethod = [$this, 'sort'];
            } elseif ($this->_gridView->dataProvider instanceof DataProvider){
                $this->_gridView->dataProvider->sort($this->name, $this->_gridView->orderDirection);
            }
        }
    }

    public function getLabel()
    {
        if ($this->_label == null) {
            if (isset($this->_config['label'])){
                $this->_label = $this->_config['label'];
            } elseif (isset($this->_config['field'])){
                if ($this->_gridView->dataProvider instanceof DataProvider){
                    $this->_label = $this->_gridView->dataProvider->getAttributeLabel($this->_config['field']);
                } else {
                    $this->_label = $this->_config['field'];
                }
            } else {
                $this->_label = '';
            }
        }
        return $this->_label;
    }

    public function getHeader(){
        $content = Html::startTag('th', ['class' => (isset($this->_config['class']) ? $this->_config['class'] : false)]);
        if (isset($this->_config['field']) || (!empty($this->name) && isset($this->_config['sort']))){
            $content .= Html::startTag('a', [
                        'href' => '?'.Url::prepareParams(array_merge($_REQUEST, ['sort'=>$this->name,
                                'sort_direction' =>($this->_gridView->orderDirection == 'DESC'
                                ? 'ASC'
                                : 'DESC'
                            )])),
                        'class' => $this->name == $this->_gridView->orderBy
                            ? ' sort-active '. ($this->_gridView->orderDirection == 'ASC' ? 'sort-up' : 'sort-down')
                            : ''
                    ]);
        }
        $content .= $this->label;
        if (isset($this->_config['field'])){
            $content .= Html::endTag('a');
        }
        $content .= Html::endTag('th');
        return $content;
    }

    public function getContent($data = null)
    {
        if (isset($this->_config['value'])){
            if (is_callable($this->_config['value'])){
                return call_user_func($this->_config['value'], $data);
            } else {
                return $this->_config['value'];
            }
        } elseif (isset($this->_config['field'])){
            $field = $this->_config['field'];
            return $this->_gridView->dataProvider->getField($field, $data);
//            return $data->$field;
        }
        return '';
    }
}