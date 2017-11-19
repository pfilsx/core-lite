<?php


namespace core\widgets\gridview;


use core\helpers\Url;
use core\providers\ActiveDataProvider;
use core\web\Html;

class ActionColumn extends BaseColumn
{
    private $_primaryKey;

    public function init(){
        parent::init();
        if (isset($this->_config['field'])) {
            $this->_primaryKey = $this->_config['field'];
        } elseif ($this->_gridView->dataProvider instanceof ActiveDataProvider) {
            if (($query = $this->_gridView->dataProvider->query) !== null){
                $className = $query->model;
                $model = $className::instance();
                $this->_primaryKey = $model->primaryKey;
            }
        }
    }

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
        if (!empty($this->_primaryKey)) {
            if (!isset($this->_config['buttons'])) {
                $this->_config['buttons'] = ['view', 'update', 'delete'];
            }
            $content = $this->getButtons($this->_config['buttons'], $data);

            return $content;
        }
        return '';
    }

    private function getButtons($buttons, $data){
        $content = '';
        if (isset($buttons[0])){
            foreach ($buttons as $button){
                if (isset($button['template']) && is_callable($button['template'])){
                    $content .= call_user_func($button['template'], $data);
                } else {
                    $content .= $this->createDefaultButton($button, $data);
                }
            }
        } else {
            foreach ($buttons as $type => $button) {
                if (isset($button['template']) && is_callable($button['template'])){
                    $content .= call_user_func($button['template'], $data);
                } else {
                    $content .= $this->createDefaultButton($type, $data, (isset($button['route']) ? $button['route'] : null));
                }
            }
        }
        return $content;
    }

    private function createDefaultButton($type, $data, $route = null){
        $html = '';
        $primaryKey = $this->_primaryKey;
        switch ($type) {
            case 'update':
                $class = 'pencil';
                break;
            case 'view':
                $class = 'eye-open';
                break;
            case 'delete':
                $class = 'remove';
                break;
            default:
                $class = '';
        }
        if ($route != null){
            $url = Url::toRoute($route, ['id' => $data->$primaryKey]);
        } else {
            $url = Url::toAction($type, ['id' => $data->$primaryKey]);
        }
        $html .= Html::startTag('a', [
            'class' => 'btn btn-default',
            'href' => $url
        ]);
        $html .= Html::startTag('span', ['class' => 'glyphicon glyphicon-' . $class]) . Html::endTag('span');
        $html .= Html::endTag('a');
        return $html;
    }
}