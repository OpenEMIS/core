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

class StudentAbsencesExcelBehavior extends Behavior
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

        $labelArray = array("openEMIS_ID","student","institution","code","date","attendance_per_day",/*"subjects",*/"absence_type","institution_class","education_grade","gender","default_identity_type","identity_number","address","contact", "parent_name");

        foreach($labelArray as $label) {
            $headerRow[] = $this->getFields($this->_table, $settings, $label);
        }

        $data = $this->getData($settings);
        $writer->writeSheetRow('StudentAbsences', $headerRow);
        
        foreach($data as $row) {
            if(array_filter($row)) {
                $writer->writeSheetRow('StudentAbsences', $row);
            }
        }
        
        $blankRow[] = [];
        $footer = $this->getFooter();
        $writer->writeSheetRow('StudentAbsences', $blankRow);
        $writer->writeSheetRow('StudentAbsences', $footer);


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
        $StudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institution_id = $requestData->institution_id;
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $Genders = TableRegistry::get('User.Genders');
        $Users = TableRegistry::get('User.Users');
        $StudentGuardians = TableRegistry::get('Student.StudentGuardians');
        $Guardians = TableRegistry::get('Security.Users');
        $UserContacts = TableRegistry::get('UserContacts');
        $GuardianUser = TableRegistry::get('Security.Users');
        $InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
        $StudentMarkTypeStatuses = TableRegistry::get('Attendance.StudentMarkTypeStatuses');
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        //$StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $InstitutionStudentAbsenceDetails = TableRegistry::get('Institution.InstitutionStudentAbsenceDetails');
        //$academicPeriodsId = $entity->academicPeriodId;
        if ( $institution_id > 0) {
            $where = [$StudentAbsences->aliasField('institution_id = ') => $institution_id];
        } else {
            $where = [];
        }

        if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
            $periodEntity = $AcademicPeriods->get($academicPeriodId);

            $startDate = $periodEntity->start_date->format('Y-m-d');
            $endDate = $periodEntity->end_date->format('Y-m-d');
        }

        $record = $StudentAbsences->find()
                ->select([
                    'student_id' => 'Users.id',
                    'openemis_no' => 'Users.openemis_no',
                    'student_name' => $Users->find()->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                    'institution_id' => 'Institutions.id',
                    'institution_name' => 'Institutions.name',
                    'institution_code' => 'Institutions.code',
                    'absence_type' => 'AbsenceTypes.name',
                    'date' => $StudentAbsences->aliasField('date'),
                    'institution_class_id' => 'InstitutionClasses.id',
                    'institution_class' => 'InstitutionClasses.name',
                    'education_grade_id' => $EducationGrades->aliasField('id'),
                    'education_grade' => $EducationGrades->aliasField('name'),
                    'gender' => $Genders->aliasField('name'),
                    'address' => 'Users.address',
                    'period' => $StudentAttendancePerDayPeriods->aliasField('name'),
                    'isPeriod' => $InstitutionStudentAbsenceDetails->aliasField('period'),
                    'isSubject' => $InstitutionStudentAbsenceDetails->aliasField('subject_id'),
                    'academic_period_id' => 'AcademicPeriods.id',
                    'guardian_name' => $GuardianUser->find()->func()->concat([
                        'GuardianUser.first_name' => 'literal',
                        " ",
                        'GuardianUser.last_name' => 'literal'
                    ])
                ]) 
                ->contain([
                    'AbsenceTypes',               
                    'Institutions.Areas.AreaLevels',
                    'Institutions.AreaAdministratives',
                    'InstitutionClasses'
                ])     
                ->leftJoin([$Users->alias() => $Users->table()],[
                        $Users->aliasField('id = ') . $StudentAbsences->aliasField('student_id')
                ])      
                ->leftJoin([$InstitutionClassGrades->alias() => $InstitutionClassGrades->table()],[
                        $InstitutionClassGrades->aliasField('institution_class_id = ') . $StudentAbsences->aliasField('institution_class_id')
                ])
                ->leftJoin([$EducationGrades->alias() => $EducationGrades->table()],[
                        $EducationGrades->aliasField('id = ') . $InstitutionClassGrades->aliasField('education_grade_id')
                ])
                ->leftJoin([$Genders->alias() => $Genders->table()], [
                        $Genders->aliasField('id = ') . $Users->aliasField('gender_id')
                ])
                ->leftJoin([$StudentGuardians->alias() => $StudentGuardians->table()], [
                        $StudentGuardians->aliasField('student_id = ') . $StudentAbsences->aliasField('student_id')
                ])
                ->leftJoin(['GuardianUser' => 'security_users'],[
                        'GuardianUser.id = '.$StudentGuardians->aliasField('guardian_id')
                ])
                ->leftJoin([$AcademicPeriods->alias() => $AcademicPeriods->table()], [
                        $AcademicPeriods->aliasField('id = ') . $StudentAbsences->aliasField('academic_period_id')
                ])
                ->leftJoin([$InstitutionStudentAbsenceDetails->alias() => $InstitutionStudentAbsenceDetails->table()], [
                        $InstitutionStudentAbsenceDetails->aliasField('student_id = ') . $StudentAbsences->aliasField('student_id')
                ])
                ->innerJoin([$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()], [
                        $StudentMarkTypeStatusGrades->aliasField('education_grade_id = ') . $InstitutionStudentAbsenceDetails->aliasField('education_grade_id')
                ])
                ->innerJoin([$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()], [
                        $StudentMarkTypeStatuses->aliasField('id = ') . $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id')
                ])
                ->innerJoin([$StudentAttendanceMarkTypes->alias() => $StudentAttendanceMarkTypes->table()], [
                    $StudentAttendanceMarkTypes->aliasField('id = ') . $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id')
                ])
                ->innerJoin([$StudentAttendancePerDayPeriods->alias() => $StudentAttendancePerDayPeriods->table()], [
                    $StudentAttendanceMarkTypes->aliasField('id = ') . $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id')
                ])
                ->where([
                    $StudentAbsences->aliasField('date >= ') => $startDate,
                    $StudentAbsences->aliasField('date <= ') => $endDate,
                    $where
                ])
                ->order([
                    $StudentAbsences->aliasField('student_id'),
                    $StudentAbsences->aliasField('institution_id'),
                    $StudentAbsences->aliasField('date')
                ]);
                //echo "<pre>";print_r($record);die();

            $result = [];
            if (!empty($record)) {
                foreach ($record as $key => $value) {
                    //echo "<pre>";print_r($value);die();
                    $stdId = $value->student_id;
                    $result[$key][] = $value->openemis_no;
                    $result[$key][] = $value->student_name;
                    $result[$key][] = $value->institution_name;
                    $result[$key][] = $value->institution_code;
                    $result[$key][] = date("d-m-Y", strtotime($value->date));
                    
                    if ($value->isPeriod !=0 && $value->isSubject == 0) {
                        $result[$key][] = $value->period;
                    } else {
                        $result[$key][] = '';
                    }
                    
                    /*//subject
                    $sub = [];
                    if (!empty($userData)) {
                        foreach ($userData as $k => $data) {
                            if ($data->period != 0 && $data->subject_id != 0) {
                                $gradeId = $data->education_grade_id;
                                $date = $data->date->format('Y-m-d');
                                $subjects = $StudentMarkTypeStatusGrades->find()
                                        ->select([$InstitutionSubjects->aliasField('name')])
                                        ->leftJoin([$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()], [
                                                $StudentMarkTypeStatuses->aliasField('id = ') . $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id')
                                        ])
                                        ->leftJoin([$StudentAttendanceMarkTypes->alias() => $StudentAttendanceMarkTypes->table()], [
                                                $StudentAttendanceMarkTypes->aliasField('id = ') . $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id')
                                        ])
                                        ->leftJoin([$StudentAttendanceMarkTypes->alias() => $StudentAttendanceMarkTypes->table()], [
                                                $StudentAttendanceMarkTypes->aliasField('id = ') . $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id')
                                        ])
                                        ->leftJoin([$InstitutionSubjectStudents->alias() => $InstitutionSubjectStudents->table()], [
                                            $InstitutionSubjectStudents->aliasField('education_grade_id = ') . $StudentMarkTypeStatusGrades->aliasField('education_grade_id')
                                        ])
                                        ->leftJoin([$InstitutionSubjects->alias() => $InstitutionSubjects->table()], [
                                            $InstitutionSubjects->aliasField('id = ') . $InstitutionSubjectStudents->aliasField('institution_subject_id')
                                        ])
                                        ->group([$InstitutionSubjects->aliasField('name')])
                                        ->where([
                                            $InstitutionSubjectStudents->aliasField('student_id') => $stdId,
                                            $StudentMarkTypeStatusGrades->aliasField('education_grade_id') => $gradeId,
                                            $StudentMarkTypeStatuses->aliasField('date_enabled >= ') => $date,
                                            $StudentMarkTypeStatuses->aliasField('date_disabled <= ') => $date
                                        ])->toArray();
                                //echo "<pre>";print_r($subjects);die();
                                if (!empty($subjects)) {
                                    foreach ($subjects as $vals) {
                                        //echo "<pre>";print_r($vals);die();
                                        $sub[] =  $vals['InstitutionSubjects']['name'];
                                    }
                                }
                            }
                        }
                    }
                    $subjectList = '';
                    if (isset($sub)) {
                        $subjectList  = implode(',', $sub);
                    } else {
                        $subjectList  = '';
                    }
                    $result[$key][] = $subjectList;*/
                    $result[$key][] = $value->absence_type;
                    $result[$key][] = $value->institution_class;
                    $result[$key][] = $value->education_grade;
                    $result[$key][] = $value->gender;
                    //user identity
                    $UserIdentities = TableRegistry::get('User.Identities');
                    $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
                    $identites = $UserIdentities->find()
                                    ->select([
                                        'identity_types' => $IdentityTypes->aliasField('name'),
                                        'identity_number' => $UserIdentities->aliasField('number'),
                                    ])
                                    ->leftJoin([$IdentityTypes->alias() => $IdentityTypes->table()], [
                                    $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id')
                                    ])
                                    ->where([
                                        $UserIdentities->aliasField('security_user_id') => $stdId,
                                        $IdentityTypes->aliasField('default') => 1
                                    ])
                                    ->first();
                    $result[$key][] = !empty($identites) ? $identites->identity_types : '';
                    $result[$key][] = !empty($identites) ? $identites->identity_number : '';
                    $result[$key][] = $value->address;
                    //User contacts
                    $UserContacts = TableRegistry::get('UserContacts');
                    $detail = $UserContacts->find()->select([$UserContacts->aliasField('value')])
                            ->where([$UserContacts->aliasField('security_user_id') => $stdId])->toArray();
                    $data = [];
                    if (!empty($detail)) {
                        foreach ($detail as $val) {
                            $data[] = $val['value'];
                        }
                    } 
                    $contact = '';
                    if (isset($data)) {
                        $contact  = implode(',', $data);
                    }
                    $result[$key][] = $contact;
                    $result[$key][] = $value->guardian_name;
                }
            }
            //echo "<pre>";print_r($result);die();
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
