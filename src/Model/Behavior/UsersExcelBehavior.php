<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\I18n\Time;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\EventTrait;
use Cake\I18n\I18n;
use Cake\Utility\Hash;
use XLSXWriter;
use Cake\ORM\TableRegistry;

// Events
// public function onExcelBeforeGenerate(Event $event, ArrayObject $settings) {}
// public function onExcelGenerate(Event $event, $writer, ArrayObject $settings) {}
// public function onExcelGenerateComplete(Event $event, ArrayObject $settings) {}
// public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {}
// public function onExcelStartSheet(Event $event, ArrayObject $settings, $totalCount) {}
// public function onExcelEndSheet(Event $event, ArrayObject $settings, $totalProcessed) {}
// public function onExcelGetLabel(Event $event, $column) {}

class UsersExcelBehavior extends Behavior
{
    use EventTrait;

    private $events = [];

    protected $_defaultConfig = [
        'folder' => 'export',
        'default_excludes' => ['modified_user_id', 'modified', 'created', 'created_user_id', 'password'],
        'excludes' => [],
        'limit' => 100000,
        'pages' => [],
        'autoFields' => true,
        'orientation' => 'landscape', // or portrait
        'sheet_limit' =>  1000000, // 1 mil rows and header row
        'auto_contain' => true
    ];

    public function initialize(array $config)
    {
        $this->config('excludes', array_merge($this->config('default_excludes'), $this->config('excludes')));
        if (!array_key_exists('filename', $config)) {
            $this->config('filename', $this->_table->alias());
        }
        $folder = WWW_ROOT . $this->config('folder');

        if (!file_exists($folder)) {
            umask(0);
            mkdir($folder, 0777);
        } else {
            // $delete = true;
            // if (array_key_exists('delete', $settings) &&  $settings['delete'] == false) {
            //  $delete = false;
            // }
            // if ($delete) {
            //  $this->deleteOldFiles($folder, $format);
            // }
        }
        $pages = $this->config('pages');
        if ($pages !== false && empty($pages)) {
            $this->config('pages', ['index', 'view']);
        }
    }

    private function eventMap($method)
    {
        $exists = false;
        if (in_array($method, $this->events)) {
            $exists = true;
        } else {
            $this->events[] = $method;
        }
        return $exists;
    }

    public function excel($id = 0)
    {
        $ids = empty($id) ? [] : $this->_table->paramsDecode($id);
        $this->generateXLXS($ids);
    }

    public function excelV4(Event $mainEvent, ArrayObject $extra)
    {
        $id = 0;
        $break = false;
        $action = $this->_table->action;
        $pass = $this->_table->request->pass;
        if (in_array($action, $pass)) {
            unset($pass[array_search($action, $pass)]);
            $pass = array_values($pass);
        }
        if (isset($pass[0])) {
            $id = $pass[0];
        }
        $ids = empty($id) ? [] : $this->_table->paramsDecode($id);
        $this->generateXLXS($ids);
        return true;
    }

    private function eventKey($key)
    {
        return 'Model.excel.' . $key;
    }

