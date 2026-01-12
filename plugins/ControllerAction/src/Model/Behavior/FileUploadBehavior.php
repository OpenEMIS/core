<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

/**
 * Depends on @link[ControllerActionComponent] events heavily.
 */
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;

class FileUploadBehavior extends Behavior
{
    protected $_defaultConfig = [
        'name' => 'file_name',
        'content' => 'file_content',
        'size' => '1MB',
        'contentEditable' => true,
        'allowable_file_types' => 'all'
    ];

    public $fileImagesMap = [
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png'
    ];

    public $fileDocumentsMap = [
        'rtf' => 'text/rtf',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'pdf' => 'application/pdf',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip' => 'application/zip'
    ];
    //for both word and pdf files(POCOR-7758)
    public $fileDocPdfMap = [
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'pdf' => 'application/pdf',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

// Combine both image and document extensions into a single array
    public $fileSignatureMap = [
        'jpeg' => "\xFF\xD8\xFF",    // JPEG
        'jpg' => "\xFF\xD8\xFF",     // JPEG
        'gif' => "GIF",              // GIF
        'png' => "\x89\x50\x4E\x47", // PNG
        'rtf' => "{\\rtf",           // RTF
        'txt' => null,               // Plain text (no specific signature)
        'csv' => null,               // CSV (no specific signature)
        'pdf' => "%PDF-",            // PDF
        'ppt' => "\xD0\xCF\x11\xE0", // PPT (Magic number)
        'pptx' => "PK\x03\x04",      // PPTX (Magic number)
        'doc' => "\xD0\xCF\x11\xE0", // DOC (Magic number)
        'docx' => "PK\x03\x04",      // DOCX (Magic number)
        'xls' => "\xD0\xCF\x11\xE0", // XLS (Magic number)
        'xlsx' => "PK\x03\x04",      // XLSX (Magic number)
        'zip' => "PK\x03\x04"        // ZIP (Magic number)
    ];

    public $fileTypesMap = [];
    private $allowableFileTypes = [];

    private $_validator;

    public function initialize(array $config): void
    {
        $this->setConfig(array_merge($this->_defaultConfig, $config));
        $this->fileTypesMap = array_merge($this->fileImagesMap, $this->fileDocumentsMap);

        if ($this->getConfig('allowable_file_types')=='image') {
            $this->allowableFileTypes = $this->fileImagesMap;
        } else if ($this->getConfig('allowable_file_types')=='document') {
            $this->allowableFileTypes = $this->fileDocumentsMap;
        } else if ($this->getConfig('allowable_file_types') == 'doc/pdf'){//POCOR-7758
            $this->allowableFileTypes = $this->fileDocPdfMap;
        }
        else {
            $this->allowableFileTypes = $this->fileTypesMap;
        }
    }


/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'ControllerAction.Model.addEdit.beforePatch' => 'addEditBeforePatch',
            'ControllerAction.Model.edit.beforePatch' => 'editBeforePatch',
            'ControllerAction.Model.edit.beforeAction' => 'editBeforeAction',
            'ControllerAction.Model.afterAction' => 'afterAction',
            'ControllerAction.Model.add.onInitialize' => 'addOnInitialize',
            'ControllerAction.Model.edit.onInitialize' => 'editOnInitialize',
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }


/******************************************************************************************************************
**
** ControllerActionComponent events
**
******************************************************************************************************************/
    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        $model = $this->_table;
        $request = $model->request;
        $session = $model->request->getSession();
        $session->delete($model->getRegistryAlias().'.parseUpload');
    }

    public function editOnInitialize(EventInterface $event, Entity $entity)
    {
        $model = $this->_table;
        $session = $model->request->getSession();
        $session->delete($model->getRegistryAlias().'.parseUpload');
    }

    public function afterAction(EventInterface $event)
    {
        if (isset($this->_table->fields[$this->getConfig('content')])) {
            $comment = '* ' . sprintf(__('File size should not be larger than %s.'), $this->getConfig('size'));
            $comment .= '<br/>* ' . sprintf(__('Format Supported: %s'), $this->fileTypesForView());
            $this->_table->fields[$this->getConfig('content')]['comment'] = $comment ;
        }
    }

    public function editBeforeAction(EventInterface $event)
    {
        if (!$this->getConfig('contentEditable')) {
            unset($this->_table->fields[$this->getConfig('content')]);
        }
    }

    public function editBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (!$this->getConfig('contentEditable')) {
            if (isset($data[$this->_table->aliasField($this->getConfig('content'))])) {
                unset($data[$this->_table->aliasField($this->getConfig('content'))]);
            }
        }
    }

    /**
     * @todo if user wants the file or image to be removed, it should be emptied from the record.
     */
    public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $fileNameField = $this->getConfig('name');
        $fileContentField = $this->getConfig('content');
        $contentEditable = $this->getConfig('contentEditable');
        $fileContentFieldRules = $this->_table->getValidator()->field($fileContentField);
        $model = $this->_table;
        $session = $model->request->getSession();
        $file = isset($data[$model->getAlias()][$fileContentField]) ? $data[$model->getAlias()][$fileContentField] : [];
        if ($entity->isNew()) {
            if (!empty($file) && $file->getError() == 0) { // success
                if ($this->uploadedFileIsAllowed($file)) {
                    if ($this->uploadedFileSizeIsAcceptable($file)) {
                        $parseUploadData = $this->parseUpload($file);
                        $session->write($model->getRegistryAlias().'.parseUpload', $parseUploadData);
                        $data = $this->parseUploadInput($data, $parseUploadData);
                    } else {
                        $entity->errors($fileContentField, [sprintf(__('File size should not be larger than %s.'), $this->getConfig('size'))]);
                        unset($data[$model->getAlias()][$fileContentField]);
                    }
                } else {
                    $entity->getErrors($fileContentField, ['Only the following formats are allowed: ' . $this->fileTypesForView() . ', check the type of the file please.']);
                    unset($data[$model->getAlias()][$fileContentField]);
                }
            } elseif ($fileContentFieldRules->isEmptyAllowed() && !empty($file)) {
                if ($session->check($model->getRegistryAlias().'.parseUpload')) {
                    $parseUploadData = $session->read($model->getRegistryAlias().'.parseUpload');
                    $data = $this->parseUploadInput($data, $parseUploadData);
                } else {
                    if (isset($data[$model->getAlias()][$fileNameField])) {
                        unset($data[$model->getAlias()][$fileNameField]);
                    }
                    if (isset($data[$model->getAlias()][$fileContentField])) {
                        unset($data[$model->getAlias()][$fileContentField]);
                    }
                }
            } elseif ($fileContentFieldRules->isEmptyAllowed()) {
                $session->delete($model->getRegistryAlias().'.parseUpload');
                $this->unsetProperties($entity, $data);
            } else {
                // pr('should throw an error here');
                $session->delete($model->getRegistryAlias().'.parseUpload');
                $entity->getErrors($fileContentField, ['File attachment is required']);
                unset($data[$model->getAlias()][$fileContentField]);
            }
        } else {
            if ($contentEditable) {
                if (!empty($file) && $file->getError() == 0) { // success
                    // pr('parseUploadInput');
                    if ($this->uploadedFileIsAllowed($file)) {
                        if ($this->uploadedFileSizeIsAcceptable($file)) {
                            $parseUploadData = $this->parseUpload($file);
                            $session->write($model->getRegistryAlias().'.parseUpload', $parseUploadData);
                            $data = $this->parseUploadInput($data, $parseUploadData);
                        } else {
                            $entity->getErrors($fileContentField, [sprintf(__('File size should not be larger than '), $this->config('size'))]);
                            unset($data[$model->getAlias()][$fileContentField]);
                        }
                    } else {
                        $entity->getErrors($fileContentField, ['Only the following formats are allowed: ' . $this->fileTypesForView()]);
                        unset($data[$model->getAlias()][$fileContentField]);
                    }
                } elseif ($fileContentFieldRules->isEmptyAllowed() && !empty($file)) {
                    // pr('content allowed to be empty');
                    // $this->unsetProperties($entity, $data);
                    if ($session->check($model->getRegistryAlias().'.parseUpload')) {
                        $parseUploadData = $session->read($model->getRegistryAlias().'.parseUpload');
                        $data = $this->parseUploadInput($data, $parseUploadData);
                    } else {
                        if (isset($data[$model->getAlias()][$fileNameField])) {
                            unset($data[$model->getAlias()][$fileNameField]);
                        }
                        if (isset($data[$model->getAlias()][$fileContentField])) {
                            unset($data[$model->getAlias()][$fileContentField]);
                        }
                    }
                } elseif ($fileContentFieldRules->isEmptyAllowed()) {
                    /**
                     * columns set as nullable in db
                     */
                    $parseUploadData = $this->parseUpload();
                    $session->write($model->getRegistryAlias().'.parseUpload', $parseUploadData);
                    $data = $this->parseUploadInput($data, $parseUploadData);
                } else {
                    /**
                     * columns set as NOT nullable in db
                     */
                    // die('dun know what to do yet');
                }
            } else {
                // pr('content not editable');
                $this->unsetProperties($entity, $data);
            }
        }

        // die();
    }

