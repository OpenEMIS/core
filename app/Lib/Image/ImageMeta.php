<?php

App::uses('ImageValidate', 'Image');

/**
 * Created by JetBrains PhpStorm.
 * User: eugenewong
 * Date: 18/1/13
 * Time: 4:14 PM
 * To change this template use File | Settings | File Templates.
 */
class ImageMeta
{
    private $_content = '';
    private $_name = '';
    private $_extension = '';
    private $_mime = '';
    private $_width = 0;
    private $_height = 0;
    private $_x = 0;
    private $_y = 0;
    private $_size = 0;
    private $_fileUploadError = 0;
//    private $_supportMimeType = array();

    public function __construct(Array $img = null) {

        $this->setFileUploadError($img['error']);

        if( !is_null($img) && is_array($img) && isset($img['error']) && $img['error'] < 1 && isset($img['tmp_name']) && !empty($img['tmp_name']) && isset($img['name']) && !empty($img['tmp_name']) ){
//            $finfo = new finfo;
//            echo $fileinfo = $finfo->file($img['tmp_name'], FILEINFO_MIME_TYPE);

            $this->setMime($img['type']);
            if(in_array($this->getMime(), ImageValidate::getSupportMimeType())){
                $size = getimagesize($img['tmp_name']);
                $this->setWidth($size[0]);
                $this->setHeight($size[1]);
                $this->_content = file_get_contents($img['tmp_name']);
            }

            $this->setExtension(pathinfo($img['name'], PATHINFO_EXTENSION));
            $this->setSize($img['size']);

        }

        $this->setName( time()."_{$this->_x}_{$this->_y}_{$this->_width}_{$this->_height}" );
    }

    /***** Static function: Start *****/
    public static function mimeTypes()
    {
        $mime_types = array(
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
        );

        return $mime_types;
    }
    /***** Static function: End *****/

    /***** Getter and Setter: Start *****/
    public function getFilename()
    {
        return $this->getName().'.'.$this->getExtension();
    }

    public function imageExist()
    {
        return ($this->getContent() == '')? false : true;
    }

    public function setContent($content)
    {
        $this->_content = $content;
    }

    public function getContent()
    {
        return $this->_content;
    }

    public function setExtension($extension)
    {
        $this->_extension = $extension;
    }

    public function getExtension()
    {
        return $this->_extension;
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setHeight($height)
    {
        $this->_height = $height;
    }

    public function getHeight()
    {
        return $this->_height;
    }

    public function setMime($mime)
    {
        $this->_mime = $mime;
    }

    public function getMime()
    {
        return $this->_mime;
    }

    public function setWidth($width)
    {
        $this->_width = $width;
    }

    public function getWidth()
    {
        return $this->_width;
    }

    public function setX($x)
    {
        $this->_x = $x;
    }

    public function getX()
    {
        return $this->_x;
    }

    public function setY($y)
    {
        $this->_y = $y;
    }

    public function getY()
    {
        return $this->_y;
    }

    public function setSize($size)
    {
        $this->_size = $size;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function setFileUploadError($fileUploadError)
    {
        $this->_fileUploadError = $fileUploadError;
    }

    public function getFileUploadError()
    {
        return $this->_fileUploadError;
    }

    public function getSupportMimeType()
    {
        return $this->_supportMimeType;
    }

    /***** Getter and Setter: End *****/



}
