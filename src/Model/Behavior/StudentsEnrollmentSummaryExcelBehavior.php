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


class StudentsEnrollmentSummaryExcelBehavior extends Behavior
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

        $labelArray = array("Name","Code","Academic Period","Education Grade","Gender","Number of Students");
        
        foreach($labelArray as $label) {
            $headerRow[] = $this->getFields($this->_table, $settings, $label);
        }

        $data = $this->getData($settings);
       
        $writer->writeSheetRow('InstitutionList', $headerRow);
        foreach($data as $row) {
            if(array_filter($row)) {
                $writer->writeSheetRow('InstitutionList', $row);
            }
        }
        $blankRow[] = [];
        $footer = $this->getFooter();
        $writer->writeSheetRow('InstitutionList', $blankRow);
        $writer->writeSheetRow('InstitutionList', $footer);


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


    //POCOR-5863 starts
    private function getData($settings) {
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $areaEducationId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $AcademicPeriods = TableRegistry::get('academic_periods');
        $Institutions = TableRegistry::get('institutions');
        $StudentsEnrollmentSummary = TableRegistry::get('institution_students');
        $area_id_array=[];
        if(!empty($areaEducationId)){
            $Areas = TableRegistry::get('Areas');
            if($areaEducationId == -1){
                $regionAreaArr = $Areas->find()->All();
            }else{
                $area_id_array[$areaEducationId] = $areaEducationId;
                $conditions = ['parent_id' => $areaEducationId];
                $regionAreaArr = $Areas->find()
                            ->where($conditions)
                            ->All();
            }
            
            if(!empty($regionAreaArr)){
                foreach ($regionAreaArr as $reg_val) {
                    $area_id_array[$reg_val->id] = $reg_val->id;
                    $conditions1 = array();
                    $conditions1 = ['parent_id' => $reg_val->id];
                    $distAreaArr = $Areas->find()
                                        ->where($conditions1)
                                        ->All();
                    if(!empty($distAreaArr)){
                        foreach ($distAreaArr as $dist_val) {
                            $area_id_array[$dist_val->id] = $dist_val->id;
                        }
                    }
                                        
                }
            }
        }
        $areaEducationId = $area_id_array;    
        $conditions = [];
        if($areaEducationId != -1){
            $conditions['Areas.id IN '] = $areaEducationId;
        }
        if(!empty($institutionId) && $institutionId > 0){
            $conditions[$Institutions->aliasfield('id')] = $institutionId;
        }
        $institutionsList = $Institutions
                                ->find()
                                ->select([
                                        $Institutions->aliasField('id'),
                                        $Institutions->aliasField('name'),
                                        $Institutions->aliasField('code'),
                                        $Institutions->aliasField('area_id')
                                    ])
                                ->leftJoin(['Areas' => 'areas'], [
                                    $Institutions->aliasfield('area_id').' = ' . 'Areas.id'
                                ])
                                ->where($conditions)
                                ->toArray();
        $result = [];
        $check_data_consitency  = [];
        $prepare_logical_result = [];
        if(!empty($institutionsList)){
            $i = 0;
            foreach ($institutionsList as $ins_key => $ins_value) {
               
                $instStudData = $StudentsEnrollmentSummary
                                ->find()
                                ->select([
                                    'institution_name' => 'Institutions.name',
                                    'institution_code' => 'Institutions.code',
                                    'academic_period_name' => 'AcademicPeriods.name',
                                    'gender_name' =>'Genders.name',
                                    'education_grade_name' => 'EducationGrades.name',
                                    'status_name' => 'StudentStatuses.name',
                                    'first_name' => 'Users.first_name',
                                    'last_name' => 'Users.last_name',
                                    'email' => 'Users.email',
                                    'openemis_no' => 'Users.openemis_no',
                                    'end_date' => $StudentsEnrollmentSummary->aliasField('end_date')
                                    // 'count'=> $StudentsEnrollmentSummary->find()->func()->count('DISTINCT '.$StudentsEnrollmentSummary->aliasField('student_id'))
                                    
                                 ])
                                ->leftJoin(['Users' => 'security_users'], [
                                                'Users.id = ' . $StudentsEnrollmentSummary->aliasfield('student_id')
                                            ])
                                ->leftJoin(['Genders' => 'genders'], [
                                                'Users.gender_id = ' . 'Genders.id'
                                            ])
                                ->leftJoin(['Institutions' => 'institutions'], [
                                            $StudentsEnrollmentSummary->aliasfield('institution_id').' = ' . 'Institutions.id'
                                        ])
                                ->leftJoin(['EducationGrades' => 'education_grades'], [
                                                $StudentsEnrollmentSummary->aliasfield('education_grade_id').' = ' . 'EducationGrades.id'
                                            ])
                                ->leftJoin(['AcademicPeriods' => 'academic_periods'], [
                                                $StudentsEnrollmentSummary->aliasfield('academic_period_id').' = ' . 'AcademicPeriods.id'
                                            ])
                                ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                                    $StudentsEnrollmentSummary->aliasfield('student_status_id').' = ' . 'StudentStatuses.id'
                                ])
                                ->where([
                                    'Genders.id IS NOT NULL', 'AcademicPeriods.id' => $academicPeriodId,
                                    $StudentsEnrollmentSummary->aliasfield('institution_id') => $ins_value->id,
                                    //POCOR-6620[START]
                                    //$StudentsEnrollmentSummary->aliasfield('student_status_id') => $enrolledStatus  //POCOR-6712
                                    //POCOR-6620[END]
                                ]);
                                // if ($institutionId > 0) {
                                //     $instStudData->where([$StudentsEnrollmentSummary->aliasfield('student_status_id') => $enrolledStatus]);
                                // }
                                $instStudData
                                //->group(['EducationGrades.id', 'Genders.id', 'StudentStatuses.id'])
                                ->hydrate(false)
                                ->toArray();
                
                if(!empty($instStudData)){
                    foreach ($instStudData as $key => $value) {                        
                        if ( isset($check_data_consitency[$value['academic_period_name']][$value['institution_name']][$value['openemis_no']]) ) {
                            $end_date_check = $check_data_consitency[$value['academic_period_name']][$value['institution_name']][$value['openemis_no']];
                            if ($end_date_check < $value['end_date']->format('Y-m-d')) {
                                $prepare_logical_result[$value['institution_name']][$value['openemis_no']] = [
                                    !empty($value['institution_name'])     ? $value['institution_name']     : ' 0',
                                    !empty($value['institution_code'])     ? $value['institution_code']     : ' 0',
                                    !empty($value['academic_period_name']) ? $value['academic_period_name'] : ' 0',
                                    !empty($value['education_grade_name']) ? $value['education_grade_name'] : ' 0',
                                    !empty($value['gender_name'])          ? $value['gender_name']          : ' 0',
                                ];
                                $check_data_consitency[$value['academic_period_name']][$value['institution_name']][$value['openemis_no']] = $value['end_date']->format('Y-m-d');
                            }
                        } else {
                            $check_data_consitency[$value['academic_period_name']][$value['institution_name']][$value['openemis_no']] = $value['end_date']->format('Y-m-d');
                            $prepare_logical_result[$value['institution_name']][$value['openemis_no']] = [
                                !empty($value['institution_name'])     ? $value['institution_name']     : ' 0',
                                !empty($value['institution_code'])     ? $value['institution_code']     : ' 0',
                                !empty($value['academic_period_name']) ? $value['academic_period_name'] : ' 0',
                                !empty($value['education_grade_name']) ? $value['education_grade_name'] : ' 0',
                                !empty($value['gender_name'])          ? $value['gender_name']          : ' 0',

                            ];
                        }
                        $i++;
                    }
                }                
            }
        }
        $prepare_result_array = [];
        foreach ( $prepare_logical_result as $users ) {
            foreach ( $users as $user ) {
                $prepare_result_array[$user[1]][$user[3]][$user[4]] = [
                    'count' => $prepare_result_array[$user[1]][$user[3]][$user[4]]['count'] + 1,
                    'institution_name' => $user[0],
                    'year' => $user[2]
                ];
            }
        }
        $final_result = [];
        foreach ( $prepare_result_array as $institution_code => $institution_code_data) {
            foreach ( $institution_code_data as $grade => $grade_data ) {
                foreach ( $grade_data as $gender => $user_data ) {
                    $final_result[] = [
                        $user_data['institution_name'],
                        $institution_code,
                        $user_data['year'],
                        $grade,
                        $gender,
                        $user_data['count'],
                    ];
                }
            }
        }
        return $final_result;
        /* START : POCOR-6469
        if ($institutionId == '' || $institutionId == null || $institutionId < 1) {
        * END : POCOR-6469 */
            /*
            $updated_result = [];
            foreach ($result AS $grade_data) {
                $check_key = $grade_data[0].$grade_data[1].$grade_data[2].$grade_data[3].$grade_data[4];
                if ($grade_data[5] != 0) {
                    $updated_result[$check_key] = $grade_data;
                }
            }
            return $updated_result;
        */
        /* START : POCOR-6469
        } else {
            $check_grade_exist = [];
            $updated_result= [];
            foreach ($result AS $grade_data) {
                if (!in_array($grade_data[4].$grade_data[3], $check_grade_exist)) {
                    $updated_result[] = $grade_data;
                }
                $check_grade_exist[] = $grade_data[4].$grade_data[3];
            }
            return $updated_result;
        }
        * END : POCOR-6469 */
    }
    //POCOR-5863 ends
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
