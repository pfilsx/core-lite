<?php


namespace core\widgets\breadcrumbs;


use core\components\Widget;
use core\web\Html;

class Breadcrumbs extends Widget
{
    public $items = [];

    public function run()
    {
        ob_start();
        ob_implicit_flush(false);
        echo Html::startTag('ol', ['class' => 'crl-breadcrumb']);
        $this->renderItems();
        echo Html::endTag('ol');
        return ob_get_clean();
    }

    protected function renderItems(){
        foreach ($this->items as $key => $url){
            if ($url === null){
                echo "<li class='active'>$key</li>";
            } else {
                echo "<li><a href=\"$url\">$key</a></li>";
            }
        }
    }
}