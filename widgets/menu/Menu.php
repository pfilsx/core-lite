<?php


namespace core\widgets\menu;


use core\base\App;
use core\components\Widget;
use core\exceptions\ErrorException;
use core\helpers\ArrayHelper;
use core\web\Html;

class Menu extends Widget
{

    public $items;

    public $orientation = 'horizontal';

    public $itemOptions = [];

    protected $_class = 'crl-menu';
    protected $_class_horizontal = 'crl-menu-horizontal';

    protected $_currentUrl;
    protected $_currentRoute;

    public function init(){
        $this->_currentRoute = App::$instance->request->getBaseUrl().'/'.App::$instance->router->route;
        $this->_currentUrl = App::$instance->router->baseRoute;
        if (isset($this->itemOptions['href'])){
            unset($this->itemOptions['href']);
        }
    }

    public function run()
    {
        ob_start();
        ob_implicit_flush(false);
        if ($this->orientation == 'horizontal'){
            $this->_class .= " {$this->_class_horizontal}";
        }
        $this->render();
        return ob_get_clean();
    }

    protected function render(){
        echo Html::startTag('div', ArrayHelper::merge_recursive($this->options, ['class' => $this->_class]));
        foreach ($this->items as $item){
            if (!isset($item['label']) || (!isset($item['url']) && (!isset($item['items']) || !is_array($item['items'])))){
                throw new ErrorException('Invalid parameters passed to Menu::widget items');
            }
            $this->renderItem($item);
        }
        echo Html::endTag('div');
    }

    protected function renderItem($item){
        if (isset($item['items'])){
            echo Html::startTag('div', ArrayHelper::merge_recursive(['class' => 'crl-menu-item'], $this->itemOptions));
            echo $item['label'];
            echo Html::startTag('div', ['class' => 'crl-menu-subitems']);
            foreach ($item['items'] as $subItem){
                echo Html::tag('a', $subItem['label'], ArrayHelper::merge_recursive([
                    'href' => $subItem['url'],
                    'class' => 'crl-menu-subitem'.($this->_currentUrl == $subItem['url'] ? ' active' : '')
                ], isset($subItem['options']) ? $subItem['options'] : []));
            }
            echo Html::endTag('div');
            echo Html::endTag('div');
        } else {
            echo Html::tag('a', $item['label'],ArrayHelper::merge_recursive([
                'class' => 'crl-menu-item'.($this->_currentUrl == $item['url'] ? ' active' : ''),
                'href' => $item['url']
            ], $this->itemOptions));
        }
    }
}