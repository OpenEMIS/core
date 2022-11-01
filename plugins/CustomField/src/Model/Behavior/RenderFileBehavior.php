<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderFileBehavior extends RenderBehavior
{
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

    public $fileImagesMap = array(
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'gif'   => 'image/gif',
        'png'   => 'image/png'
    );

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadFile'] = 'downloadFile';

        return $events;
    }

    public function onGetCustomFileElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $value = '';

        $model = $this->_table;
        $fieldType = strtolower($this->fieldTypeCode);
        // for edit
        $fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];

        $savedId = null;
        $savedValue = null;
        $savedFile = null;
        if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
            if (isset($fieldValues[$fieldId]['text_value'])) {
                $savedValue = $fieldValues[$fieldId]['text_value'];
            }
            if (isset($fieldValues[$fieldId]['file'])) {
                $savedFile = $fieldValues[$fieldId]['file'];
            }
        }
        // End

        if ($action == 'view') {
            if (!is_null($savedValue)) {
                $value = $savedValue;
                $mimeType = !is_null($savedFile) ? mime_content_type($savedFile) : '';

                if (in_array($mimeType, $this->fileImagesMap) && is_resource($savedFile)) {
                    $imgSrc = base64_encode(stream_get_contents($savedFile));
                    if (base64_decode($imgSrc, true)) {
                        $value = $event->subject()->renderElement('ControllerAction.thumbnail', ['attr' => ['src' => "data:image/jpeg;base64,$imgSrc", 'title' => $savedValue]]);
                    }
                } else {
                    $url = $model->url('view');
                    $url['action'] = $model->request->param('action');
                    $url[0] = 'downloadFile';
                    $url[1] = $model->paramsEncode(['id' => $savedId]);
                    $value = $event->subject()->Html->link($savedValue, $url);
                }
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $unlockFields = [];
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            if (!is_null($savedValue)) {
                $attr['value'] = $savedValue;
            }

            // Rely on session variable to show file name, if session has value, read from session
            $session = $model->request->session();
            $sessionKey = $model->registryAlias().'.parseFile.'.$fieldId;
            if ($session->check($sessionKey)) {
                $parseFileData = $session->read($sessionKey);
                $attr['value'] = $parseFileData['file']['name'];
            }
            // End

            $attr['fieldName'] = $fieldPrefix.".file";
            $form->unlockField($attr['fieldName']);
            $attr['comment'] = $this->getFileComment();

            $value .= $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
            $value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
            $unlockFields[] = $fieldPrefix.".".$attr['attr']['fieldKey'];
            if (!is_null($savedId)) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $savedId]);
                $unlockFields[] = $fieldPrefix.".id";
            }
            $value = $this->processRelevancyDisabled($entity, $value, $fieldId, $form, $unlockFields);
        }

        $event->stopPropagation();
        return $value;
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action)
    {
        $includes['jasny']['include'] = true;
    }

    public function onFileInitialize(Event $event, Entity $entity, ArrayObject $settings)
    {
        $fieldKey = $settings['fieldKey'];
        $customValue = $settings['customValue'];

        $fieldId = $customValue[$fieldKey];

        $model = $this->_table;
        $session = $model->request->session();
        $sessionKey = $model->registryAlias().'.parseFile.'.$fieldId;

        $parseFileData = [
            'file' => ['name' => $customValue->text_value]
        ];
        $session->write($sessionKey, $parseFileData);
    }

    public function patchFileValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $fieldKey = $settings['fieldKey'];
        $customValue = $settings['customValue'];

        $fieldId = $customValue[$fieldKey];
        $file = $customValue['file'];

        $model = $this->_table;
        $session = $model->request->session();
        $sessionKey = $model->registryAlias().'.parseFile.'.$fieldId;
        $sessionErrorKey = $model->registryAlias().'.parseFileError.'.$fieldId;
        $session->delete($sessionErrorKey);

        if (!is_array($file)) {
            if ($session->check($sessionKey)) {
                $session->delete($sessionKey);
            }
        } else {
            if (!empty($file) && $file['error'] == 0) { // success
                if ($this->fileSizeAllowed($file)) {
                    $parseFileData = $this->parseFile($file);
                    $parseFileData['file'] = $file;
                    $session->write($sessionKey, $parseFileData);
                } else {
                    // File size too big
                    $session->delete($sessionKey);
                    $session->write($sessionErrorKey, [
                        'file' => [
                            'ruleCustomFile' => $model->getMessage('CustomField.file.maxSize', ['sprintf' => $this->config('size')])
                        ]
                    ]);
                }
            } else if (!empty($file) && $file['error'] != 4) {  // allow empty
                $session->delete($sessionKey);
                $session->write($sessionErrorKey, [
                    'file' => [
                        'ruleCustomFile' => $model->getMessage('fileUpload.'.$file['error'])
                    ]
                ]);
            }
        }

        $settings['customValue'] = $customValue;
    }

    public function processFileValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $settings['valueKey'] = 'text_value';

        $fieldKey = $settings['fieldKey'];
        $valueKey = $settings['valueKey'];
        $customValue = $settings['customValue'];

        $fieldId = $customValue[$fieldKey];
        $model = $this->_table;
        $session = $model->request->session();
        $sessionKey = $model->registryAlias().'.parseFile.'.$fieldId;

        $uploadNewFile = true;
        if ($session->check($sessionKey)) {
            $parseFileData = $session->read($sessionKey);

            if (array_key_exists('fileContent', $parseFileData)) {
                // upload new file
                $customValue['text_value'] = $parseFileData['fileName'];
                $customValue['file'] = $parseFileData['fileContent'];
            } else {
                $uploadNewFile = false;
            }

            $session->delete($sessionKey);
        } else {
            // will delete
            $customValue['text_value'] = '';
            $customValue['file'] = '';
        }

        if ($uploadNewFile) {
            $settings['customValue'] = $customValue;
            $this->processValues($entity, $data, $settings);
        }
    }

    private function getFileComment()
    {
        $comment = '* ' . sprintf(__('File size should not be larger than %s'), $this->config('size'));

        return $comment;
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
        } else if (substr_count(strtolower($this->config('size')), 'mb')) {
            $size = intval(str_replace('mb', '', (strtolower($this->config('size')))));
            return $size * $MEGA;
        } else if (substr_count(strtolower($this->config('size')), 'gb')) {
            $size = intval(str_replace('gb', '', (strtolower($this->config('size')))));
            return $size * $GIGA;
        } else if (substr_count(strtolower($this->config('size')), 'tb')) {
            $size = intval(str_replace('tb', '', (strtolower($this->config('size')))));
            return $size * $TERA;
        } else {
            return intval($this->config('size'));
        }
    }

    private function fileSizeAllowed($file)
    {
        return !(isset($file['type']) && ($file['size'] > $this->readableFormatToBytes()));
    }

    private function parseFile($file = null)
    {
        if (!is_null($file)) {
            $fileName = $file['name'];
            $fileContent = file_get_contents($file['tmp_name']);
        } else {
            $fileName = null;
            $fileContent = null;
        }

        return ['fileName' => $fileName, 'fileContent' => $fileContent];
    }

    public function downloadFile(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table->CustomFieldValues->target();
        $ids = $model->paramsDecode($this->_table->paramsPass(0));
        $idKey = $model->getIdKeys($model, $ids);

        if ($model->exists($idKey)) {
            $data = $model->get($ids);
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

    private function getFile($phpResourceFile)
    {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
}
