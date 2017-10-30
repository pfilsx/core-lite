<?php

namespace core\helpers;


final class FileHelper
{
    const PATTERN_NODIR = 1;
    const PATTERN_ENDSWITH = 4;
    const PATTERN_MUSTBEDIR = 8;
    const PATTERN_NEGATIVE = 16;
    const PATTERN_CASE_INSENSITIVE = 32;

    public static $mimeMagicFile = '@crl/helpers/mimeTypes.php';

    private static $_mimeTypes = [];

    public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR)
    {
        $path = rtrim(strtr($path, '/\\', $ds . $ds), $ds);
        if (strpos($ds . $path, "{$ds}.") === false && strpos($path, "{$ds}{$ds}") === false) {
            return $path;
        }
        // the path may contain ".", ".." or double slashes, need to clean them up
        $parts = [];
        foreach (explode($ds, $path) as $part) {
            if ($part === '..' && !empty($parts) && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part === '.' || $part === '' && !empty($parts)) {
                continue;
            } else {
                $parts[] = $part;
            }
        }
        $path = implode($ds, $parts);
        return $path === '' ? '.' : $path;
    }

    public static function copyDirectory($src, $dst, $options = [])
    {
        $src = static::normalizePath($src);
        $dst = static::normalizePath($dst);
        if ($src === $dst || strpos($dst, $src . DIRECTORY_SEPARATOR) === 0) {
            throw new \Exception('Trying to copy a directory to itself or a subdirectory.');
        }
        $dstExists = is_dir($dst);
        if (!$dstExists && (!isset($options['copyEmptyDirectories']) || $options['copyEmptyDirectories'])) {
            static::createDirectory($dst, isset($options['dirMode']) ? $options['dirMode'] : 0775, true);
            $dstExists = true;
        }
        $handle = opendir($src);
        if ($handle === false) {
            throw new \Exception("Unable to open directory: $src");
        }
        if (!isset($options['basePath'])) {
            // this should be done only once
            $options['basePath'] = realpath($src);
            $options = static::normalizeOptions($options);
        }
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to = $dst . DIRECTORY_SEPARATOR . $file;
            if (static::filterPath($from, $options)) {
                if (isset($options['beforeCopy']) && !call_user_func($options['beforeCopy'], $from, $to)) {
                    continue;
                }
                if (is_file($from)) {
                    if (!$dstExists) {
                        // delay creation of destination directory until the first file is copied to avoid creating empty directories
                        static::createDirectory($dst, isset($options['dirMode']) ? $options['dirMode'] : 0775, true);
                        $dstExists = true;
                    }
                    copy($from, $to);
                    if (isset($options['fileMode'])) {
                        @chmod($to, $options['fileMode']);
                    }
                } else {
                    // recursive copy, defaults to true
                    if (!isset($options['recursive']) || $options['recursive']) {
                        static::copyDirectory($from, $to, $options);
                    }
                }
                if (isset($options['afterCopy'])) {
                    call_user_func($options['afterCopy'], $from, $to);
                }
            }
        }
        closedir($handle);
    }

    public static function removeDirectory($dir, $options = [])
    {
        if (!is_dir($dir)) {
            return;
        }
        if (isset($options['traverseSymlinks']) && $options['traverseSymlinks'] || !is_link($dir)) {
            if (!($handle = opendir($dir))) {
                return;
            }
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    static::removeDirectory($path, $options);
                } else {
                    try {
                        unlink($path);
                    } catch (\Exception $e) {
                        if (DIRECTORY_SEPARATOR === '\\') {
                            // last resort measure for Windows
                            $lines = [];
                            exec("DEL /F/Q \"$path\"", $lines, $deleteError);
                        } else {
                            throw $e;
                        }
                    }
                }
            }
            closedir($handle);
        }
        if (is_link($dir)) {
            unlink($dir);
        } else {
            rmdir($dir);
        }
    }

    public static function findFiles($dir, $options = [])
    {
        if (!is_dir($dir)) {
            throw new \Exception("The dir argument must be a directory: $dir");
        }
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        if (!isset($options['basePath'])) {
            // this should be done only once
            $options['basePath'] = realpath($dir);
            $options = static::normalizeOptions($options);
        }
        $list = [];
        $handle = opendir($dir);
        if ($handle === false) {
            throw new \Exception("Unable to open directory: $dir");
        }
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (static::filterPath($path, $options)) {
                if (is_file($path)) {
                    $list[] = $path;
                } elseif (is_dir($path) && (!isset($options['recursive']) || $options['recursive'])) {
                    $list = array_merge($list, static::findFiles($path, $options));
                }
            }
        }
        closedir($handle);
        return $list;
    }

    public static function filterPath($path, $options)
    {
        if (isset($options['filter'])) {
            $result = call_user_func($options['filter'], $path);
            if (is_bool($result)) {
                return $result;
            }
        }
        if (empty($options['except']) && empty($options['only'])) {
            return true;
        }
        $path = str_replace('\\', '/', $path);
        if (!empty($options['except'])) {
            if (($except = self::lastExcludeMatchingFromList($options['basePath'], $path, $options['except'])) !== null) {
                return $except['flags'] & self::PATTERN_NEGATIVE;
            }
        }
        if (!empty($options['only']) && !is_dir($path)) {
            if (($except = self::lastExcludeMatchingFromList($options['basePath'], $path, $options['only'])) !== null) {
                // don't check PATTERN_NEGATIVE since those entries are not prefixed with !
                return true;
            }
            return false;
        }
        return true;
    }

    public static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        // recurse if parent dir does not exist and we are not at the root of the file system.
        if ($recursive && !is_dir($parentDir) && $parentDir !== $path) {
            static::createDirectory($parentDir, $mode, true);
        }
        try {
            if (!mkdir($path, $mode)) {
                return false;
            }
        } catch (\Exception $e) {
            if (!is_dir($path)) {// https://github.com/yiisoft/yii2/issues/9288
                throw new \Exception("Failed to create directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
            }
        }
        try {
            return chmod($path, $mode);
        } catch (\Exception $e) {
            throw new \Exception("Failed to change permissions for directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public static function getMimeType($file, $magicFile = null, $checkExtension = true)
    {
        if ($magicFile !== null) {
            $magicFile = \Core::getAlias($magicFile);
        }
        if (!extension_loaded('fileinfo')) {
            if ($checkExtension) {
                return static::getMimeTypeByExtension($file, $magicFile);
            }
            throw new \Exception('The fileinfo PHP extension is not installed.');
        }
        $info = finfo_open(FILEINFO_MIME_TYPE, $magicFile);
        if ($info) {
            $result = finfo_file($info, $file);
            finfo_close($info);
            if ($result !== false) {
                return $result;
            }
        }
        return $checkExtension ? static::getMimeTypeByExtension($file, $magicFile) : null;
    }

    public static function getExtensionsByMimeType($mimeType, $magicFile = null)
    {
        $mimeTypes = static::loadMimeTypes($magicFile);
        return array_keys($mimeTypes, mb_strtolower($mimeType, 'UTF-8'), true);
    }


    public static function getMimeTypeByExtension($file, $magicFile = null)
    {
        $mimeTypes = static::loadMimeTypes($magicFile);
        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);
            if (isset($mimeTypes[$ext])) {
                return $mimeTypes[$ext];
            }
        }
        return null;
    }

    protected static function loadMimeTypes($magicFile)
    {
        if ($magicFile === null) {
            $magicFile = static::$mimeMagicFile;
        }
        $magicFile = \Core::getAlias($magicFile);
        if (!isset(self::$_mimeTypes[$magicFile])) {
            self::$_mimeTypes[$magicFile] = require $magicFile;
        }
        return self::$_mimeTypes[$magicFile];
    }

    private static function matchBasename($baseName, $pattern, $firstWildcard, $flags)
    {
        if ($firstWildcard === false) {
            if ($pattern === $baseName) {
                return true;
            }
        } elseif ($flags & self::PATTERN_ENDSWITH) {
            /* "*literal" matching against "fooliteral" */
            $n = StringHelper::byteLength($pattern);
            if (StringHelper::byteSubstr($pattern, 1, $n) === StringHelper::byteSubstr($baseName, -$n, $n)) {
                return true;
            }
        }
        $fnmatchFlags = 0;
        if ($flags & self::PATTERN_CASE_INSENSITIVE) {
            $fnmatchFlags |= FNM_CASEFOLD;
        }
        return fnmatch($pattern, $baseName, $fnmatchFlags);
    }

    private static function matchPathname($path, $basePath, $pattern, $firstWildcard, $flags)
    {
        // match with FNM_PATHNAME; the pattern has base implicitly in front of it.
        if (isset($pattern[0]) && $pattern[0] === '/') {
            $pattern = StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern));
            if ($firstWildcard !== false && $firstWildcard !== 0) {
                $firstWildcard--;
            }
        }
        $namelen = StringHelper::byteLength($path) - (empty($basePath) ? 0 : StringHelper::byteLength($basePath) + 1);
        $name = StringHelper::byteSubstr($path, -$namelen, $namelen);
        if ($firstWildcard !== 0) {
            if ($firstWildcard === false) {
                $firstWildcard = StringHelper::byteLength($pattern);
            }
            // if the non-wildcard part is longer than the remaining pathname, surely it cannot match.
            if ($firstWildcard > $namelen) {
                return false;
            }
            if (strncmp($pattern, $name, $firstWildcard)) {
                return false;
            }
            $pattern = StringHelper::byteSubstr($pattern, $firstWildcard, StringHelper::byteLength($pattern));
            $name = StringHelper::byteSubstr($name, $firstWildcard, $namelen);
            // If the whole pattern did not have a wildcard, then our prefix match is all we need; we do not need to call fnmatch at all.
            if (empty($pattern) && empty($name)) {
                return true;
            }
        }
        $fnmatchFlags = FNM_PATHNAME;
        if ($flags & self::PATTERN_CASE_INSENSITIVE) {
            $fnmatchFlags |= FNM_CASEFOLD;
        }
        return fnmatch($pattern, $name, $fnmatchFlags);
    }

    private static function lastExcludeMatchingFromList($basePath, $path, $excludes)
    {
        foreach (array_reverse($excludes) as $exclude) {
            if (is_string($exclude)) {
                $exclude = self::parseExcludePattern($exclude, false);
            }
            if (!isset($exclude['pattern']) || !isset($exclude['flags']) || !isset($exclude['firstWildcard'])) {
                throw new \Exception('If exclude/include pattern is an array it must contain the pattern, flags and firstWildcard keys.');
            }
            if ($exclude['flags'] & self::PATTERN_MUSTBEDIR && !is_dir($path)) {
                continue;
            }
            if ($exclude['flags'] & self::PATTERN_NODIR) {
                if (self::matchBasename(basename($path), $exclude['pattern'], $exclude['firstWildcard'], $exclude['flags'])) {
                    return $exclude;
                }
                continue;
            }
            if (self::matchPathname($path, $basePath, $exclude['pattern'], $exclude['firstWildcard'], $exclude['flags'])) {
                return $exclude;
            }
        }
        return null;
    }

    private static function parseExcludePattern($pattern, $caseSensitive)
    {
        if (!is_string($pattern)) {
            throw new \Exception('Exclude/include pattern must be a string.');
        }
        $result = [
            'pattern' => $pattern,
            'flags' => 0,
            'firstWildcard' => false,
        ];
        if (!$caseSensitive) {
            $result['flags'] |= self::PATTERN_CASE_INSENSITIVE;
        }
        if (!isset($pattern[0])) {
            return $result;
        }
        if ($pattern[0] === '!') {
            $result['flags'] |= self::PATTERN_NEGATIVE;
            $pattern = StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern));
        }
        if (StringHelper::byteLength($pattern) && StringHelper::byteSubstr($pattern, -1, 1) === '/') {
            $pattern = StringHelper::byteSubstr($pattern, 0, -1);
            $result['flags'] |= self::PATTERN_MUSTBEDIR;
        }
        if (strpos($pattern, '/') === false) {
            $result['flags'] |= self::PATTERN_NODIR;
        }
        $result['firstWildcard'] = self::firstWildcardInPattern($pattern);
        if ($pattern[0] === '*' && self::firstWildcardInPattern(StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern))) === false) {
            $result['flags'] |= self::PATTERN_ENDSWITH;
        }
        $result['pattern'] = $pattern;
        return $result;
    }

    private static function firstWildcardInPattern($pattern)
    {
        $wildcards = ['*', '?', '[', '\\'];
        $wildcardSearch = function ($r, $c) use ($pattern) {
            $p = strpos($pattern, $c);
            return $r === false ? $p : ($p === false ? $r : min($r, $p));
        };
        return array_reduce($wildcards, $wildcardSearch, false);
    }

    protected static function normalizeOptions(array $options)
    {
        if (!array_key_exists('caseSensitive', $options)) {
            $options['caseSensitive'] = true;
        }
        if (isset($options['except'])) {
            foreach ($options['except'] as $key => $value) {
                if (is_string($value)) {
                    $options['except'][$key] = self::parseExcludePattern($value, $options['caseSensitive']);
                }
            }
        }
        if (isset($options['only'])) {
            foreach ($options['only'] as $key => $value) {
                if (is_string($value)) {
                    $options['only'][$key] = self::parseExcludePattern($value, $options['caseSensitive']);
                }
            }
        }
        return $options;
    }
}