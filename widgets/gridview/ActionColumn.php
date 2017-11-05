<?php


namespace core\widgets\gridview;


use core\db\QueryBuilder;
use core\helpers\Url;
use core\web\Html;

class ActionColumn extends BaseColumn
{

    public function getLabel()
    {
        return '';
    }

    public function getBody($data = null)
    {
        return Html::startTag('td', ['class' => 'col-md-2 text-center']).$this->getContent($data).Html::endTag('td');
    }

    public function getContent($data = null)
    {
        $primaryKey = '';
        if (isset($this->_config['field'])) {
            $primaryKey = $this->_config['field'];
        } elseif ($this->_gridView->dataProvider instanceof QueryBuilder && $this->_gridView->dataProvider->model != null) {
            $className = $this->_gridView->dataProvider->model;
            $model = $className::instance();
            $primaryKey = $model->primaryKey;
        }
        if (!empty($primaryKey)) {
            if (!isset($this->_config['buttons'])) {
                $this->_config['buttons'] = ['view', 'update', 'delete'];
            }
            $content = '';
            foreach ($this->_config['buttons'] as $type => $button) {
                switch ($type) {
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
                $content .= Html::startTag('a', [
                    'class' => 'btn btn-default',
                    'href' => Url::toRoute((isset($button['route']) ? $button['route'] : $type) . '?id=' . $data->$primaryKey)
                ]);
                $content .= Html::startTag('span', ['class' => 'glyphicon ' . $class]) . Html::endTag('span');
                $content .= Html::endTag('a');
            }
            return $content;
        }
        return '';
    }
}