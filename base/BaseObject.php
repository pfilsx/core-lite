<?php


namespace core\base;


use core\exceptions\ErrorException;

abstract class BaseObject extends Configurable
{
    const EVENT_AFTER_INIT = 'object_after_init';

    protected static $_events = [];

    protected $_instance_events = [];

    /**
     * Returns fully qualified class name
     * @return string
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * BaseObject constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        foreach ($this->_config as $key => $value){
            if ($this->canSetProperty($key)){
                $this->$key = $value;
            }
        }
        $this->init();
        $this->invoke(static::EVENT_AFTER_INIT);
    }

    /**
     * Initialize method. Use it if you need some actions in constructor.
     */
    public function init(){
    }

    /**
     * Magic get method for properties
     * @param $name
     * @return mixed
     * @throws ErrorException
     */
    public function __get($name){
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . ucfirst($name))) {
            throw new ErrorException('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new ErrorException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Magic set method for properties
     * @param $name
     * @param mixed $value
     * @throws \Exception
     */
    public function __set($name, $value){
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . ucfirst($name))) {
            throw new ErrorException('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new ErrorException('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Magic isset method for properties
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }

    /**
     * Magic unset method for properties
     * @param $name
     * @throws ErrorException
     */
    public function __unset($name)
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } elseif (method_exists($this, 'get' . ucfirst($name))) {
            throw new ErrorException('Unsetting read-only property: ' . get_class($this) . '::' . $name);
        }
    }
    /**
     * Returns a value indicating whether a property is defined.
     *
     * A property is defined if:
     *
     * - the class has a getter or setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name - the property name
     * @param bool $checkVars - whether to treat member variables as properties
     * @return bool - whether the property is defined
     * @see canGetProperty()
     * @see canSetProperty()
     */
    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }
    /**
     * Returns a value indicating whether a property can be read.
     *
     * A property is readable if:
     *
     * - the class has a getter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name - the property name
     * @param bool $checkVars - whether to treat member variables as properties
     * @return bool - whether the property can be read
     * @see canSetProperty()
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . ucfirst($name)) || $checkVars && property_exists($this, $name);
    }
    /**
     * Returns a value indicating whether a property can be set.
     *
     * A property is writable if:
     *
     * - the class has a setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string $name - the property name
     * @param bool $checkVars - whether to treat member variables as properties
     * @return bool - whether the property can be written
     * @see canGetProperty()
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . ucfirst($name)) || $checkVars && property_exists($this, $name);
    }
    /**
     * Returns a value indicating whether a method is defined.
     *
     * The default implementation is a call to php function `method_exists()`.
     * You may override this method when you implemented the php magic method `__call()`.
     * @param string $name - the method name
     * @return bool - whether the method is defined
     */
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
        if (!isset(static::$_events[static::className()])){
            static::$_events[static::className()] = [];
        }
        if (!array_key_exists($event, static::$_events[static::className()])){
            static::$_events[static::className()][$event] = [];
        }
        static::$_events[static::className()][$event][] = $handler;
    }
    /**
     * Add an event handler for specific instance
     * @param string $event - event name to handle
     * @param callable $handler - handler
     * @throws ErrorException - invalid args exception
     */
    public final function addInstanceEventHandler($event, callable $handler){
        if (!is_string($event)){
            throw new ErrorException("Invalid argument passed. Event name must be a string");
        }
        if (!array_key_exists($event, $this->_instance_events)){
            $this->_instance_events[$event] = [];
        }
        $this->_instance_events[$event][] = $handler;
    }

    /**
     * Remove an event handler
     * @param string $event - event name to remove handler
     * @param callable $handler - handler
     * @throws ErrorException - invalid args exception
     */
    public static final function removeEventHandler($event, callable $handler){
        if (!is_string($event)){
            throw new ErrorException("Invalid argument passed. Event name must be a string");
        }
        if (!isset(static::$_events[static::className()])){
            static::$_events[static::className()] = [];
        }
        if (!array_key_exists($event, static::$_events[static::className()])){
            return;
        }
        if (($index = array_search($handler, static::$_events[static::className()][$event])) !== false){
            unset(static::$_events[static::className()][$event][$index]);
        }
    }
    /**
     * Remove an event handler for specific instance
     * @param string $event - event name to remove handler
     * @param callable $handler - handler
     * @throws ErrorException - invalid args exception
     */
    public final function removeInstanceEventHandler($event, callable $handler){
        if (!is_string($event)){
            throw new ErrorException("Invalid argument passed. Event name must be a string");
        }
        if (!array_key_exists($event, $this->_instance_events)){
            return;
        }
        if (($index = array_search($handler, $this->_instance_events[$event])) !== false){
            unset($this->_instance_events[$event][$index]);
        }
    }
    /**
     * Removes all event handlers for specific event or for all events
     * @param null||string $event - event name or null for all events
     * @throws ErrorException - invalid args exception
     */
    public static final function removeAllEventHandlers($event = null){
        if ($event == null || !isset(static::$_events[static::className()])){
            static::$_events[static::className()] = [];
            return;
        }
        if (!is_string($event)){
            throw new ErrorException("Invalid argument passed. Event name must be a string or null");
        }
        unset(static::$_events[static::className()][$event]);
    }
    /**
     * Removes all event handlers for specific event or for all events for specific instance
     * @param null||string $event - event name or null for all events
     * @throws ErrorException - invalid args exception
     */
    public final function removeAllInstanceEventHandlers($event = null)
    {
        if ($event == null){
            $this->_instance_events = [];
            return;
        }
        if (!is_string($event)){
            throw new ErrorException("Invalid argument passed. Event name must be a string or null");
        }
        unset($this->_instance_events[$event]);
    }

    /**
     * Invoke a specific event by name
     * @param string $event - event name to invoke
     * @param array $args - array of arguments
     * @throws ErrorException - invalid args exception
     */
    protected function invoke($event, $args = []){
        if (!is_string($event)){
            throw new ErrorException("Invalid argument passed. Event name must be a string or null");
        }
        if (!isset(static::$_events[static::className()])){
            static::$_events[static::className()] = [];
        }
        $classHandlers = array_key_exists($event, static::$_events[static::className()]);
        $instanceHandlers = array_key_exists($event, $this->_instance_events);
        if (!$classHandlers && !$instanceHandlers){
            return;
        }
        $args = array_merge($args, ['callerObj' => $this]);
        if ($classHandlers){
            foreach (static::$_events[static::className()][$event] as $handler){
                call_user_func($handler, $args);
            }
        }
        if ($instanceHandlers){
            foreach ($this->_instance_events[$event] as $handler){
                call_user_func($handler, $args);
            }
        }
    }

    /**
     * Return list of specified events for class with their handlers
     * @return array
     */
    public static final function getEvents(){
        if (!isset(static::$_events[static::className()])){
            static::$_events[static::className()] = [];
        }
        return static::$_events[static::className()];
    }
    /**
     * Return list of specified events for specific instance with their handlers
     * @return array
     */
    public final function getInstanceEvents(){
        return $this->_instance_events;
    }

}