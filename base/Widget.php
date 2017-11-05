<?php


namespace core\base;


abstract class Widget extends BaseObject
{
    protected static $stack = [];

    public static function begin(array $config = []){
        $widgetClass = get_called_class();
        $widget = new $widgetClass($config);
        static::$stack[] = $widget;
        return $widget;
    }
    public abstract function run();

    public static function widget(array $config = []){
        $widgetClass = get_called_class();
        /**
         * @var Widget $widget
         */
        $widget = new $widgetClass($config);
        echo $widget->run();
    }

    public static function end(){
        if (!empty(static::$stack)){
            $widget = array_pop(static::$stack);
            if (get_class($widget) === get_called_class()) {
                echo $widget->run();
                return $widget;
            } else {
                throw new \Exception('Expecting end() of ' . get_class($widget) . ', found ' . get_called_class());
            }
        } else {
            throw new \Exception('Unexpected ' . get_called_class() . '::end() call. A matching begin() is not found.');
        }
    }
}