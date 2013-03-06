<?php
/**
 * Created by JetBrains PhpStorm.
 * User: eugenewong
 * Date: 21/1/13
 * Time: 11:18 AM
 * To change this template use File | Settings | File Templates.
 */
class ImageValidate
{
    private $_width = 0;
    private $_height = 0;
    private $_size = 0;

    public function __construct($height = 514, $width = 400, $size = 204800){
        $this->setHeight($height);
        $this->setWidth($width);
        $this->setSize($size);
    }

    /**** Static functions: Start *****/
    public static function getSupportMimeType(){
        return array(
            image_type_to_mime_type(IMAGETYPE_GIF),
            image_type_to_mime_type(IMAGETYPE_JPEG),
            image_type_to_mime_type(IMAGETYPE_PNG)
        );
    }
    /**** Static functions: End *****/

    public function validateImage(ImageMeta $meta)
    {

        $validate = array(
            'error' => 0,
            'message' => array()
        );

        // Check there are not php file upload error.
        if($meta->getFileUploadError() > 0 AND $meta->getFileUploadError() !== 4)
        {
            $validate['error'] = 1;
            array_push($validate['message'], $this->getValidateFileUploadMessage($meta->getFileUploadError()));
        }else if($meta->getFileUploadError() !== 4){

            // Check the mime type is valid.
            if($this->validateMime($meta->getMime(), $this->getSupportMimeType()) > 0)
            {
                $validate['error'] = 1;
                array_push($validate['message'], 'Format not support.');
            }

            // Check the size is valid
            if($this->validateSize($meta->getSize()) > 0)
            {
                $validate['error'] = 1;
                array_push($validate['message'], 'Image filesize too large.');
            }

            // Check the width and height are valid
            if($this->validateHeight($meta->getHeight()) > 0 OR $this->validateWidth($meta->getWidth()) > 0 )
            {
                $validate['error'] = 1;
                array_push($validate['message'], 'Resolution too large.');
            }

            // Check there is no empty content.

        }

        return $validate;
    }

    public function validateContent($content)
    {
        return ($content == '')? 1 : 0;
    }

    protected function validateHeight($height)
    {

        return ($height > $this->getHeight()) ? 1: 0;
    }

    protected function validateWidth($width)
    {
        return ($width > $this->getWidth()) ? 1 : 0;
    }

    protected function validateSize($size)
    {
        return ($size > $this->getSize()) ? 1 : 0 ;
    }

    protected function validateMime($mime, $supportedMime)
    {
        return (!empty($mime) AND in_array($mime, $supportedMime)) ? 0 : 1;
    }

    protected function getValidateFileUploadMessage($uploadError)
    {
        $message = "File uploaded with success.";
        if($uploadError == UPLOAD_ERR_INI_SIZE)
        {
            $message = "Image exceeds system max filesize.";
        }elseif($uploadError == UPLOAD_ERR_FORM_SIZE)
        {
            $message = "Image exceeds max file size in the HTML form.";
        }elseif($uploadError == UPLOAD_ERR_PARTIAL)
        {
            $message ="Image was only partially uploaded.";
        }elseif($uploadError == UPLOAD_ERR_NO_FILE)
        {
                $message = "No image was uploaded.";
        }elseif($uploadError == UPLOAD_ERR_NO_TMP_DIR)
        {
            $message = "Missing a temporary folder.";
        }elseif($uploadError == UPLOAD_ERR_CANT_WRITE)
        {
            $message = "Failed to write file to disk.";
        }elseif($uploadError == UPLOAD_ERR_EXTENSION)
        {
            $message = "A PHP extension stopped the file upload.";
        }

        return $message;
    }

    /***** Getter and Setter: Start *****/
    public function setHeight($height)
    {
        $this->_height = $height;
    }

    public function getHeight()
    {
        return $this->_height;
    }

    public function setSize($size)
    {
        $this->_size = $size;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function setWidth($width)
    {
        $this->_width = $width;
    }

    public function getWidth()
    {
        return $this->_width;
    }
    /***** Getter and Setter: End *****/

}