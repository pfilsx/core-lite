<?php


namespace core\helpers;


class Inflector
{
    const TRANSLITERATE_LOOSE = 'Any-Latin; Latin-ASCII; [\u0080-\uffff] remove';

    public static $transliterator = self::TRANSLITERATE_LOOSE;

    public static $transliteration = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
        'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
        'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
        'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
        'ÿ' => 'y',
    ];

    public static function transliterate($string, $transliterator = null)
    {
        if (static::hasIntl()) {
            if ($transliterator === null) {
                $transliterator = static::$transliterator;
            }
            return transliterator_transliterate($transliterator, $string);
        }
        return strtr($string, static::$transliteration);
    }

    protected static function hasIntl()
    {
        return extension_loaded('intl');
    }

    public static function camel2id($name, $separator = '-', $strict = false)
    {
        $regex = $strict ? '/[A-Z]/' : '/(?<![A-Z])[A-Z]/';
        if ($separator === '_') {
            return strtolower(trim(preg_replace($regex, '_\0', $name), '_'));
        }
        return strtolower(trim(str_replace('_', $separator, preg_replace($regex, $separator . '\0', $name)), $separator));
    }
    public static function id2camel($id, $separator = '-')
    {
        return str_replace(' ', '', ucwords(implode(' ', explode($separator, $id))));
    }
}