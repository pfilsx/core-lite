<?php


namespace core;


use core\exceptions\WarningException;

class BaseCore
{
    public static $aliases = [
        '@web' => null,
        '@webroot' => null,
        '@vendor' => null,
        '@app' => null,
        '@crl' => __DIR__
    ];

    public static $app = null;

    public static $classMap = [];

    public static function getAlias($alias){
        if (strncmp($alias, '@', 1)) {
            // not an alias
            return $alias;
        }

        $pos = strpos($alias, '/');
        if ($pos === false){
            $pos = strpos($alias, '\\');
        }
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
            } else {
                foreach (static::$aliases[$root] as $name => $path) {
                    if (strpos($alias . '/', $name . '/') === 0) {
                        return $path . substr($alias, strlen($name));
                    }
                }
            }
        }
        return false;
    }

    public static function setAlias($alias, $path){
        if (strncmp($alias, '@', 1)) {
            $alias = '@' . $alias;
        }
        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
        if ($path !== null) {
            $path = strncmp($path, '@', 1) ? rtrim($path, '\\/') : static::getAlias($path);
            if (!isset(static::$aliases[$root])) {
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [$alias => $path];
                }
            } elseif (is_string(static::$aliases[$root])) {
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [
                        $alias => $path,
                        $root => static::$aliases[$root],
                    ];
                }
            } else {
                static::$aliases[$root][$alias] = $path;
                krsort(static::$aliases[$root]);
            }
        } elseif (isset(static::$aliases[$root])) {
            if (is_array(static::$aliases[$root])) {
                unset(static::$aliases[$root][$alias]);
            } elseif ($pos === false) {
                unset(static::$aliases[$root]);
            }
        }
    }

    public static function getRootAlias($alias)
    {
        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $root;
            } else {
                foreach (static::$aliases[$root] as $name => $path) {
                    if (strpos($alias . '/', $name . '/') === 0) {
                        return $name;
                    }
                }
            }
        }

        return false;
    }

    public static function autoload($className)
    {
        if (array_key_exists($className, static::$classMap)) {
            $classFile = static::$classMap[$className];
            if ($classFile[0] === '@') {
                $classFile = static::getAlias($classFile);
            }
            $classFile = str_replace('/', DIRECTORY_SEPARATOR, $classFile);
        } else if (strpos($className, '\\') !== false) {
            $classFile = static::getAlias('@' . str_replace(['\\', '_'], [DIRECTORY_SEPARATOR, '-'], $className) . '.php');
            if ($classFile === false || !is_file($classFile)) {
                return;
            }
        } else {
            return;
        }
        include $classFile;
        
        if (CRL_DEBUG && !class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {
            throw new \Exception("Unable to find '$className' in file: $classFile. Namespace missing?");
        }
    }



    public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext){
        // error was suppressed with the @-operator
        if (0 === error_reporting()) {
            return false;
        }
        throw new WarningException($errstr, $errno, $errfile, $errline);
    }

}