/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
    private function unsetProperties($entity, $data)
    {
        /**
         * unset this two entities so that no changes will be made on the uploaded record
         */

        $model = $this->_table;
        $fileNameField = $this->getConfig('name');
        $fileContentField = $this->getConfig('content');

        if (isset($data[$model->getAlias()][$fileNameField])) {
            unset($data[$model->getAlias()][$fileNameField]);
        }
        if (isset($data[$model->getAlias()][$fileContentField])) {
            unset($data[$model->getAlias()][$fileContentField]);
        }
        $entity->unsetProperty($fileNameField);
        $entity->unsetProperty($fileContentField);
    }

    /**
     * http://codereview.stackexchange.com/questions/6476/quick-way-to-convert-bytes-to-a-more-readable-format
     * @param  [type] $bytes [description]
     * @return [type]        [description]
     */
    private function bytesToReadableFormat($bytes)
    {
        if ($bytes < $KILO) {
            return $bytes . 'B';
        }
        if ($bytes < $MEGA) {
            return round($bytes / $KILO, 2) . 'KB';
        }
        if ($bytes < $GIGA) {
            return round($bytes / $MEGA, 2) . 'MB';
        }
        if ($bytes < $TERA) {
            return round($bytes / $GIGA, 2) . 'GB';
        }
        return round($bytes / $TERA, 2) . 'TB';
    }

    private function readableFormatToBytes()
    {
        $KILO = 1024;
        $MEGA = $KILO * 1024;
        $GIGA = $MEGA * 1024;
        $TERA = $GIGA * 1024;

        if (substr_count(strtolower($this->getConfig('size')), 'kb')) {
            $size = intval(str_replace('kb', '', (strtolower($this->getConfig('size')))));
            return $size * $KILO;
        }
        if (substr_count(strtolower($this->getConfig('size')), 'mb')) {
            $size = intval(str_replace('mb', '', (strtolower($this->getConfig('size')))));
            return $size * $MEGA;
        }
        if (substr_count(strtolower($this->getConfig('size')), 'gb')) {
            $size = intval(str_replace('gb', '', (strtolower($this->getConfig('size')))));
            return $size * $GIGA;
        }
        $size = intval($this->getConfig('size'));
        return $size * $TERA;
    }
    //POCOR-7485 comment because of not supported in cakephp 3
    /*public function uploadedFileIsAllowed($file)
    {
        //        $this->_table->log(__FUNCTION__, 'debug');
        //        $this->_table->log($file, 'debug');
        if($file->getClientMediaType() !== null){
            return false;
        }
        if (($file->getClientMediaType() !==null) && !in_array($file->getClientMediaType(), $this->allowableFileTypes)) {
            return false;
        }

        $pathInfo = pathinfo($file['name']);
        $fileExtension = strtolower($pathInfo['extension']);
        
        if (isset($this->allowableFileTypes[$fileExtension]) && $this->fileSignatureMap[$fileExtension]) {
            $expectedSignature = $this->fileSignatureMap[$fileExtension];
            $fileContent = file_get_contents($file['tmp_name'], false, null, 0, strlen($expectedSignature));
            if ($fileContent === $expectedSignature) {
                return true;
            }
        }
        return false;

    }*/
    //POCOR-7485 comment because of supported in cakephp 4
    public function uploadedFileIsAllowed($file) {
        $isValid = true;
    
        $fileType = $file->getClientMediaType();
        if(isset($fileType) && !in_array($fileType, $this->allowableFileTypes)){
            $isValid = false;
        } 
        return $isValid;
    }

    public function uploadedFileSizeIsAcceptable($file)
    {
        $isValid = true;
        $restrictedSize = $this->readableFormatToBytes();

        // pr($file['size'] .' <> '. $restrictedSize);die;
        $fileType = $file->getClientMediaType();//POCOR-7485
        $fileSize = $file->getSize();//POCOR-7485
        //if (isset($file['type']) && ($file['size'] > $restrictedSize)) {//POCOR-7485 comment because of not supported in cakephp 3
        if (isset($fileType) && ($fileSize > $restrictedSize)) {
            $isValid = false;
        }
        return $isValid;
    }

    private function parseUpload($file = null)
    {
        if (!is_null($file)) {
            $uploadedFileName = $file->getClientFilename();//POCOR-7485
            $uploadedFileTmpName = $file->getStream()->getMetadata('uri');//POCOR-7485
            if ($this->getConfig('useDefaultName')) {
                $fileName = $uploadedFileName;//POCOR-7485
            } else {
                //$pathInfo = pathinfo($file['name']);//POCOR-7485 comment because of not supported in cakephp 3
                $pathInfo = pathinfo($uploadedFileName);
                $fileName = uniqid() . '.' . $pathInfo['extension'];
            }
            //$fileContent = file_get_contents($file['tmp_name']);//POCOR-7485 comment because of not supported in cakephp 3
            $fileContent = file_get_contents($uploadedFileTmpName);
        } else {
            $fileName = null;
            $fileContent = null;
        }
        return ['fileName' => $fileName, 'fileContent' => $fileContent];
    }

    private function parseUploadInput($data, $nameContentArray)
    {
        $fileNameField = $this->getConfig('name');
        $fileContentField = $this->getConfig('content');
        $model = $this->_table;
        $data[$model->getAlias()][$fileNameField] = $nameContentArray['fileName'];
        $data[$model->getAlias()][$fileContentField] = $nameContentArray['fileContent'];
        return $data;
    }

    public function fileTypesForView()
    {
        return implode(', .', array_keys($this->allowableFileTypes));
    }

    public function getFileTypeForView($filename)
    {
        $exp = explode('.', $filename);
        $ext = $exp[count($exp) - 1];
        if (array_key_exists($ext, $this->fileImagesMap)) {
            return 'Image';
        } elseif (array_key_exists($ext, $this->fileDocumentsMap)) {
            return 'Document';
        } else {
            return 'Unknown';
        }
    }

    public function getFileType($ext)
    {
        if (array_key_exists($ext, $this->fileTypesMap)) {
            return $this->fileTypesMap[$ext];
        } else {
            return false;
        }
    }

    /**
     * Cake V3 returns binary column type data as php resource id instead of the whole file for better performance.
     * The current work-around is to use native php normal stream functions to read the contents incrementally or all at once.
     * @link https://groups.google.com/forum/#!topic/cake-php/rgaHYh2iWwU
     *
     * @param  php_resource_file_handler $phpResourceFile acquired from table entity
     * @return binary/boolean                             returns the binary file if resource exists and returns boolean false if not exists
     */
    public function getActualFile($phpResourceFile)
    {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
}
