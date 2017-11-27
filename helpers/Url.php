<?php


namespace core\helpers;

use Core;
use core\base\App;


class Url
{
    static function toRoute($url, array $params = [])
    {
        if (is_array($url)) {
//            $url = '/' . str_replace('/', '', $url[0])
//                . '/' . (isset($url[1]) ? str_replace('/', '', $url[1]) : 'index')
//                . (isset($url[2]) ? '?' . http_build_query($url[2], '', '&') : '');
            $url = '/'.implode('/', $url);
        } else if (is_string($url)) {
            $url = (substr($url,0,1) == '/' ? '' : '/').$url;
        }
        return App::$instance->request->getBaseUrl().$url
            .(!empty($params) ? '?'.static::prepareParams($params) : '');
    }

    static function toAction($action, array $params= []){

        $controller = App::$instance->getRouter()->controller;
        $module = App::$instance->getRouter()->module;
        $route = [
            1 => $controller,
            2 => $action
        ];
        if ($module !== null){
            $route[0] = $module;
        }
        return static::toRoute($route, $params);
    }

    static function prepareParams(array $params){
        if (isset($params['_pjax'])){
            unset($params['_pjax']);
        }
        if (isset($params['#'])){
            $anchor = '#'.$params['#'];
        }
        return http_build_query($params, '', '&').(isset($anchor) ? $anchor : '');
    }


}