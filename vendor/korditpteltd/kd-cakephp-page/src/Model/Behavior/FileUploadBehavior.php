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

namespace Page\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Validation\Validator;

class FileUploadBehavior extends Behavior
{
    protected $_defaultConfig = [
        'fieldMap' => ['file_name' => 'file_content'],
        'size' => '1MB',
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

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        foreach ($this->config('fieldMap') as $fileName => $fileContent) {
            if (isset($data[$fileContent]['tmp_name']) && isset($data[$fileContent]['error']) && $data[$fileContent]['error'] == 0) {
                $data[$fileName] = $data[$fileContent]['name'];
                $data[$fileContent.'_file_size'] = $data[$fileContent]['size'];
                $data[$fileContent] = file_get_contents($data[$fileContent]['tmp_name']);
                $data[$fileContent.'_content'] = base64_encode($data[$fileContent]);
            } elseif (isset($data[$fileContent.'_remove']) && $data[$fileContent.'_remove'] == 1) {
                $data[$fileName] = null;
                $data[$fileContent] = null;
                $data[$fileContent.'_content'] = null;
                $data[$fileContent.'_file_size'] = null;
            } elseif (isset($data[$fileContent.'_content']) && !empty($data[$fileContent.'_content'])) {
                $data[$fileContent] = base64_decode($data[$fileContent.'_content']);
            } else {
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
                        if (in_array($ext, $this->config('allowable_file_types'))) {
                            return true;
                        }
                        return false;
                    },
                    'message' => __('File format not supported.')
                ]);
            }
        }
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
