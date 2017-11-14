<?php


namespace core\components;

use core\base\App;
use core\base\BaseObject;
use core\validators\Validator;
use core\validators\ValidatorInterface;


abstract class Model extends BaseObject
{
    protected $user_properties = [];

    protected $rules = [];

    public static function instance(){
        $className = get_called_class();
        return new $className();
    }

    public function init(){
        foreach ($this->rules as $key => $value){
            $this->createProperty($key);
        }
    }

    public function beforeLoad($data){
    }

    public function load(array $data){
        $this->beforeLoad($data);

        if (isset($data[$this->getModelName()])){
            $data = $data[$this->getModelName()];
        }
        foreach ($data as $key => $value){
            if (array_key_exists($key, $this->user_properties)) {
                $this->$key = $value;
            }
        }
        $this->afterLoad($data);
        return true;
    }

    public function afterLoad($data){
    }

    public function __get($property) {
        if (array_key_exists($property, $this->user_properties)) {
            return $this->user_properties[$property];
        }
        $getter = 'get' . ucfirst($property);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return null;
    }

    public function __set($property, $value) {
        if (array_key_exists($property, $this->user_properties)) {
            if (is_bool($value) === true){
                $value = (int)$value;
            }
            return $this->user_properties[$property] = $value;
        }
        return $this;
    }

    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars)
            || $this->canSetProperty($name, false)
            || array_key_exists($name, $this->user_properties);
    }

    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . ucfirst($name)) || $checkVars && property_exists($this, $name);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . ucfirst($name)) || $checkVars && property_exists($this, $name);
    }

    protected function createProperty($name, $value = null){
        if (!array_key_exists($name, $this->user_properties))
            $this->user_properties[$name] = $value;
    }

    public function attributeLabels()
    {
        return [];
    }

    public final function getAttributeLabel($name){
        return isset($this->attributeLabels()[$name]) ? $this->attributeLabels()[$name] : $name;
    }

    public final function getAttributeName($attribute){
        if (!$this->hasProperty($attribute)){
            return $attribute;
        }
        return 'crl_'.$this->getModelName().'_'.$attribute;
    }

    public final function getModelName(){
        $ref = new \ReflectionClass($this);
        return str_replace('_', '', strtolower(preg_replace(
            '/(?<=[a-z])([A-Z]+)/',
            '-$1',
            $ref->getShortName()
        )));

    }

    /**
     * @return bool|array
     */
    public function validate(){
        $errors = [];
        foreach ($this->user_properties as $key => $value){
            if (($valResult = $this->validateField($key)) !== true){
                $errors[] = $valResult;
            }
        }
        return empty($errors) ? true : $errors;
    }

    /**
     * @param string $fieldName
     * @return bool|string
     */
    public function validateField($fieldName){
        if (!$this->hasProperty($fieldName)){
            return true;
        }
        $validateResult = true;
        $field = $this->$fieldName;
        foreach ($this->rules as $rule){
            if (isset($rule[0]) && isset($rule[1])){
                $ruleFields = (array) $rule[0];
                if (in_array($fieldName, $ruleFields)){
                    /**
                     * @var ValidatorInterface $validator
                     */
                    $validator = Validator::createValidator($rule[1], ['properties' => array_slice($rule, 2)]);
                    if ($validator != null){
                        $validateResult = $validator->validateValue($field);
                        if ($validateResult !== true){
                            return str_replace('{attribute}', $this->getAttributeLabel($fieldName), $validateResult);
                        }
                    }
                }
            }
        }
        return $validateResult;
    }

    public function ajaxValidate(){
        $result = $result = $this->validateField(App::$instance->request->post['fieldName']);
        if ($result === true){
            return json_encode([]);
        } else {
            return json_encode(['message' => $result]);
        }
    }
}