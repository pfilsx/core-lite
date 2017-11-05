<?php


namespace core\widgets\pagination;


use core\base\Widget;
use core\helpers\Url;
use core\web\Html;

class Pagination extends Widget
{
    private $_currPage;

    private $_pageCount;

    public function init(){
        $this->_currPage = $this->_config['currentPage'];
        $this->_pageCount = $this->_config['pageCount'];
    }

    public function run()
    {
        ob_start();
        ob_implicit_flush(false);
        echo Html::startTag('nav');
        echo Html::startTag('ul', ['class' => 'pagination']);
        for ($i = 0; $i < $this->_pageCount; $i++){
            echo Html::startTag('li', ['class' => ($this->_currPage == ($i+1) ? 'active' : '')]);
            $href = '?'.implode('&', Url::prepareParams(array_merge($_REQUEST, ['page' => ($i+1)]))) ;
            echo Html::startTag('a', ['href' => $href]);
            echo ($i+1);
            echo Html::endTag('a');
            echo Html::endTag('li');
        }
        echo Html::endTag('ul');
        echo Html::endTag('nav');
        return ob_get_clean();
    }
}