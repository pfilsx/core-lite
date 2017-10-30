<?php


namespace core\helpers;


class ImageManager
{
    private $_image;
    private $_imageType;

    public function load($filename)
    {
        $image_info = getimagesize($filename);
        $this->_imageType = $image_info[2];
        if ($this->_imageType == IMAGETYPE_JPEG) {
            $this->_image = imagecreatefromjpeg($filename);
        } elseif ($this->_imageType == IMAGETYPE_GIF) {
            $this->_image = imagecreatefromgif($filename);
        } elseif ($this->_imageType == IMAGETYPE_PNG) {
            $this->_image = imagecreatefrompng($filename);
        }
    }

    public function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null)
    {
        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->_image, $filename, $compression);
        } elseif ($image_type == IMAGETYPE_GIF) {
            imagegif($this->_image, $filename);
        } elseif ($image_type == IMAGETYPE_PNG) {
            imagepng($this->_image, $filename);
        }
        if ($permissions != null) {
            chmod($filename, $permissions);
        }
    }

    public function output($image_type = IMAGETYPE_JPEG)
    {
        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->_image);
        } elseif ($image_type == IMAGETYPE_GIF) {
            imagegif($this->_image);
        } elseif ($image_type == IMAGETYPE_PNG) {
            imagepng($this->_image);
        }
    }

    public function getWidth()
    {
        return imagesx($this->_image);
    }

    public function getHeight()
    {
        return imagesy($this->_image);
    }

    public function resizeToHeight($height)
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
    }

    public function resizeToWidth($width)
    {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width, $height);
    }

    public function scale($scale)
    {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;
        $this->resize($width, $height);
    }

    public function resize($width, $height)
    {
        $new_image = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_image, $this->_image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->_image = $new_image;
    }
}