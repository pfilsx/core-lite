<?php


namespace core\web;


use core\base\BaseObject;
use core\components\Model;

class UploadedFile extends BaseObject
{
    /**
     * @var string the original name of the file being uploaded
     */
    public $name;
    /**
     * @var string the path of the uploaded file on the server.
     * Note, this is a temporary file which will be automatically deleted by PHP
     * after the current request is processed.
     */
    public $tempName;
    /**
     * @var string the MIME-type of the uploaded file (such as "image/gif").
     * Since this MIME type is not checked on the server-side, do not take this value for granted.
     */
    public $type;
    /**
     * @var int the actual size of the uploaded file in bytes
     */
    public $size;
    /**
     * @var int an error code describing the status of this file uploading.
     * @see http://www.php.net/manual/en/features.file-upload.errors.php
     */
    public $error;
    private static $_files;
    /**
     * String output.
     * This is PHP magic method that returns string representation of an object.
     * The implementation here returns the uploaded file's name.
     * @return string the string representation of the object
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * @param Model $model
     * @param string $attribute
     * @return UploadedFile
     */
    public static function getInstance($model, $attribute)
    {
        $name = $model->getAttributeName($attribute);
        return static::getInstanceByName($name);
    }
    /**
     * @param Model $model
     * @param string $attribute
     * @return UploadedFile[]
     */
    public static function getInstances($model, $attribute)
    {
        $name = $model->getAttributeName($attribute);
        return static::getInstancesByName($name);
    }

    public static function getInstanceByName($name)
    {
        $files = self::loadFiles();
        return isset($files[$name]) ? new static($files[$name]) : null;
    }

    public static function getInstancesByName($name)
    {
        $files = self::loadFiles();
        if (isset($files[$name])) {
            return [new static($files[$name])];
        }
        $results = [];
        foreach ($files as $key => $file) {
            if (strpos($key, "{$name}[") === 0) {
                $results[] = new static($file);
            }
        }
        return $results;
    }

    public function saveAs($file, $deleteTempFile = true)
    {
        if ($this->error == UPLOAD_ERR_OK) {
            if ($deleteTempFile) {
                return move_uploaded_file($this->tempName, $file);
            } elseif (is_uploaded_file($this->tempName)) {
                return copy($this->tempName, $file);
            }
        }
        return false;
    }

    /**
     * @return string original file base name
     */
    public function getBaseName()
    {
        $pathInfo = pathinfo('_' . $this->name, PATHINFO_FILENAME);
        return mb_substr($pathInfo, 1, mb_strlen($pathInfo, '8bit'), '8bit');
    }

    /**
     * @return string file extension
     */
    public function getExtension()
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }
    /**
     * @return bool whether there is an error with the uploaded file.
     * Check [[error]] for detailed error code information.
     */
    public function getHasError()
    {
        return $this->error != UPLOAD_ERR_OK;
    }

    /**
     * Creates UploadedFile instances from $_FILE.
     * @return array the UploadedFile instances
     */
    private static function loadFiles()
    {
        if (self::$_files === null) {
            self::$_files = [];
            if (isset($_FILES) && is_array($_FILES)) {
                foreach ($_FILES as $class => $info) {
                    self::loadFilesRecursive($class, $info['name'], $info['tmp_name'], $info['type'], $info['size'], $info['error']);
                }
            }
        }
        return self::$_files;
    }

    /**
     * Creates UploadedFile instances from $_FILE recursively.
     * @param string $key key for identifying uploaded file: class name and sub-array indexes
     * @param mixed $names file names provided by PHP
     * @param mixed $tempNames temporary file names provided by PHP
     * @param mixed $types file types provided by PHP
     * @param mixed $sizes file sizes provided by PHP
     * @param mixed $errors uploading issues provided by PHP
     */
    private static function loadFilesRecursive($key, $names, $tempNames, $types, $sizes, $errors)
    {
        if (is_array($names)) {
            foreach ($names as $i => $name) {
                self::loadFilesRecursive($key . '[' . $i . ']', $name, $tempNames[$i], $types[$i], $sizes[$i], $errors[$i]);
            }
        } elseif ((int) $errors !== UPLOAD_ERR_NO_FILE) {
            self::$_files[$key] = [
                'name' => $names,
                'tempName' => $tempNames,
                'type' => $types,
                'size' => $sizes,
                'error' => $errors,
            ];
        }
    }
}