    public function generateXLXS($settings = [])
    {
        $_settings = [
            'file' => $this->config('filename') . '_' . date('Ymd') . 'T' . date('His') . '.xlsx',
            'path' => WWW_ROOT . $this->config('folder') . DS,
            'download' => true,
            'purge' => true
        ];
        $_settings = new ArrayObject(array_merge($_settings, $settings));

        $this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeGenerate'), 'onExcelBeforeGenerate', [$_settings]);

        $writer = new XLSXWriter();
        $excel = $this;

        $generate = function ($settings) {
            $this->generate($settings);
        };

        $_settings['writer'] = $writer;

        $event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGenerate'), 'onExcelGenerate', [$_settings]);
        if ($event->isStopped()) {
            return $event->result;
        }
        if (is_callable($event->result)) {
            $generate = $event->result;
        }

        $generate($_settings);

        $requestData = json_decode($settings['process']['params']);
        $userType = $requestData->user_type;

        $StudentCustomFields = TableRegistry::get('StudentCustomFields');
        $customFields = $StudentCustomFields->find()
                            ->select([
                                'id' => $StudentCustomFields->aliasField('id'),
                                'student_custom' => $StudentCustomFields->aliasField('name'),
                    ])->toArray();
        
        $labelArray3 = [];
        foreach ($customFields as $key => $value) {
            $labelArray3[] = $value->student_custom;
        }
        
        $labelArray = array("openEMIS_ID","first_name","middle_name","third_name","last_name","preferred_name","gender","date_of_birth","address","address_area","birth_area","nationality_name","identity_type","identity_number","email","postal_Code","user_type");

        $labelArray2 = array("staff_association_ID");

        if ($userType == 'Others' || $userType == 'Guardian' ) {
            foreach($labelArray as $label) {
                $headerRow[] = $this->getFields($this->_table, $settings, $label);
            }
        }
        
        if ($userType == 'Staff') {
           $labelArray1 = array_merge($labelArray,$labelArray2);
               foreach($labelArray1 as $label) {
                    $headerRow[] = $this->getFields($this->_table, $settings, $label);
            }
        }
        
        if ($userType == 'Student') {
           $labelArray4 = array_merge($labelArray,$labelArray3);
                foreach($labelArray4 as $label) {
                    $headerRow[] = $this->getFields($this->_table, $settings, $label);
                }
        }

        $data = $this->getData($settings);
       
        $writer->writeSheetRow('UserList', $headerRow);
        foreach($data as $row) {
            if(array_filter($row)) {
                $writer->writeSheetRow('UserList', $row);
            }
        }
        $blankRow[] = [];
        $footer = $this->getFooter();
        $writer->writeSheetRow('UserList', $blankRow);
        $writer->writeSheetRow('UserList', $footer);


        $filepath = $_settings['path'] . $_settings['file'];
        $_settings['file_path'] = $filepath;
        $writer->writeToFile($_settings['file_path']);
        $this->dispatchEvent($this->_table, $this->eventKey('onExcelGenerateComplete'), 'onExcelGenerateComplete', [$_settings]);

        if ($_settings['download']) {
            $this->download($filepath);
        }

        if ($_settings['purge']) {
            $this->purge($filepath);
        }
        return $_settings;
    }



