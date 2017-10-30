<?php


namespace core\helpers;


class Json
{
    public static $jsonErrorMessages = [
        'JSON_ERROR_DEPTH' => 'The maximum stack depth has been exceeded.',
        'JSON_ERROR_STATE_MISMATCH' => 'Invalid or malformed JSON.',
        'JSON_ERROR_CTRL_CHAR' => 'Control character error, possibly incorrectly encoded.',
        'JSON_ERROR_SYNTAX' => 'Syntax error.',
        'JSON_ERROR_UTF8' => 'Malformed UTF-8 characters, possibly incorrectly encoded.', // PHP 5.3.3
        'JSON_ERROR_RECURSION' => 'One or more recursive references in the value to be encoded.', // PHP 5.5.0
        'JSON_ERROR_INF_OR_NAN' => 'One or more NAN or INF values in the value to be encoded', // PHP 5.5.0
        'JSON_ERROR_UNSUPPORTED_TYPE' => 'A value of a type that cannot be encoded was given', // PHP 5.5.0
    ];

    public static function encode($value, $options = 320)
    {
        $expressions = [];
        $value = static::processData($value, $expressions, uniqid('', true));
        set_error_handler(function () {
            static::handleJsonError(JSON_ERROR_SYNTAX);
        }, E_WARNING);
        $json = json_encode($value, $options);
        restore_error_handler();
        static::handleJsonError(json_last_error());
        return $expressions === [] ? $json : strtr($json, $expressions);
    }

    public static function decode($json, $asArray = true)
    {
        if (is_array($json)) {
            throw new \Exception('Invalid JSON data.');
        } elseif ($json === null || $json === '') {
            return null;
        }
        $decode = json_decode((string) $json, $asArray);
        static::handleJsonError(json_last_error());
        return $decode;
    }

    public static function htmlEncode($value)
    {
        return static::encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

    protected static function handleJsonError($lastError)
    {
        if ($lastError === JSON_ERROR_NONE) {
            return;
        }
        $availableErrors = [];
        foreach (static::$jsonErrorMessages as $const => $message) {
            if (defined($const)) {
                $availableErrors[constant($const)] = $message;
            }
        }
        if (isset($availableErrors[$lastError])) {
            throw new \Exception($availableErrors[$lastError]);
        }
        throw new \Exception('Unknown JSON encoding/decoding error.');
    }

    //TODO remove unused stuff
    protected static function processData($data, &$expressions, $expPrefix)
    {
        if (is_object($data)) {
//            if ($data instanceof JsExpression) {
//                $token = "!{[$expPrefix=" . count($expressions) . ']}!';
//                $expressions['"' . $token . '"'] = $data->expression;
//                return $token;
//            } else
            if ($data instanceof \JsonSerializable) {
                return static::processData($data->jsonSerialize(), $expressions, $expPrefix);
//            } elseif ($data instanceof Arrayable) {
//                $data = $data->toArray();
            } elseif ($data instanceof \SimpleXMLElement) {
                $data = (array) $data;
            } else {
                $result = [];
                foreach ($data as $name => $value) {
                    $result[$name] = $value;
                }
                $data = $result;
            }
            if ($data === []) {
                return new \stdClass();
            }
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = static::processData($value, $expressions, $expPrefix);
                }
            }
        }
        return $data;
    }
}