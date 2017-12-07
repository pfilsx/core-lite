<?php


namespace core\components;


use core\base\App;
use core\validators\Validator;

class CoreController extends Controller
{

    public function actionIndex()
    {
        return null;
    }

    public function actionValidate(){
        $post = App::$instance->request->post;
        if (isset($post['value']) || isset($post['validator'])) {
            $validator = Validator::createValidator($post['validator'], (isset($post['params']) ? $post['params'] : []));
            if ($validator !== null){
                $result = $validator->validateValue($post['value']);
                if ($result !== true){
                    return json_encode(['success' => false, 'message' => $result]);
                }
            }
        }
        return json_encode(['success' => true]);
    }

}