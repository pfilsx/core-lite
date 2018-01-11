<?php


namespace core\console;


use Core;
use core\base\BaseObject;
use core\base\BaseRouter;
use core\components\Controller;
use core\exceptions\ErrorException;
use core\exceptions\NotFoundException;
use core\web\Response;

class Router extends BaseRouter
{

    public function init(){
        $this->_controllersNamespace = 'core\console\controllers';
        $this->_controllersPath = Core::getAlias('@crl/console/controllers');
    }

    public function route(){
        $this->parseRequest();
        $this->parseRoute();
        $result = $this->defaultResolve();
        if ($result instanceof Response){
            return $result->data;
        } else {
            return $result;
        }
    }

    protected function parseRequest()
    {
        $this->_route = $this->_baseRoute = App::$instance->request->getRoute();
    }
}