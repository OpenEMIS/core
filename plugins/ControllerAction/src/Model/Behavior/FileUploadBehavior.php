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
use Cake\Event\Event;
use Cake\Validation\Validator;

class FileUploadBehavior extends Behavior
{
    protected $_defaultConfig = [
        'name' => 'file_name',
        'content' => 'file_content',
        'size' => '1MB',
        'contentEditable' => true,
        'allowable_file_types' => 'all'
    ];

    public $fileImagesMap = array(
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'gif'   => 'image/gif',
        'png'   => 'image/png'
        // 'jpeg'=>'image/pjpeg',
        // 'jpeg'=>'image/x-png'
    );

    public $fileDocumentsMap = array(
        'rtf'   => 'text/rtf',
        'txt'   => 'text/plain',
        'csv'   => 'text/csv',
        'pdf'   => 'application/pdf',
        'ppt'   => 'application/vnd.ms-powerpoint',
        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'doc'   => 'application/msword',
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'   => 'application/vnd.ms-excel',
        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip'   => 'application/zip',
    );

    public $fileTypesMap = [];
    private $allowableFileTypes = [];

    private $_validator;

    public function initialize(array $config)
    {
        $this->config(array_merge($this->_defaultConfig, $config));
        $this->fileTypesMap = array_merge($this->fileImagesMap, $this->fileDocumentsMap);

        if ($this->config('allowable_file_types')=='image') {
            $this->allowableFileTypes = $this->fileImagesMap;
        } else if ($this->config('allowable_file_types')=='document') {
            $this->allowableFileTypes = $this->fileDocumentsMap;
        } else {
            $this->allowableFileTypes = $this->fileTypesMap;
        }
    }


/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
    public function implementedEvents()
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
    public function addOnInitialize(Event $event, Entity $entity)
    {
        $model = $this->_table;
        $session = $model->request->session();
        $session->delete($model->registryAlias().'.parseUpload');
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $model = $this->_table;
        $session = $model->request->session();
        $session->delete($model->registryAlias().'.parseUpload');
    }

    public function afterAction(Event $event)
    {
        if (isset($this->_table->fields[$this->config('content')])) {
            // pr();
            $comment = '* ' . sprintf(__('File size should not be larger than %s.'), $this->config('size'));
            $comment .= '<br/>* ' . sprintf(__('Format Supported: %s'), $this->fileTypesForView());
            $this->_table->fields[$this->config('content')]['comment'] = $comment ;
        }
    }

    public function editBeforeAction(Event $event)
    {
        if (!$this->config('contentEditable')) {
            unset($this->_table->fields[$this->config('content')]);
        }
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (!$this->config('contentEditable')) {
            if (isset($data[$this->_table->aliasField($this->config('content'))])) {
                unset($data[$this->_table->aliasField($this->config('content'))]);
            }
        }
    }

