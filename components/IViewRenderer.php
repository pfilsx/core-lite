<?php


namespace core\components;


interface IViewRenderer
{
    function renderFile($view, $file, $params);
}