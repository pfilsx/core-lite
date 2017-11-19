<?php


namespace core\translate;


use Core;
use core\base\BaseObject;
use core\helpers\FileHelper;

class TranslateManager extends BaseObject
{

    private $_translators = [];

    public function init()
    {
        $coreClassName = 'core\translations\DefaultSource';
        $this->_translators['crl'] = new $coreClassName();

        $userTranslationsDirectory = FileHelper::normalizePath(Core::getAlias('@app') . '/translations');
        if (is_dir($userTranslationsDirectory)) {
            foreach (FileHelper::findFiles($userTranslationsDirectory) as $file) {
                $className = 'translations\\' . str_replace('.php', '', basename($file));
                $translator = new $className();
                if ($translator instanceof TranslateSource) {
                    $this->_translators[$translator->getName()] = $translator;
                }
            }
        }
    }

    public function translate($dictionary, $message, $_params_ = [], $language = null)
    {
        $params = [];
        foreach ($_params_ as $key => $value){
            if (substr($key, 0, 1) !== '{'){
                $params['{'.$key.'}'] = $value;
            } else {
                $params[$key] = $value;
            }
        }
        if (!array_key_exists($dictionary,$this->_translators)){
            return strtr($message, $params);
        }
        return $this->_translators[$dictionary]->translate($message, $language, $params);
    }

    public function getTranslators()
    {
        return $this->_translators;
    }

}