    private function getData($settings) {
        $requestData = json_decode($settings['process']['params']);
        $userType = $requestData->user_type;
        $Users = TableRegistry::get('Security.Users');
        $userList = $Users
                        ->find()
                        ->select([
                            $Users->aliasField('id'),
                            $Users->aliasField('openemis_no'),
                            $Users->aliasField('first_name'),
                            $Users->aliasField('middle_name'),
                            $Users->aliasField('third_name'),
                            $Users->aliasField('last_name'),
                            $Users->aliasField('preferred_name'),
                            $Users->aliasField('date_of_birth'),
                            $Users->aliasField('address'),
                            $Users->aliasField('email'),
                            $Users->aliasField('postal_code'),
                            $Users->aliasField('identity_number'),
                            'nationality_name' => 'MainNationalities.name',
                            'identity_type' => 'MainIdentityTypes.name',
                            'gender' => 'Genders.name',
                            'address_area' => 'AddressAreas.name',
                            'birth_area' => 'BirthplaceAreas.name',
                        ])
                    ->leftJoin(
                        ['Genders' => 'genders'],
                        [
                            'Genders.id = '. $Users->aliasField('gender_id')
                        ]
                    )
                    ->leftJoin(
                        ['MainNationalities' => 'nationalities'],
                        [
                            'MainNationalities.id = '. $Users->aliasField('nationality_id')
                        ]
                    )
                    ->leftJoin(
                        ['MainIdentityTypes' => 'identity_types'],
                        [
                            'MainIdentityTypes.id = '. $Users->aliasField('identity_type_id')
                        ]
                    )
                    ->leftJoin(
                        ['AddressAreas' => 'area_administratives'],
                        [
                            'AddressAreas.id = '. $Users->aliasField('address_area_id')
                        ]
                    )
                    ->leftJoin(
                        ['BirthplaceAreas' => 'area_administratives'],
                        [
                            'BirthplaceAreas.id = '. $Users->aliasField('birthplace_area_id')
                        ]);

                    if ($userType ==  'Others') {
                        $userList
                             ->where([$Users->aliasField('is_staff') => 0]);
                    } 

                    if ($userType == 'Guardian') {
                        $userList 
                            ->where([$Users->aliasField('is_guardian') => 1]);
                    } 

                    if ($userType == 'Staff') {
                        $StaffCustomFieldValues = TableRegistry::get('StaffCustomFieldValues');
                        $StaffCustomFields = TableRegistry::get('StaffCustomFields');

                        $userList
                        ->select(['staff_association' => $StaffCustomFieldValues->aliasField('text_value')])
                        ->leftJoin([$StaffCustomFieldValues->alias() => $StaffCustomFieldValues->table()], [
                            $StaffCustomFieldValues->aliasField('staff_id = ') . $Users->aliasField('id'),
                        ])
                        ->leftJoin([$StaffCustomFields->alias() => $StaffCustomFields->table()], [
                            $StaffCustomFields->aliasField('id = ') . $StaffCustomFieldValues->aliasField('staff_custom_field_id'),
                        ])
                        ->where([$Users->aliasField('is_staff') => 1]);
                    }

                    if ($userType == 'Student') {

                        $userList
                            ->where([$Users->aliasField('is_student') => 1]);
                        }
                        $result = [];
                        if (!empty($userList)) {
                            foreach ($userList as $key => $value) {
                               $result[$key][] = $value->openemis_no;
                               $result[$key][] = $value->first_name;
                               $result[$key][] = $value->middle_name;
                               $result[$key][] = $value->third_name;
                               $result[$key][] = $value->last_name;
                               $result[$key][] = $value->preferred_name;
                               $result[$key][] = $value->gender;
                               $result[$key][] = date("d-m-Y", strtotime($value->date_of_birth));
                               $result[$key][] = $value->address;
                               $result[$key][] = $value->address_area;
                               $result[$key][] = $value->birth_area;
                               $result[$key][] = $value->nationality_name;
                               $result[$key][] = $value->identity_type;
                               $result[$key][] = $value->identity_number;
                               $result[$key][] = $value->email;
                               $result[$key][] = $value->postal_code;
                               $result[$key][] = $userType;
                                   if ($userType == 'Staff') {
                                        $result[$key][] = $value->staff_association;
                                   }

                                   if ($userType == 'Student') {
                                    $StudentCustomFieldValues = TableRegistry::get('StudentCustomFieldValues');
                                    $StudentCustomFields = TableRegistry::get('StudentCustomFields');

                                    $customFieldData = $StudentCustomFieldValues->find()
                                            ->select([
                                            $StudentCustomFields->aliasField('name'),
                                            $StudentCustomFieldValues->aliasField('text_value')
                                    ])
                                    ->rightJoin([$StudentCustomFields->alias() => $StudentCustomFields->table()], [
                                        $StudentCustomFields->aliasField('id = ') . $StudentCustomFieldValues->aliasField('student_custom_field_id'),
                                    ])
                                    ->where([$StudentCustomFieldValues->aliasField('student_id =') => $value->id])
                                    ->toArray();
                                    
                                    foreach ($customFieldData as $customField) {
                                        $result[$key][] = $customField->text_value;
                                    }
                                }
                            }
                        }
                       //echo '<pre>';print_r($result);die('aaaa');
                    return $result;
            }

