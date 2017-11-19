<?php


namespace core\widgets\pagination;


use core\base\App;
use core\base\Widget;
use core\helpers\Url;
use core\web\Html;

/**
 * Class Pagination
 * @package core\widgets\pagination
 *
 * @property int currentPage
 * @property int pageSize
 * @property int pageCount
 */
class Pagination extends Widget
{
    private $_currPage;

    public $pageSize;

    public $pageCount;

    public function init(){
        parent::init();
        $this->_currPage = isset(App::$instance->request->get['page'])
            ? (int)App::$instance->request->get['page']
            : 1;
    }

    public function run()
    {
        if ($this->_currPage > $this->pageCount){
            $this->_currPage = 1;
        }
        ob_start();
        ob_implicit_flush(false);
        echo Html::startTag('nav');
        echo Html::startTag('ul', ['class' => 'pagination']);
        for ($i = 0; $i < $this->pageCount; $i++){
            echo Html::startTag('li', ['class' => ($this->_currPage == ($i+1) ? 'active' : '')]);
            $href = '?'.Url::prepareParams(array_merge($_REQUEST, ['page' => ($i+1)]));
            echo Html::startTag('a', ['href' => $href]);
            echo ($i+1);
            echo Html::endTag('a');
            echo Html::endTag('li');
        }
        echo Html::endTag('ul');
        echo Html::endTag('nav');
        return ob_get_clean();
    }

    public function getCurrentPage(){
        return $this->_currPage;
    }
}