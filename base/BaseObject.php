<?php


namespace core\base;


use core\exceptions\ErrorException;

abstract class BaseObject extends Configurable
{
    private static $_events = [];

    public static function className()
    {
        return get_called_class();
    }

    public function __construct($config = [])
    {
        parent::__construct($config);
        foreach ($this->_config as $key => $value){
            if ($this->canSetProperty($key)){
                $this->$key = $value;
            }
        }
        $this->init();
    }

    public function init(){
    }

    public function __get($name){
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . ucfirst($name))) {
            throw new \Exception('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new \Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    public function __set($name, $value){
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . ucfirst($name))) {
            throw new \Exception('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new \Exception('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    public function __isset($name)
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }

    public function __unset($name)
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } elseif (method_exists($this, 'get' . ucfirst($name))) {
            throw new \Exception('Unsetting read-only property: ' . get_class($this) . '::' . $name);
        }
    }

    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }

    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . ucfirst($name)) || $checkVars && property_exists($this, $name);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . ucfirst($name)) || $checkVars && property_exists($this, $name);
    }

    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }

    /**
     * Add an event handler
     * @param string $event - event name to handle
     * @param callable $handler - handler
     * @throws ErrorException - invalid args exception
     */
    public static final function addEventHandler($event, callable $handler){
        if (!is_string($event)){
            throw new ErrorException("Invalid argument passed. Event name must be a string");
        }
        if (!array_key_exists($event, static::$_events)){
            static::$_events[$event] = [];
        }
        static::$_events[$event][] = $handler;
    }
    /**
     * Remove an event handler
     * @param string $event - event name to remove handler
     * @param callable $handler - handler
     * @throws ErrorException - invalid args exception
     */
    public static final function removeEventHandler($event, callable $handler){
        if (!array_key_exists($event, static::$_events)){
            return;
        }
        if (!is_string($event)){
            throw new ErrorException("Invalid argument passed. Event name must be a string");
        }
        if (($index = array_search($handler, static::$_events[$event])) !== false){
            unset(static::$_events[$event][$index]);
        }
    }
    /**
     * Removes all event handlers for specific event or for all events
     * @param null||string $event - event name or null for all events
     * @throws ErrorException - invalid args exception
     */
    public function removeAllEventHandlers($event = null){
        if ($event == null){
            static::$_events = [];
            return;
        }
        if (!is_string($event)){
            throw new ErrorException("Invalid argument passed. Event name must be a string or null");
        }
        unset(static::$_events[$event]);
    }
    /**
     * Invoke a specific event by name
     * @param string $event - event name to invoke
     * @param array $args - array of arguments
     */
    protected function invokeEvent($event, $args = []){
        if (!array_key_exists($event, static::$_events)){
            return;
        }
        foreach (static::$_events[$event] as $handler){
            call_user_func($handler, array_merge($args, ['callerObj' => $this]));
        }
    }

    /**
     * Return list of specified events for class with their handlers
     * @return array
     */
    public static final function getEvents(){
        return static::$_events;
    }
}