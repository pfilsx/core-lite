<?php


namespace core\web;


class Html
{
    public static function beginForm($action = null, $method = 'post', $options = []){

        return static::startTag('form', array_merge([
            'action' => $action,
            'method' => $method
        ], $options));
    }

    public static function endForm(){
        return '</form>'.PHP_EOL;
    }

    public static function fileInput($name, $value = null ,$options = []){
        return static::startTag('input', array_merge([
            'name' => $name,
            'type' => 'file'
        ], $options));
    }

    public static function hiddenInput($name, $value = null, $options = []){
        return static::startTag('input', array_merge([
            'type' => 'hidden',
            'name' => $name,
            'id' => $name,
            'value' => $value
        ], $options));
    }

    public static function input($name, $type = 'text', $value = null, $options = []){
        return static::startTag('input', array_merge([
            'type' => $type,
            'id' => $name,
            'name' => $name,
            'value' => $value
        ], $options));
    }

    public static function textarea($name, $value = null, $options = []){
        return static::startTag('textarea', array_merge([
            'name' => $name,
            'id' => $name
            ],$options)).$value.static::endTag('textarea');
    }

    public static function label($text, $for = null, $options = []){
        return static::startTag('label', array_merge([
            'for' => $for
        ], $options)).$text.static::endTag('label');
    }

    public static function checkbox($name, $value, $options = []){
        return static::startTag('input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => ($value === 1 ? 1 : 0)
        ]).static::startTag('input', array_merge([
                'type' => 'checkbox',
                'checked' => ($value === 1 ? true : false),
                'id' => $name
            ],$options));
    }

    public static function radio($name, $value, $options = []){
        return static::startTag('input', [
                'type' => 'hidden',
                'name' => $name,
                'value' => ($value === 1 ? 1 : 0)
            ]).static::startTag('input', array_merge([
                'type' => 'radio',
                'checked' => ($value === 1 ? true : false),
                'id' => $name
            ],$options));
    }

    public static function select($name, $items, $value = null, $options = []){
        if (!is_array($items)){
            throw new \Exception('Parameter $items must be array');
        }
        $html = static::startTag('select', array_merge($options, [
            'name' => $name
        ]));
        foreach ($items as $key => $text){
            $html .= static::startTag('option', [
                'value' => $key,
                'selected' => $key === $value
                ]).$text.static::endTag('option');
        }
        $html .= static::endTag('select');
        return $html;
    }


    public static function startTag($name, $attributes = []){
        return "<$name ".static::renderTagAttributes($attributes).'>'.PHP_EOL;
    }

    public static function endTag($name){
        return "</$name>".PHP_EOL;
    }

    public static function renderTagAttributes($attributes){
        $html = '';
        foreach ($attributes as $key => $value){
            if ($value !== null && $value !== false){
                $html .= ' '.$key.'="'.$value.'" ';
            }
        }
        return $html;
    }
}