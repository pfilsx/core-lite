<?php


namespace core\helpers;


final class StringHelper
{
    public static function byteLength($string)
    {
        return mb_strlen($string, '8bit');
    }

    public static function byteSubstr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length === null ? mb_strlen($string, '8bit') : $length, '8bit');
    }

    public static function basename($path, $suffix = '')
    {
        if (($len = mb_strlen($suffix)) > 0 && mb_substr($path, -$len) === $suffix) {
            $path = mb_substr($path, 0, -$len);
        }
        $path = rtrim(str_replace('\\', '/', $path), '/\\');
        if (($pos = mb_strrpos($path, '/')) !== false) {
            return mb_substr($path, $pos + 1);
        }
        return $path;
    }

    public static function dirname($path)
    {
        $pos = mb_strrpos(str_replace('\\', '/', $path), '/');
        if ($pos !== false) {
            return mb_substr($path, 0, $pos);
        }
        return '';
    }

    public static function countWords($string)
    {
        return count(preg_split('/\s+/u', $string, null, PREG_SPLIT_NO_EMPTY));
    }

    public static function normalizeNumber($value)
    {
        $value = "$value";
        $localeInfo = localeconv();
        $decimalSeparator = isset($localeInfo['decimal_point']) ? $localeInfo['decimal_point'] : null;
        if ($decimalSeparator !== null && $decimalSeparator !== '.') {
            $value = str_replace($decimalSeparator, '.', $value);
        }
        return $value;
    }

    public static function base64UrlEncode($input)
    {
        return strtr(base64_encode($input), '+/', '-_');
    }

    public static function base64UrlDecode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}