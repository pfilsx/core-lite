<?php


namespace core\components;


use core\base\App;

class CoreController extends Controller
{

    public function actionIndex()
    {
        return null;
    }

    public function actionValidate(){
        $post = App::$instance->request->post;
        return json_encode(['message' => 'Not implemented yet']); // TODO
    }

}