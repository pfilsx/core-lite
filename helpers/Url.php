<?php


namespace core\helpers;

use core\base\App;


class Url
{
    static function toRoute($url, array $params = [])
    {
        if (is_array($url)) {
            $url = '/' . str_replace('/', '', $url[0])
                . '/' . (isset($url[1]) ? str_replace('/', '', $url[1]) : 'index')
                . (isset($url[2]) ? '?' . http_build_query($url[2], '', '&') : '');
        } else if (is_string($url)) {
            $url = (substr($url,0,1) == '/' ? '' : '/').$url;
        }

        return App::$instance->request->getBaseUrl().$url
            .(!empty($params) ? '?'.static::prepareParams($params) : '');
    }
    static function toAction($action, array $params= []){
        $controller = App::$instance->getRouter()->controller;
        return static::toRoute([$controller, $action], $params);
    }

    static function prepareParams(array $params){
        $prepareParams = [];
        if (isset($params['_pjax'])){
            unset($params['_pjax']);
        }
        foreach ($params as $key => $value){
            $prepareParams[] = $key.'='.$value;
        }
        return implode('&', $prepareParams);
    }
}