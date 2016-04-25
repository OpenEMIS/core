<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderFileBehavior extends RenderBehavior {
    protected $_defaultConfig = [
        'name' => 'text_value',
        'content' => 'file',
        'size' => '2MB'
    ];

    public $fileTypes = [
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'gif'   => 'image/gif',
        'png'   => 'image/png',
        // 'jpeg'=>'image/pjpeg',
        // 'jpeg'=>'image/x-png'
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
        'zip'   => 'application/zip'
    ];

	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function onGetCustomFileElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $fieldType = strtolower($this->fieldTypeCode);
        // for edit
        $fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];
        $savedId = null;
        $savedValue = null;
        if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
            if (isset($fieldValues[$fieldId]['text_value'])) {
                $savedValue = $fieldValues[$fieldId]['text_value'];
            }
        }
        // End

        if ($action == 'view') {
            // to-do: render attachment
            if (!is_null($savedValue)) {
                $value = $savedValue;

                $config = $this->_table->ControllerAction->getVar('ControllerAction');
                $buttons = $config['buttons'];
                $url = $buttons['download']['url'];
                $url[0] = 'downloadFile';
                $url[1] = $savedId;

                $value = $event->subject()->Html->link($savedValue, $url);
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            if (!is_null($savedValue)) {
                $attr['value'] = $savedValue;
            }

            $attr['fieldName'] = $fieldPrefix.".file";
            $attr['comment'] = $this->getFileComment();

            $value .= $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
            $value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
            if (!is_null($savedId)) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $savedId]);
            }
        }

        $event->stopPropagation();
        return $value;
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
        $includes['jasny']['include'] = true;
    }

    public function processFileValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $customValue = $settings['customValue'];

        $file = $customValue['file'];
        if (!empty($file) && $file['error'] == 0) { // success
            $parsedUploadData = $this->parseUpload($file);
            $customValue['text_value'] = $parsedUploadData['fileName'];
            $customValue['file'] = $parsedUploadData['fileContent'];
        } else {
            $customValue['text_value'] = '';
        }

        $settings['valueKey'] = 'text_value';
        $settings['customValue'] = $customValue;
        $this->processValues($entity, $data, $settings);
    }

    private function getFileComment() {
        $comment = '* File size should not be larger than ' . $this->config('size');

        return $comment;
    }

    private function parseUpload($file=null) {
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

    public function downloadFile($id) {
        $model = $this->_table->CustomFieldValues;
        $primaryKey = $model->primaryKey();
        $idKey = $model->aliasField($primaryKey);
        
        if ($model->exists([$idKey => $id])) {
            $data = $model->get($id);
            $fileName = $data->{$this->config('name')};
            $pathInfo = pathinfo($fileName);

            $file = $this->getFile($data->{$this->config('content')});
            $fileType = 'image/jpg';
            if (array_key_exists($pathInfo['extension'], $this->fileTypes)) {
                $fileType = $this->fileTypes[$pathInfo['extension']];
            }

            // echo '<img src="data:image/jpg;base64,' .   base64_encode($file)  . '" />';

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: " . $fileType);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');

            echo $file;
        }
        exit();
    }

    private function getFile($phpResourceFile) {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
}