    /**
     * @todo if user wants the file or image to be removed, it should be emptied from the record.
     */
    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {

        $fileNameField = $this->config('name');
        $fileContentField = $this->config('content');
        $contentEditable = $this->config('contentEditable');
        $fileContentFieldRules = $this->_table->validator()->field($fileContentField);
        $model = $this->_table;
        $session = $model->request->session();
        $file = isset($data[$model->alias()][$fileContentField]) ? $data[$model->alias()][$fileContentField] : [];

        if ($entity->isNew()) {
            if (!empty($file) && $file['error'] == 0) { // success

                if ($this->uploadedFileIsAllowed($file)) {
                    if ($this->uploadedFileSizeIsAcceptable($file)) {
                        $parseUploadData = $this->parseUpload($file);
                        $session->write($model->registryAlias().'.parseUpload', $parseUploadData);
                        $data = $this->parseUploadInput($data, $parseUploadData);
                    } else {
                        $entity->errors($fileContentField, [sprintf(__('File size should not be larger than %s.'), $this->config('size'))]);
                        unset($data[$model->alias()][$fileContentField]);
                    }
                } else {
                    $entity->errors($fileContentField, ['Only the following formats are allowed: ' . $this->fileTypesForView()]);
                    unset($data[$model->alias()][$fileContentField]);
                }
            } elseif ($fileContentFieldRules->isEmptyAllowed() && !empty($file)) {
                if ($session->check($model->registryAlias().'.parseUpload')) {
                    $parseUploadData = $session->read($model->registryAlias().'.parseUpload');
                    $data = $this->parseUploadInput($data, $parseUploadData);
                } else {
                    if (isset($data[$model->alias()][$fileNameField])) {
                        unset($data[$model->alias()][$fileNameField]);
                    }
                    if (isset($data[$model->alias()][$fileContentField])) {
                        unset($data[$model->alias()][$fileContentField]);
                    }
                }
            } elseif ($fileContentFieldRules->isEmptyAllowed()) {
                $session->delete($model->registryAlias().'.parseUpload');
                $this->unsetProperties($entity, $data);
            } else {
                // pr('should throw an error here');
                $session->delete($model->registryAlias().'.parseUpload');
                $entity->errors($fileContentField, ['File attachment is required']);
                unset($data[$model->alias()][$fileContentField]);
            }
        } else {
            if ($contentEditable) {
                if (!empty($file) && $file['error'] == 0) { // success
                    // pr('parseUploadInput');
                    if ($this->uploadedFileIsAllowed($file)) {
                        if ($this->uploadedFileSizeIsAcceptable($file)) {
                            $parseUploadData = $this->parseUpload($file);
                            $session->write($model->registryAlias().'.parseUpload', $parseUploadData);
                            $data = $this->parseUploadInput($data, $parseUploadData);
                        } else {
                            $entity->errors($fileContentField, [sprintf(__('File size should not be larger than '), $this->config('size'))]);
                            unset($data[$model->alias()][$fileContentField]);
                        }
                    } else {
                        $entity->errors($fileContentField, ['Only the following formats are allowed: ' . $this->fileTypesForView()]);
                        unset($data[$model->alias()][$fileContentField]);
                    }
                } elseif ($fileContentFieldRules->isEmptyAllowed() && !empty($file)) {
                    // pr('content allowed to be empty');
                    // $this->unsetProperties($entity, $data);
                    if ($session->check($model->registryAlias().'.parseUpload')) {
                        $parseUploadData = $session->read($model->registryAlias().'.parseUpload');
                        $data = $this->parseUploadInput($data, $parseUploadData);
                    } else {
                        if (isset($data[$model->alias()][$fileNameField])) {
                            unset($data[$model->alias()][$fileNameField]);
                        }
                        if (isset($data[$model->alias()][$fileContentField])) {
                            unset($data[$model->alias()][$fileContentField]);
                        }
                    }
                } elseif ($fileContentFieldRules->isEmptyAllowed()) {
                    /**
                     * columns set as nullable in db
                     */
                    $parseUploadData = $this->parseUpload();
                    $session->write($model->registryAlias().'.parseUpload', $parseUploadData);
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
        $fileNameField = $this->config('name');
        $fileContentField = $this->config('content');

        if (isset($data[$model->alias()][$fileNameField])) {
            unset($data[$model->alias()][$fileNameField]);
        }
        if (isset($data[$model->alias()][$fileContentField])) {
            unset($data[$model->alias()][$fileContentField]);
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

        if (substr_count(strtolower($this->config('size')), 'kb')) {
            $size = intval(str_replace('kb', '', (strtolower($this->config('size')))));
            return $size * $KILO;
        }
        if (substr_count(strtolower($this->config('size')), 'mb')) {
            $size = intval(str_replace('mb', '', (strtolower($this->config('size')))));
            return $size * $MEGA;
        }
        if (substr_count(strtolower($this->config('size')), 'gb')) {
            $size = intval(str_replace('gb', '', (strtolower($this->config('size')))));
            return $size * $GIGA;
        }
        $size = intval($this->config('size'));
        return $size * $TERA;
    }

    public function uploadedFileIsAllowed($file)
    {
        $isValid = true;

        if (isset($file['type']) && !in_array($file['type'], $this->allowableFileTypes)) {
            $isValid = false;
        }
        return $isValid;
    }

    public function uploadedFileSizeIsAcceptable($file)
    {
        $isValid = true;
        $restrictedSize = $this->readableFormatToBytes();

        // pr($file['size'] .' <> '. $restrictedSize);die;

        if (isset($file['type']) && ($file['size'] > $restrictedSize)) {
            $isValid = false;
        }
        return $isValid;
    }

    private function parseUpload($file = null)
    {
        if (!is_null($file)) {
            if ($this->config('useDefaultName')) {
                $fileName = $file['name'];
            } else {
                $pathInfo = pathinfo($file['name']);
                $fileName = uniqid() . '.' . $pathInfo['extension'];
            }
            $fileContent = file_get_contents($file['tmp_name']);
        } else {
            $fileName = null;
            $fileContent = null;
        }
        return ['fileName' => $fileName, 'fileContent' => $fileContent];
    }

    private function parseUploadInput($data, $nameContentArray)
    {
        $fileNameField = $this->config('name');
        $fileContentField = $this->config('content');
        $model = $this->_table;
        $data[$model->alias()][$fileNameField] = $nameContentArray['fileName'];
        $data[$model->alias()][$fileContentField] = $nameContentArray['fileContent'];
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
