<?php
namespace Page\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Validation\Validator;

class FileUploadBehavior extends Behavior
{
    protected $_defaultConfig = [
        'fieldMap' => ['file_name' => 'file_content'],
        'type' => 'file', // file or image
        'size' => '1MB',
        'compression' => 70,
        'contentEditable' => true,
        'allowable_file_types' => ['jpeg', 'jpg', 'gif', 'png', 'rtf', 'txt', 'csv', 'pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'odt', 'ods', 'key', 'pages', 'numbers']
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
        'odt'   => 'application/vnd.oasis.opendocument.text',
        'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
        'key'   => 'application/x-iwork-keynote-sffkey',
        'pages' => 'application/x-iwork-pages-sffpages',
        'numbers' => 'application/x-iwork-numbers-sffnumbers'

    );

    public function initialize(array $config)
    {
        $this->_config = array_merge($this->_config, $config);
    }

    public function compress($source, $quality)
    {
        $info = getimagesize($source);

        if ($info['mime'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
        } elseif ($info['mime'] == 'image/gif') {
            $image = imagecreatefromgif($source);
        } elseif ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source);
        }

        $destination = tempnam(TMP, 'image');
        imagejpeg($image, $destination, $quality);
        return $destination;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        foreach ($this->config('fieldMap') as $fileName => $fileContent) {
            if (isset($data[$fileContent]['tmp_name']) && isset($data[$fileContent]['error']) && $data[$fileContent]['error'] == UPLOAD_ERR_OK) {
                $data[$fileName] = $data[$fileContent]['name'];
                $data[$fileContent.'_file_size'] = $data[$fileContent]['size'];
                if ($this->config('type') == 'image') {
                    $tmpPath = $this->compress($data[$fileContent]['tmp_name'], $this->config('compression'));
                } else {
                    $tmpPath = $data[$fileContent]['tmp_name'];
                }
                $data[$fileContent] = file_get_contents($tmpPath);
                $data[$fileContent.'_content'] = base64_encode($data[$fileContent]);
                unlink($tmpPath);
            } elseif (isset($data[$fileContent]['error']) && $data[$fileContent]['error'] == UPLOAD_ERR_NO_FILE) {
                $data->offsetUnset($fileContent);
                if ($data->offsetExists($fileName)) {
                    $data->offsetUnset($fileName);
                }
            } elseif (isset($data[$fileContent.'_remove']) && $data[$fileContent.'_remove'] == 1) {
                $data[$fileName] = null;
                $data[$fileContent] = null;
                $data[$fileContent.'_content'] = null;
                $data[$fileContent.'_file_size'] = null;
            } elseif (isset($data[$fileContent.'_content']) && !empty($data[$fileContent.'_content'])) {
                $data[$fileContent] = base64_decode($data[$fileContent.'_content']);
            } elseif (!isset($data[$fileName])) {
                $data[$fileContent] = null;
                $data[$fileName] = null;
                $data[$fileContent.'_content'] = null;
                $data[$fileContent.'_file_size'] = null;
            }
        }
    }

    public function buildValidator(Event $event, Validator $validator, $name)
    {
        if ($name == 'default') {
            foreach ($this->config('fieldMap') as $fileName => $fileContent) {
                $validator->add($fileContent, 'ruleFileSize', [
                    'rule' => function ($check, array $globalData) use ($fileContent) {
                        return $this->readableFormatToBytes() > $globalData['data'][$fileContent.'_file_size'];
                    },
                    'message' => __('File size exceeded the allowed limit.')
                ]);

                $validator->add($fileContent, 'ruleFileFormat', [
                    'rule' => function ($check, array $globalData) use ($fileName) {
                        $ext = pathinfo($globalData['data'][$fileName], PATHINFO_EXTENSION);
                        $allowableFileTypes = $this->config('allowable_file_types');
                        if (isset($allowableFileTypes[$fileName])) {
                            return in_array($ext, $allowableFileTypes[$fileName]);
                        } else {
                            return in_array($ext, $allowableFileTypes);
                        }
                    },
                    'message' => __('File format not supported.')
                ]);
            }
        }
    }

    public function getBinaryColumn($nameColumn = null)
    {
        $binaryColumn = '';
        $fieldMap = $this->config('fieldMap');
        if (is_null($nameColumn)) {
            $binaryColumn = current($fieldMap);
        } else {
            $binaryColumn = $fieldMap[$nameColumn];
        }
        return $binaryColumn;
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
}