    private function getFields($table, $settings, $label)
    {
        $language = I18n::locale();
        $module = $this->_table->alias();

        $event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, $label, $language], true);
        return $event->result;
    }

    private function getFooter()
    {
        $footer = [__("Report Generated") . ": "  . date("Y-m-d H:i:s")];
        return $footer;
    }

    private function getValue($entity, $table, $attr)
    {
        $value = '';
        $field = $attr['field'];
        $type = $attr['type'];
        $style = [];

        if (!empty($entity)) {
            if (!in_array($type, ['string', 'integer', 'decimal', 'text'])) {
                $method = 'onExcelRender' . Inflector::camelize($type);
                if (!$this->eventMap($method)) {
                    $event = $this->dispatchEvent($table, $this->eventKey($method), $method, [$entity, $attr]);
                } else {
                    $event = $this->dispatchEvent($table, $this->eventKey($method), null, [$entity, $attr]);
                }
                if ($event->result) {
                    $returnedResult = $event->result;
                    if (is_array($returnedResult)) {
                        $value = isset($returnedResult['value']) ? $returnedResult['value'] : '';
                        $style = isset($returnedResult['style']) ? $returnedResult['style'] : [];
                    } else {
                        $value = $returnedResult;
                    }
                }
            } else {
                $method = 'onExcelGet' . Inflector::camelize($field);
                $event = $this->dispatchEvent($table, $this->eventKey($method), $method, [$entity], true);
                if ($event->result) {
                    $returnedResult = $event->result;
                    if (is_array($returnedResult)) {
                        $value = isset($returnedResult['value']) ? $returnedResult['value'] : '';
                        $style = isset($returnedResult['style']) ? $returnedResult['style'] : [];
                    } else {
                        $value = $returnedResult;
                    }
                } elseif ($entity->has($field)) {
                    if ($this->isForeignKey($table, $field)) {
                        $associatedField = $this->getAssociatedKey($table, $field);
                        if ($entity->has($associatedField)) {
                            $value = $entity->{$associatedField}->name;
                        }
                    } else {
                        $value = $entity->{$field};
                    }
                }
            }
        }

        $specialCharacters = ['=', '@'];
        $firstCharacter = substr($value, 0, 1);
        if (in_array($firstCharacter, $specialCharacters)) {
            // append single quote to escape special characters
            $value = "'" . $value;
        }

        return ['rowData' => __($value), 'style' => $style];
    }

    private function isForeignKey($table, $field)
    {
        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAssociatedTable($table, $field)
    {
        $relatedModel = null;

        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    $relatedModel = $assoc;
                    break;
                }
            }
        }
        return $relatedModel;
    }

    public function getAssociatedKey($table, $field)
    {
        $tableObj = $this->getAssociatedTable($table, $field);
        $key = null;
        if (is_object($tableObj)) {
            $key = Inflector::underscore(Inflector::singularize($tableObj->alias()));
        }
        return $key;
    }

    public function generate($settings = [])
    {
        $language = I18n::locale();
        $module = $this->_table->alias();
        //echo '<pre>';print_r($module);
        
        $event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, 'postal_code', $language], true);
        return $event;
    }

    private function contain(Query $query, $fields, $table)
    {
        $contain = [];
        foreach ($fields as $attr) {
            $field = $attr['field'];
            if ($this->isForeignKey($table, $field)) {
                $contain[] = $this->getAssociatedTable($table, $field)->alias();
            }
        }
        $query->contain($contain);
    }

    private function download($path)
    {
        $filename = basename($path);

        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".$filename);
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($path));
        echo file_get_contents($path);
    }

    private function purge($path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 0];

        if ($this->isCAv4()) {
            $events['ControllerAction.Model.excel'] = 'excelV4';
            $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction'];
        }
        return $events;
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $action = $this->_table->action;
        if (in_array($action, $this->config('pages'))) {
            $toolbarButtons = isset($extra['toolbarButtons']) ? $extra['toolbarButtons'] : [];
            $toolbarAttr = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Export')
            ];

            $toolbarButtons['export'] = [
                'type' => 'button',
                'label' => '<i class="fa kd-export"></i>',
                'attr' => $toolbarAttr,
                'url' => ''
            ];

            $url = $this->_table->url($action);
            $url[0] = 'excel';
            $toolbarButtons['export']['url'] = $url;
            $extra['toolbarButtons'] = $toolbarButtons;
        }
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($buttons->offsetExists('view')) {
            $export = $buttons['view'];
            $export['type'] = 'button';
            $export['label'] = '<i class="fa kd-export"></i>';
            $export['attr'] = $attr;
            $export['attr']['title'] = __('Export');

            if ($isFromModel) {
                $export['url'][0] = 'excel';
            } else {
                $export['url']['action'] = 'excel';
            }

            $pages = $this->config('pages');
            if (in_array($action, $pages)) {
                $toolbarButtons['export'] = $export;
            }
        } elseif ($buttons->offsetExists('back')) {
            $export = $buttons['back'];
            $export['type'] = 'button';
            $export['label'] = '<i class="fa kd-export"></i>';
            $export['attr'] = $attr;
            $export['attr']['title'] = __('Export');

            if ($isFromModel) {
                $export['url'][0] = 'excel';
            } else {
                $export['url']['action'] = 'excel';
            }

            $pages = $this->config('pages');
            if ($pages != false) {
                if (in_array($action, $pages)) {
                    $toolbarButtons['export'] = $export;
                }
            }
        }
    }
}
