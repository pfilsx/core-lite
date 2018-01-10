<?php


namespace core\web;


use core\helpers\ArrayHelper;

class Html
{
    public static function beginForm($action = null, $method = 'post', $options = []){

        $csrf = ArrayHelper::remove($options, 'csrf', true);
        $html = static::startTag('form', array_merge([
            'action' => $action,
            'method' => $method
        ], $options));

        if ($csrf){
            $request = App::$instance->request;
            if ($request->enableCsrfValidation && strcasecmp($method, 'post') === 0) {
                $html .= static::hiddenInput($request->csrfParam, $request->getCsrfToken());
            }
        }
        return $html;
    }

    public static function endForm(){
        return static::endTag('form');
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
        if (isset($options['empty'])){
            $empty = $options['empty'];
            unset($options['empty']);
        }
        $html = static::startTag('select', array_merge($options, [
            'name' => $name
        ]));
        if (isset($empty)){
            $html .= static::startTag('option', ['value' => null]).$empty.static::endTag('option');
        }
        foreach ($items as $key => $text){
            $html .= static::startTag('option', [
                'value' => $key,
                'selected' => $key === $value
                ]).$text.static::endTag('option');
        }
        $html .= static::endTag('select');
        return $html;
    }

    public static function submitButton($text, $options = []){
        return static::tag('input', $text, array_merge($options, ['type' => 'submit']));
    }

    public static function startTag($name, $attributes = []){
        return "<$name ".static::renderTagAttributes($attributes).'>'.PHP_EOL;
    }

    public static function endTag($name){
        return "</$name>".PHP_EOL;
    }
    public static function tag($name, $content = '', $attributes = []){
        if ($name === null || $name === false) {
            return $content;
        }
        $html = "<$name" . static::renderTagAttributes($attributes) . '>';
        return isset(static::$voidElements[strtolower($name)]) ? $html : "$html$content</$name>";
    }

    public static $voidElements = [
        'area' => 1,
        'base' => 1,
        'br' => 1,
        'col' => 1,
        'command' => 1,
        'embed' => 1,
        'hr' => 1,
        'img' => 1,
        'input' => 1,
        'keygen' => 1,
        'link' => 1,
        'meta' => 1,
        'param' => 1,
        'source' => 1,
        'track' => 1,
        'wbr' => 1,
    ];

    public static function renderTagAttributes($attributes){
        $html = '';
        foreach ($attributes as $key => $value){
            if ($value !== null && $value !== false){
                $html .= ' '.$key.'="'.$value.'" ';
            }
        }
        return $html;
    }
    /**
     * Encodes special characters into HTML entities.
     * @param string $content the content to be encoded
     * @param bool $doubleEncode whether to encode HTML entities in `$content`. If false,
     * HTML entities in `$content` will not be further encoded.
     * @return string the encoded content
     * @see decode()
     */
    public static function encode($content, $doubleEncode = true)
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, App::$instance ? App::$instance->charset : 'UTF-8', $doubleEncode);
    }
    /**
     * Decodes special HTML entities back to the corresponding characters.
     * This is the opposite of [[encode()]].
     * @param string $content the content to be decoded
     * @return string the decoded content
     */
    public static function decode($content)
    {
        return htmlspecialchars_decode($content, ENT_QUOTES);
    }
}