<?php


namespace core\components;

use core\base\App;
use core\base\BaseObject;
use core\validators\Validator;
use core\validators\ValidatorInterface;


/**
 * @property bool hasErrors
 * @property array attributes
 */
abstract class Model extends BaseObject
{
    protected $user_properties = [];

    protected $rules = [];

    protected $_errors = [];

    const EVENT_BEFORE_VALIDATE = 'model_before_validate';
    const EVENT_AFTER_VALIDATE = 'model_after_validate';
    const EVENT_BEFORE_VALIDATE_ATTR = 'model_before_validate_attr';
    const EVENT_AFTER_VALIDATE_ATTR = 'model_after_validate_attr';
    const EVENT_BEFORE_LOAD = 'model_before_load';
    const EVENT_AFTER_LOAD = 'model_after_load';

    /**
     * @var ValidatorInterface[]
     */
    private $_activeValidators = [];

    public static function instance(){
        $className = get_called_class();
        return new $className();
    }

    public function __construct(array $config = [])
    {
        $this->initializeAttributes();
        $this->initializeValidators();
        parent::__construct($config);
    }

    protected function initializeAttributes(){
        foreach ($this->rules as $rule){
            $fields = $rule[0];
            if (is_array($fields)){
                foreach ($fields as $field){
                    if (!$this->hasProperty($field)){
                        $this->createProperty($field);
                    }
                }
            } else {
                if (!$this->hasProperty($fields)){
                    $this->createProperty($fields);
                }
            }
        }
    }

    protected function initializeValidators(){
        foreach ($this->rules as $rule){
            if (isset($rule[0]) && isset($rule[1])){
                $attributes = (array) $rule[0];
                $validator = Validator::createModelValidator($this, $attributes, $rule[1], array_slice($rule, 2));
                if ($validator != null){
                   $this->_activeValidators[] = $validator;
                }
            }
        }
    }

    public function beforeLoad($data){
    }

    public function load(array $data){
        $this->beforeLoad($data);
        $this->invoke(self::EVENT_BEFORE_LOAD, ['data' => $data]);
        if (isset($data[$this->getModelName()])){
            $data = $data[$this->getModelName()];
        }
        foreach ($data as $key => $value){
            if (array_key_exists($key, $this->user_properties)) {
                $this->$key = $value;
            }
        }
        $this->afterLoad($data);
        $this->invoke(self::EVENT_AFTER_LOAD, ['data' => $data]);
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
        return method_exists($this, 'get' . ucfirst($name)) || $checkVars && property_exists($this, $name)
            || array_key_exists($name, $this->user_properties);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . ucfirst($name)) || $checkVars && property_exists($this, $name)
            || array_key_exists($name, $this->user_properties);
    }

    protected function createProperty($name, $value = null){
        if (!array_key_exists($name, $this->user_properties))
            $this->user_properties[$name] = $value;
    }

    public function attributeLabels()
    {
        return [];
    }

    public final function getAttributes(){
        return array_keys($this->user_properties);
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
    public function getHasErrors(){
        return !empty($this->_errors);
    }
    public function hasError($attribute){
        return array_key_exists($attribute, $this->_errors);
    }
    public function getErrors($attribute = null){
        if ($attribute != null)
            return $this->_errors[$attribute];
        return $this->_errors;
    }

    /**
     * Validate all model attributes by validators specified in rules
     * @return bool - result of validation for all model attributes
     */
    public function validate(){
        $this->invoke(self::EVENT_BEFORE_VALIDATE);
        $this->_errors = [];
        foreach ($this->user_properties as $key => $value){
            if (($valResult = $this->validateAttribute($key)) !== true){
                $this->_errors[$key][] = $valResult;
            }
        }
        $this->invoke(self::EVENT_AFTER_VALIDATE);
        return !$this->hasErrors ? true : false;
    }

    /**
     * Validate a specific attribute
     * @param string $attributeName
     * @return bool|string
     */
    public function validateAttribute($attributeName){
        $this->invoke(self::EVENT_BEFORE_VALIDATE_ATTR, ['attribute' => $attributeName]);
        if (!$this->hasProperty($attributeName)){
            unset($this->_errors[$attributeName]);
            return true;
        }
        /**
         * @var Validator $validator
         */
        foreach ($this->_activeValidators as $validator){
            if (in_array($attributeName, $validator->getValidatorAttributes())){
                $validateResult = $validator->validateAttribute($attributeName);
                if ($validateResult !== true){;
                    $this->_errors[$attributeName][] = $validateResult;
                    $this->invoke(self::EVENT_AFTER_VALIDATE_ATTR, ['attribute' => $attributeName]);
                    return $validateResult;
                }
            }
        }
        unset($this->_errors[$attributeName]);
        $this->invoke(self::EVENT_AFTER_VALIDATE_ATTR, ['attribute' => $attributeName]);
        return true;
    }

    public function ajaxValidate(){
        $result = $result = $this->validateAttribute(App::$instance->request->post['attributeName']);
        if ($result === true){
            return json_encode(['success' => true]);
        } else {
            return json_encode(['success' => false, 'message' => $result]);
        }
    }
}