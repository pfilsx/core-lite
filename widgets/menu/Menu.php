<?php


namespace core\widgets\menu;


use core\base\App;
use core\components\Widget;
use core\helpers\ArrayHelper;
use core\web\Html;

class Menu extends Widget
{

    public $items;

    public $orientation = 'horizontal';

    public $itemOptions = [];

    protected $_currentUrl;
    protected $_currentRoute;

    public function init(){
        $this->_currentRoute = App::$instance->request->getBaseUrl().'/'.App::$instance->router->route;
        $this->_currentUrl = App::$instance->router->baseRoute;
        if (isset($this->itemOptions['url'])){
            unset($this->itemOptions['url']);
        }
    }

    public function run()
    {
        ob_start();
        ob_implicit_flush(false);
        if ($this->orientation == 'horizontal'){
            $this->renderHorizontal();
        } else {
            $this->renderVertical();
        }
        return ob_get_clean();
    }

    protected function renderHorizontal(){
        echo Html::startTag('div', ArrayHelper::merge_recursive($this->options, ['class' => ' crm-menu']));
        foreach ($this->items as $item){
            if (!isset($item['label']) || !isset($item['url'])){
                throw new \Exception('Invalid parameters passed to Menu::widget items');
            }
            echo Html::tag('a', $item['label'], ArrayHelper::merge_recursive($this->itemOptions, [
                'class' => ($this->_currentUrl == $item['url'] || $this->_currentRoute == $item['url']
                    ? 'active'
                    : ''),
                'url' => $item['url']
            ]));
        }
        echo Html::endTag('div');
    }
    protected function renderVertical(){
        echo Html::startTag('div', ArrayHelper::merge_recursive($this->options, ['class' => ' crm-menu crl-menu-horizontal']));
        foreach ($this->items as $item){
            if (!isset($item['label']) || !isset($item['url'])){
                throw new \Exception('Invalid parameters passed to Menu::widget items');
            }
            echo Html::tag('a', $item['label'], ArrayHelper::merge_recursive($this->itemOptions, [
                'class' => ($this->_currentUrl == $item['url'] || $this->_currentRoute == $item['url']
                    ? 'active'
                    : ''),
                'url' => $item['url']
            ]));
        }
        echo Html::endTag('div');
    }
}