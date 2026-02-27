<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use CustomField\Model\Behavior\RenderBehavior;
use Cake\Http\Session;
use Cake\Log\Log;
use Laminas\Diactoros\UploadedFile;

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

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadFile'] = 'downloadFile';

        return $events;
    }

    public function onGetCustomFileElement(EventInterface $event, $action, $entity, $attr, $options = [])
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
                $savedValue = $fieldValues[$fieldId]['file_name']; //POCOR-9407
                
            }
        }
        // End
        if ($action == 'view' && !empty($savedFile)) { //POCOR-9407  add this new condition  
            if (!is_null($savedValue)) {
                $value = $savedValue;

                // If file resource exists and is an image → show thumbnail
                $mimeType = (!empty($savedFile) && is_resource($savedFile)) 
                    ? mime_content_type($savedFile) 
                    : '';

                if (!empty($savedFile) && is_resource($savedFile) && in_array($mimeType, $this->fileImagesMap)) {
                    $imgSrc = base64_encode(stream_get_contents($savedFile));
                    if (base64_decode($imgSrc, true)) {
                        $value = $event->getSubject()->renderElement(
                            'ControllerAction.thumbnail',
                            ['attr' => ['src' => "data:$mimeType;base64,$imgSrc", 'title' => $savedValue]]
                        );
                    }
                }

                // Always provide a download link (even if file resource is missing)
                if (!empty($savedId)) { //POCOR-9407 start
                    $url = $model->url('view');
                    $url['action'] = 'downloadFile';
                    $url[0] = 'downloadFile';
                    $url[1] = $model->paramsEncode(['id' => $savedId, 'institution_id' => $model->getInstitutionID()]);

                    // Append download link below/next to the image or name
                    $value .= '<br>' . $event->getSubject()->Html->link(
                        'Download ' . h($savedValue),
                        $url,
                        ['target' => '_blank']
                    );
                } 
            } //POCOR-9407 end
        }else if ($action == 'view') {
            if (!is_null($savedValue)) {

                $value = $savedValue;
                $mimeType = !is_null($savedFile) ? mime_content_type($savedFile) : '';

                if (in_array($mimeType, $this->fileImagesMap) && is_resource($savedFile)) {
                    $imgSrc = base64_encode(stream_get_contents($savedFile));
                    if (base64_decode($imgSrc, true)) {
                        $value = $event->getSubject()->renderElement('ControllerAction.thumbnail', ['attr' => ['src' => "data:image/jpeg;base64,$imgSrc", 'title' => $savedValue]]);
                    }
                } else {
                    $url = $model->url('view');
                    $url['action'] = $model->request->getParam('action');
                    $url[0] = 'downloadFile';
                    $url[1] = $model->paramsEncode(['id' => $savedId]);
                    $value = $event->getSubject()->Html->link($savedValue, $url);
                }
            }
        } else if ($action == 'edit') {
            $form = $event->getSubject()->Form;
            $unlockFields = [];
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            if (!is_null($savedValue)) {
                $attr['value'] = $savedValue;
            }else{
                $attr['value'] = '';
            }

            // Rely on session variable to show file name, if session has value, read from session
            $session = $model->request->getSession();
            $sessionKey = $model->getRegistryAlias().'.parseFile.'.$fieldId;
            if ($session->check($sessionKey)) {
                $parseFileData = $session->read($sessionKey);
                $attr['value'] = $fieldValues[$fieldId]['file_name'];
            }
            // End

            $attr['fieldName'] = $fieldPrefix.".file";
            $form->unlockField($attr['fieldName']);
            $attr['comment'] = $this->getFileComment();

            $value .= $event->getSubject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
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

    public function onUpdateIncludes(EventInterface $event, ArrayObject $includes, $action)
    {
        $includes['jasny']['include'] = true;
    }

    public function onFileInitialize(EventInterface $event, Entity $entity, ArrayObject $settings)
    {
        $fieldKey = $settings['fieldKey'];
        $customValue = $settings['customValue'];

        $fieldId = $customValue[$fieldKey];

        $model = $this->_table;
        $session = $model->request->getSession();
        $sessionKey = $model->getRegistryAlias().'.parseFile.'.$fieldId;

        $parseFileData = [
            'file' => ['name' => $customValue->text_value]
        ];
        $session->write($sessionKey, $parseFileData);
    }

    public function patchFileValues(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $fieldKey = $settings['fieldKey'];
        $customValue = $settings['customValue'];

        $fieldId = $customValue[$fieldKey] ?? null;
        $file = $customValue['file'] ?? null;

        $session = new \Cake\Http\Session();
        $sessionKey = $this->_table->getRegistryAlias() . '.parseFile.' . $fieldId;
        $sessionErrorKey = $this->_table->getRegistryAlias() . '.parseFileError.' . $fieldId;
        $session->delete($sessionErrorKey);

        //---- fileSizeAllowed() ----
        if ($file instanceof \Laminas\Diactoros\UploadedFile && $file->getError() === UPLOAD_ERR_OK) {
            // convert UploadedFile to array-like for consistency
            $tmpFile = [
                'name' => $file->getClientFilename(),
                'size' => $file->getSize(),
                'tmp_name' => $file->getStream()->getMetadata('uri'),
                'type' => $file->getClientMediaType(),
                'error' => $file->getError(),
            ];

            if ($this->fileSizeAllowed($tmpFile)) {
                $parseFileData = $this->parseFile($tmpFile);
                $session->write($sessionKey, $parseFileData);
            } else {
                $session->delete($sessionKey);
                $session->write($sessionErrorKey, [
                    'file' => [
                        'ruleCustomFile' => $this->_table->getMessage(
                            'CustomField.file.maxSize',
                            ['sprintf' => $this->getConfig('size')]
                        )
                    ]
                ]);
            }

        } elseif (is_array($file) && isset($file['tmp_name']) && $file['error'] === 0) {
            if ($this->fileSizeAllowed($file)) {
                $parseFileData = $this->parseFile($file);
                $session->write($sessionKey, $parseFileData);
            } else {
                $session->delete($sessionKey);
                $session->write($sessionErrorKey, [
                    'file' => [
                        'ruleCustomFile' => $this->_table->getMessage(
                            'CustomField.file.maxSize',
                            ['sprintf' => $this->getConfig('size')]
                        )
                    ]
                ]);
            }
        }

        $settings['customValue'] = $customValue;
    }


    /**
     * POCOR-9407
     *
     * Main entry point for processing custom field file values.
     * Decides which handler method to call based on whether a new file
     * was uploaded in the request or not.
     *
     * @param Event        $event    The event instance triggered by the save operation.
     * @param Entity       $entity   The entity being saved (with custom field data).
     * @param ArrayObject  $data     Additional request data passed into the process.
     * @param ArrayObject  $settings Field configuration and custom values.
     *
     * @return mixed Result of the delegated file processing method.
     */
    public function processFileValues(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $fieldKey    = $settings['fieldKey'];
        $customValue = $settings['customValue'];

        $hasRequestFile = (
            isset($customValue['file']) &&
            $customValue['file'] instanceof UploadedFile
        );
        if (isset($customValue['file']) &&$customValue['file'] instanceof UploadedFile) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $customValue['file'];
            
            // Get original file name 
            $fileName = $uploadedFile->getClientFilename();
        }

        //Case 1: If request contains a file (new upload attempt and file in entity already) 
        if ($hasRequestFile && !empty($customValue['id'])) {
            return $this->processFileExist($event, $entity, $data, $settings);
        }elseif($hasRequestFile && empty($customValue['id']) && !empty($fileName)){
            return $this->processFileExist($event, $entity, $data, $settings);
        }

        //Case 2: If NO request file → use this (handles empty case and preserve existing)
        return $this->processFileNewRequest($event, $entity, $data, $settings);
    }

    //POCOR-9407  
    public function processFileExist(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $settings['valueKey'] = 'file';

        $fieldKey    = $settings['fieldKey'];
        $customValue = $settings['customValue'];
        $fieldId     = $customValue[$fieldKey] ?? null;

        $session = new \Cake\Http\Session();
        $sessionKey = $this->_table->getRegistryAlias() . '.parseFile.' . $fieldId;

        $uploadNewFile = false;

        if ($session->check($sessionKey)) {
            $parseFileData = $session->read($sessionKey);

            if (!empty($parseFileData['fileContent'])) {

                //store both file and file_name
                $customValue['file']      = $parseFileData['fileContent'];
                $customValue['file_name'] = $parseFileData['fileName'];
                $uploadNewFile = true;
              
            }

            $session->delete($sessionKey);
        }
        $settings['customValue'] = $customValue;
        $this->processValuesFile($entity, $data, $settings); //POCOR-9407
    }

    //POCOR-9407
    public function processFileNewRequest(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $settings['valueKey'] = 'file';

        $fieldKey    = $settings['fieldKey'];
        $customValue = $settings['customValue'];
        $fieldId     = $customValue[$fieldKey] ?? null;

        $session = new \Cake\Http\Session();
        $sessionKey = $this->_table->getRegistryAlias() . '.parseFile.' . $fieldId;

        $uploadNewFile = false;

        //Preserve existing file from entity if no new file
        if (!$uploadNewFile) {
            $foundInEntity = false;
            foreach ($entity->custom_field_values ?? [] as $cf) {
                if ($cf[$fieldKey] == $fieldId) {
                    $existingFile = $cf->file ?? null;
                    $existingFileName = $cf->file_name ?? null;

                    if ($existingFile instanceof UploadedFile) {
                        $customValue['file'] = $existingFile->getError() === UPLOAD_ERR_OK
                            ? $existingFile->getStream()->getContents()
                            : null;
                    } else {
                        $customValue['file'] = $existingFile;
                    }

                    $customValue['file_name'] = $existingFileName;
                    $foundInEntity = true;
                    break;
                }
            }

            // Final fallback: no file anywhere
            if (!$foundInEntity && !isset($customValue['file'])) {
                $customValue['file'] = null;
                $customValue['file_name'] = null;
            }
        }

        //Final strict check before saving
        if (isset($customValue['file'])) {
            if ($customValue['file'] instanceof UploadedFile) {
                
                $uploadedFile = $customValue['file'];
                $customValue['file'] = $uploadedFile->getError() === UPLOAD_ERR_OK
                    ? $uploadedFile->getStream()->getContents()
                    : null;
                $customValue['file_name'] = $uploadedFile->getClientFilename();
            } elseif (!is_string($customValue['file']) && !is_null($customValue['file'])) {
                Log::error('Invalid file type detected: ' . gettype($customValue['file']));
                $customValue['file'] = null;
                $customValue['file_name'] = null;
            }
        }

        $settings['customValue'] = $customValue;
        $this->processValuesFile($entity, $data, $settings);
    }

    private function getFileComment()
    {
        $comment = '* ' . sprintf(__('File size should not be larger than %s'), $this->getConfig('size'));

        return $comment;
    }

    private function readableFormatToBytes()
    {
        $KILO = 1024;
        $MEGA = $KILO * 1024;
        $GIGA = $MEGA * 1024;
        $TERA = $GIGA * 1024;

        $sizeConfig = strtolower((string)$this->getConfig('size')); // always fetch as string

        if (strpos($sizeConfig, 'kb') !== false) {
            $size = (int) str_replace('kb', '', $sizeConfig);
            return $size * $KILO;
        } elseif (strpos($sizeConfig, 'mb') !== false) {
            $size = (int) str_replace('mb', '', $sizeConfig);
            return $size * $MEGA;
        } elseif (strpos($sizeConfig, 'gb') !== false) {
            $size = (int) str_replace('gb', '', $sizeConfig);
            return $size * $GIGA;
        } elseif (strpos($sizeConfig, 'tb') !== false) {
            $size = (int) str_replace('tb', '', $sizeConfig);
            return $size * $TERA;
        } else {
            return (int) $this->getConfig('size'); // fallback: numeric value directly
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

    public function downloadFile(EventInterface $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table->CustomFieldValues->getTarget();
        $ids = $model->paramsDecode($this->_table->paramsPass(0));
        $idKey = $model->getIdKeys($model, $ids);

        if ($model->exists($idKey)) {
            $data = $model->get($ids);
            $fileName = $data->{$this->getConfig('name')};
            $pathInfo = pathinfo($fileName);

            $file = $this->getFile($data->{$this->getConfig('content')});
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


    /*public function patchFileValuesbkp(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $fieldKey = $settings['fieldKey'];
        $customValue = $settings['customValue'];

        $fieldId = $customValue[$fieldKey];
        $file = $customValue['file'];

        $model = $this->_table;
        $session = $model->request->getSession();
        $sessionKey = $model->getRegistryAlias().'.parseFile.'.$fieldId;
        $sessionErrorKey = $model->getRegistryAlias().'.parseFileError.'.$fieldId;
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
                            'ruleCustomFile' => $model->getMessage('CustomField.file.maxSize', ['sprintf' => $this->getConfig('size')])
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
    }*/

    /*public function processFileValuesbkp(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $settings['valueKey'] = 'text_value';

        $fieldKey = $settings['fieldKey'];
        $valueKey = $settings['valueKey'];
        $customValue = $settings['customValue'];

        $fieldId = $customValue[$fieldKey];
        $model = $this->_table;
        $session = $model->request->getSession();
        $sessionKey = $model->getRegistryAlias().'.parseFile.'.$fieldId;

        $uploadNewFile = true;
        if ($session->check($sessionKey)) {
            $parseFileData = $session->read($sessionKey);

            if (isset($parseFileData['fileContent'])) {
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
    }*/

    private function readableFormatToBytesbkp()
    {
        $KILO = 1024;
        $MEGA = $KILO * 1024;
        $GIGA = $MEGA * 1024;
        $TERA = $GIGA * 1024;

        if (substr_count(strtolower($this->setConfig('size')), 'kb')) {
            $size = intval(str_replace('kb', '', (strtolower($this->getConfig('size')))));
            return $size * $KILO;
        } else if (substr_count(strtolower($this->setConfig('size')), 'mb')) {
            $size = intval(str_replace('mb', '', (strtolower($this->getConfig('size')))));
            return $size * $MEGA;
        } else if (substr_count(strtolower($this->setConfig('size')), 'gb')) {
            $size = intval(str_replace('gb', '', (strtolower($this->getConfig('size')))));
            return $size * $GIGA;
        } else if (substr_count(strtolower($this->setConfig('size')), 'tb')) {
            $size = intval(str_replace('tb', '', (strtolower($this->getConfig('size')))));
            return $size * $TERA;
        } else {
            return intval($this->getConfig('size'));
        }
    }
}
