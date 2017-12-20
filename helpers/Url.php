<?php


namespace core\helpers;

use Core;
use core\base\App;


class Url
{
    static function toRoute($url, array $params = [])
    {
        $baseUrl = App::$instance->request->getBaseUrl();
        if (is_string($url)){
            if (substr($url, 0, strlen($baseUrl)) == $baseUrl){
                return $url;
            }
        }
        $url = (array)$url;
        if (strncmp($url[0], '/', 1) !== 0){
            $url[0] = '/'.$url[0];
        }
        return $baseUrl.implode('/',$url).(!empty($params) ? '?'.static::prepareParams($params) : '');
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
        ksort($route);
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