<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
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
use Cake\Http\ServerRequest;
use Cake\Http\Session;
// Events
// public function onExcelBeforeGenerate(EventInterface $event, ArrayObject $settings) {}
// public function onExcelGenerate(EventInterface $event, $writer, ArrayObject $settings) {}
// public function onExcelGenerateComplete(EventInterface $event, ArrayObject $settings) {}
// public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query) {}
// public function onExcelStartSheet(EventInterface $event, ArrayObject $settings, $totalCount) {}
// public function onExcelEndSheet(EventInterface $event, ArrayObject $settings, $totalProcessed) {}
// public function onExcelGetLabel(EventInterface $event, $column) {}

class SubjectExcelBehavior extends Behavior
{
    use EventTrait;

    private $events = [];
    private $_session;
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

    public function initialize(array $config): void
    {
        $this->getConfig('excludes', array_merge($this->getConfig('default_excludes'), $this->getConfig('excludes')));
        if (!isset($config['filename'])) {
            $this->setConfig('filename', $this->_table->getAlias());//POCOR-8324
        }
        $folder = WWW_ROOT . $this->getConfig('folder');

        if (!file_exists($folder)) {
            umask(0);
            mkdir($folder, 0777);
        } else {
            // $delete = true;
            // if (isset($settings['delete']) &&  $settings['delete'] == false) {
            //  $delete = false;
            // }
            // if ($delete) {
            //  $this->deleteOldFiles($folder, $format);
            // }
        }
        $pages = $this->getConfig('pages');
        if ($pages !== false && empty($pages)) {
            $this->getConfig('pages', ['index', 'view']);
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

    public function excelV4(EventInterface $mainEvent, ArrayObject $extra)
    {
        $id = 0;
        $break = false;
        $action = $this->_table->request->getParam('action');//POCOR-8324
        $pass = $this->_table->request->getParam('pass');//POCOR-8324
        if (in_array($action, $pass)) {
            unset($pass[array_search($action, $pass)]);
            $pass = array_values($pass);
        }
        if (isset($pass[0])) {
            $id = $pass[0];
        }
        $ids = empty((int)$id) ? [] : $this->_table->paramsDecode($id);//POCOR-8324
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
            'file' => $this->getConfig('filename') . '_' . date('Ymd') . 'T' . date('His') . '.xlsx',
            'path' => WWW_ROOT . $this->getConfig('folder') . DS,
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
            return $event->getResult();
        }
        if (is_callable($event->getResult())) {
            $generate = $event->getResult();
        }

        $generate($_settings);

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

    public function generate($settings = [])
    {
        $writer = $settings['writer'];
        $sheets = new ArrayObject();

        // Event to get the sheets. If no sheet is specified, it will be by default one sheet
        $event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeStart'), 'onExcelBeforeStart', [$settings, $sheets], true);

        if (count($sheets->getArrayCopy())==0) {
            $sheets[] = [
                'name' => $this->_table->getAlias(),
                'table' => $this->_table,
                'query' => $this->_table->find(),
            ];
        }

        $sheetNameArr = [];
        //POCOR-5852 starts
        //$session = $this->_table->request->getSession();
        //$institution_id = $session->read('Institution.Institutions.id') ? $session->read('Institution.Institutions.id'): 0;
        $request = $this->_table->request;//POCOR-8324
        $institutionId = $this->_table->paramsDecode($request->getAttribute('params')['pass'][1]);//POCOR-8324
        $institution_id = $institutionId ? $institutionId['institution_id']: 0;//POCOR-8324
        $class_id = $academic_period_id = '';
        $condition = [];
        if(!is_null($this->_table->request->getQuery()['class_id']) && !is_null($this->_table->request->getQuery()['academic_period_id'])){//POCOR-8324
            $class_id = $this->_table->request->getQuery()['class_id'];//POCOR-8324
            $academic_period_id = $this->_table->request->getQuery()['academic_period_id'];//POCOR-8324

            $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
            $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
            $conditions = [
                $InstitutionClassSubjects->aliasField('institution_class_id') => $class_id,
                $InstitutionSubjects->aliasField('InstitutionSubjects.academic_period_id') => $academic_period_id,
                $InstitutionSubjects->aliasField('InstitutionSubjects.institution_id') => $institution_id

            ];

        }
        //POCOR-5852 ends
        foreach ($sheets as $sheet) {
            $table = $sheet['table'];
            // sheet info added to settings to avoid adding more parameters to event
            $settings['sheet'] = $sheet;
            $this->getFields($table, $settings);
            $fields = $settings['sheet']['fields'];

            $footer = $this->getFooter();
            $Query = $sheet['query'];

			$EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
			$InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
			$InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
			$InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
			$InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
			$InstitutionSubjectStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');
			$InstitutionSubjectsRooms = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectsRooms');
            /**
            * added condition to make query on the bases on selected subject and exporting student's list
            * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
            * @ticket POCOR-6635 starts
            */
            $checkEncodedSubjectId = $this->_table->request->getAttribute('params')['pass'][1];//POCOR-8324
            $encodedSubjectId = $this->_table->paramsDecode($checkEncodedSubjectId);//POCOR-8324
            if (isset($encodedSubjectId['institution_subject_id'])) {//POCOR-8324
                //$decodedSubjectId = $this->_table->paramsDecode($encodedSubjectId);//POCOR-8324
                //$subjectId = $decodedSubjectId['id'];//POCOR-8324
                $decodedSubjectId = $encodedSubjectId['institution_subject_id'];
                $subjectId = $decodedSubjectId;
                $where[$InstitutionSubjects->aliasField('InstitutionSubjects.id')] = $subjectId;
                $query = $Query
                        ->select([
                            'academic_period_id' => 'InstitutionSubjects.academic_period_id',
                            'education_grade' => 'EducationGrades.name',
                            'institution_code' => 'Institutions.code',
                            'institution_name' => 'Institutions.name',
                            'Class_Name' => $InstitutionClasses->aliasField('name'),//POCOR-8324
                            'subject_name' => 'InstitutionSubjects.name',
                            'subject_code' => 'EducationSubjects.code',
                            'openEMIS_ID' => 'SubjectStudents.openemis_no',
                            'student_name' => $Query->func()->concat([
                                'SubjectStudents.first_name' => 'literal',
                                " ",
                                'SubjectStudents.last_name' => 'literal'
                            ]),
                            'gender' => 'Genders.name',
                            'institution_id' => 'Institutions.id',
                            'student_status' => 'StudentStatuses.name',//POCOR-6338
                        ])
                        ->contain([
                            'AcademicPeriods' => [
                                'fields' => [
                                    'AcademicPeriods.name'
                                ]
                            ],
                            'Institutions.Types',
                        ])
                        ->leftJoin(['EducationSubjects' => 'education_subjects'], [
                            'EducationSubjects.id =' . $InstitutionSubjects->aliasField('education_subject_id')
                        ])
                        ->leftJoin([$InstitutionClassSubjects->getAlias() => $InstitutionClassSubjects->getTable()], [
                            $InstitutionSubjects->aliasField('id =') . $InstitutionClassSubjects->aliasField('institution_subject_id')
                        ])
                        ->leftJoin([$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()], [
                            $InstitutionClassSubjects->aliasField('institution_class_id =') . $InstitutionClasses->aliasField('id')
                        ])
                        ->leftJoin(['EducationGrades' => 'education_grades'], [
                            $InstitutionSubjects->aliasField('education_grade_id ='). $EducationGrades->aliasField('id')
                        ])
                        ->leftJoin(['InstitutionSubjectStudents' => 'institution_subject_students'], [
                            'InstitutionSubjectStudents.institution_subject_id = '. $InstitutionSubjects->aliasField('id')
                        ])
                        ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                            'StudentStatuses.id = InstitutionSubjectStudents.student_status_id'
                        ])
                        ->leftJoin(['SubjectStudents' => 'security_users'], [
                            'SubjectStudents.id = '. $InstitutionStudents->aliasField('student_id')
                        ])
                        ->leftJoin(['Genders' => 'genders'], [
                            'SubjectStudents.gender_id = Genders.id'
                        ])
                        ->where([$conditions, $where]);
            } else {
                $query = $Query
                        ->select([
                            'academic_period_id' => 'InstitutionSubjects.academic_period_id',
                            'education_grade' => 'EducationGrades.name',
                            'institution_code' => 'Institutions.code',
                            'institution_name' => 'Institutions.name',
                            'Class_Name' => $InstitutionClasses->aliasField('name'),//POCOR-8324
                            'subject_name' => 'InstitutionSubjects.name',
                            'subject_code' => 'EducationSubjects.code',
                            'openEMIS_ID' => 'SubjectStudents.openemis_no',
                            'student_name' => $Query->func()->concat([
                                'SubjectStudents.first_name' => 'literal',
                                " ",
                                'SubjectStudents.last_name' => 'literal'
                            ]),
                            'teachers' => $Query->func()->group_concat([
                                'SubjectTeachers.openemis_no' => 'literal',
                                " - ",
                                'SubjectTeachers.first_name' => 'literal',
                                " ",
                                'SubjectTeachers.last_name' => 'literal'
                            ]),
                            'rooms' => $Query->func()->group_concat([
                                'SubjectRooms.code' => 'literal',
                                " - ",
                                'SubjectRooms.name' => 'literal'
                            ]),
                            'gender' => 'Genders.name',
                            'institution_id' => 'Institutions.id',
                            'student_status' => 'StudentStatuses.name',//POCOR-6338
                        ])
                        ->contain([
                            'AcademicPeriods' => [
                                'fields' => [
                                    'AcademicPeriods.name'
                                ]
                            ],
                            'Institutions.Types',
                        ])
                        ->leftJoin(['EducationSubjects' => 'education_subjects'], [
                            'EducationSubjects.id =' . $InstitutionSubjects->aliasField('education_subject_id')
                        ])
                        ->leftJoin([$InstitutionClassSubjects->getAlias() => $InstitutionClassSubjects->getTable()], [
                            $InstitutionSubjects->aliasField('id =') . $InstitutionClassSubjects->aliasField('institution_subject_id')
                        ])
                        ->leftJoin([$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()], [
                            $InstitutionClassSubjects->aliasField('institution_class_id =') . $InstitutionClasses->aliasField('id')
                        ])
                        ->leftJoin(['EducationGrades' => 'education_grades'], [
                            $InstitutionSubjects->aliasField('education_grade_id ='). $EducationGrades->aliasField('id')
                        ])
                        ->leftJoin(['InstitutionSubjectStudents' => 'institution_subject_students'], [
                            'InstitutionSubjectStudents.institution_subject_id = '. $InstitutionSubjects->aliasField('id')
                        ]) // POCOR-6338 starts
                        ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                            'StudentStatuses.id = InstitutionSubjectStudents.student_status_id'
                        ])//POCOR-6338 ends
                        ->leftJoin(['SubjectStudents' => 'security_users'], [
                            'SubjectStudents.id = '. $InstitutionStudents->aliasField('student_id')
                        ])
                        ->leftJoin([$InstitutionSubjectStaff->getAlias() => $InstitutionSubjectStaff->getTable()], [
                            $InstitutionSubjects->aliasField('id =') . $InstitutionSubjectStaff->aliasField('institution_subject_id')
                        ])
                        ->leftJoin(['SubjectTeachers' => 'security_users'], [
                            'SubjectTeachers.id = '. $InstitutionSubjectStaff->aliasField('staff_id')
                        ])
                        ->leftJoin([$InstitutionSubjectsRooms->getAlias() => $InstitutionSubjectsRooms->getTable()], [
                            $InstitutionSubjects->aliasField('id =') . $InstitutionSubjectsRooms->aliasField('institution_subject_id')
                        ])
                        ->leftJoin(['SubjectRooms' => 'institution_rooms'], [
                            'SubjectRooms.id = '. $InstitutionSubjectsRooms->aliasField('institution_room_id')
                        ])
                        ->leftJoin(['Genders' => 'genders'], [
                            'SubjectStudents.gender_id = Genders.id'
                        ])
                        ->where($conditions)//POCOR-5852
                        ->order([
                            'AcademicPeriods.order',
                            'Institutions.code',
                            'InstitutionSubjects.id'
                        ]);

                    if($table->alias!='Subjects'){
                        $query->group([
                            'SubjectStudents.id'
                        ]);
                    }
            }
                $Query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
					return $results->map(function ($row) {
						$teachers = explode(',',$row['teachers']);
						$teachers = array_unique($teachers);
						$teachers = implode(', ',$teachers);
						$row['teachers'] = $teachers;

						$rooms = explode(',',$row['rooms']);
						$rooms = array_unique($rooms);
						$rooms = implode(', ',$rooms);
						$row['rooms'] = $rooms;
						return $row;
					});
				});
                //POCOR-5852 starts
                $Query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                    return $results->map(function ($row) {
                        $openemisId = $row->openEMIS_ID;//POCOR-8324 starts
                        if ($openemisId !== null) {
                            $Users = TableRegistry::getTableLocator()->get('Security.Users');
                            $user_data= $Users
                                        ->find()
                                        ->where([$Users->aliasField('openemis_no') => $row->openEMIS_ID])
                                        ->first();
                            $UserIdentities = TableRegistry::getTableLocator()->get('User.Identities');//POCOR-5852 starts
                            $IdentityTypes = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');//POCOR-5852 ends
                            $conditions = [
                                $UserIdentities->aliasField('security_user_id') => $user_data->id,
                            ];
                            $data = $UserIdentities
                                        ->find()
                                        ->select([
                                            // 'identity_type' => $IdentityTypes->getAlias().'.name',//POCOR-5852 starts
                                            // 'identity_number' => $UserIdentities->getAlias().'.number',
                                            // '"default"' => $IdentityTypes->getAlias().'.default' // Note the double quotes to escape 'default'
                                            'identity_type' => $IdentityTypes->aliasField('name'),
                                            'identity_number' => $UserIdentities->aliasField('number'),
                                            '"default"' => $IdentityTypes->aliasField('default')
                                            //POCOR-5852 ends
                                        ])
                                        ->leftJoin(
                                            [$IdentityTypes->getAlias() => $IdentityTypes->getTable()],
                                            [
                                                $IdentityTypes->aliasField('id = '). $UserIdentities->aliasField('identity_type_id')
                                            ]
                                        )
                                        ->where($conditions)
                                        ->toArray();
                            $row['identity_type'] = '';
                            $row['identity_number'] = '';
                            if(!empty($data)){
                                $identity_type_name = '';
                                $identity_type_number = '';
                                foreach ($data as $key => $value) {
                                    if($value->default == 1){
                                    $identity_type_name =  $value->identity_type;
                                    $identity_type_number =  $value->identity_number;
                                    break;
                                    }
                                }
                                if(!empty($identity_type_name) && !empty($identity_type_number)){
                                    $row['identity_type'] = $identity_type_name;
                                    $row['identity_number'] = $identity_type_number;
                                }else{
                                    $row['identity_type'] = $data[0]->identity_type;
                                    $row['identity_number'] = $data[0]->identity_number;
                                }
                            }
                        }else{
                            $user_data = null;
                            $row['identity_type'] = '';
                            $row['identity_number'] = '';
                        }//POCOR-8324 ends
                        return $row;
                    });
                });
				//POCOR-5852 ends
            $this->dispatchEvent($table, $this->eventKey('onExcelBeforeQuery'), 'onExcelBeforeQuery', [$settings, $query], true);
            $sheetName = $sheet['name'];

            // Check to make sure the string length does not exceed 31 characters
            $sheetName = (strlen($sheetName) > 31) ? substr($sheetName, 0, 27).'....' : $sheetName;

            // Check to make sure that no two sheets has the same name
            $counter = 1;
            $initialLength = 0;
            while (in_array($sheetName, $sheetNameArr)) {
                if ($counter > 1) {
                    $sheetName = substr($sheetName, 0, $initialLength);
                } else {
                    $initialLength = strlen($sheetName);
                }
                if (strlen($sheetName) > 23) {
                    $sheetName = substr($sheetName, 0, 23).'('.$counter++.')';
                } else {
                    $sheetName = $sheetName.'('.$counter++.')';
                }
            }
            $sheetNameArr[] = $sheetName;
            $baseSheetName = $sheetName;

            // if the primary key of the record is given, only generate that record
            if (isset($settings['id'])) {
                $id = $settings['id'];
                if ($id != 0) {
                    $primaryKey = $table->getPrimaryKey();
                    $query->where([$table->aliasField($primaryKey) => $id]);
                }
            }

            if ($this->getConfig('auto_contain')) {
                $this->contain($query, $fields, $table);
            }

            // To auto include the default fields. Using select will turn off autoFields by default
            // This is set so that the containable data will still be in the array.
            /* //POCOR-5852 starts
            $autoFields = $this->config('autoFields');

            if (!isset($autoFields) || $autoFields == true) {
                $query->autoFields(true);
            } //POCOR-5852 ends*/

            $count = $query->count();
            $rowCount = 0;
            $sheetCount = 1;
            $sheetRowCount = 0;
            $percentCount = intval($count / 100);
            $pages = ceil($count / $this->getConfig('limit'));

            // Debugging
            $pages = 1;

            if (isset($sheet['orientation'])) {
                if ($sheet['orientation'] == 'landscape') {
                    $this->getConfig('orientation', 'landscape');
                } else {
                    $this->getConfig('orientation', 'portrait');
                }
            } elseif ($count == 1) {
                $this->getConfig('orientation', 'portrait');
            }

            $this->dispatchEvent($table, $this->eventKey('onExcelStartSheet'), 'onExcelStartSheet', [$settings, $count], true);
            $this->onEvent($table, $this->eventKey('onExcelBeforeWrite'), 'onExcelBeforeWrite');
            if ($this->getConfig('orientation') == 'landscape') {
                $headerRow = [];
                $headerStyle = [];
                $headerFormat = [];

                // Handling of Group field for merging cells for first 2 row
                $groupList = Hash::extract($fields, '{n}.group');
                $hasGroupRow = (!empty($groupList));

                if ($hasGroupRow) {
                    $subjectsHeaderRow = [];
                    $subjectsColWidth = [];
                    $groupStartingIndex = 0;
                    $groupName = '';
                    $subjectHeaderstyle = ['halign' => 'center'];

                    foreach ($fields as $index => $attr) {
                        $subjectsHeaderRow[$index] = "";

                        if (isset($attr['group'])) {
                            if ($groupName !== $attr['group']) {
                                $groupStartingIndex = $index;
                                $groupName = $attr['group'];
                            }

                            $groupKey = $groupName . $groupStartingIndex;

                            if (!array_key_exists($groupKey, $subjectsColWidth)) {
                                $subjectsColWidth[$groupKey] = [];
                                $subjectsColWidth[$groupKey]['start_col'] = $index;
                                $subjectsHeaderRow[$index]  = $attr['group'];
                            }

                            $subjectsColWidth[$groupKey]['end_col'] = $index;

                        } else {
                            $groupName = '';
                        }
                    }

                    $writer->writeSheetRow($sheetName, $subjectsHeaderRow, $subjectHeaderstyle);

                    foreach ($subjectsColWidth as $obj) {
                        $writer->markMergedCell($sheetName, $start_row=0, $start_col=$obj['start_col'], $end_row=0, $end_col=$obj['end_col']);
                    }
                }
                // End of handling of Group field for merging cells

                foreach ($fields as $attr) {
                    $headerRow[] = $attr['label'];
                    $headerStyle[] = isset($attr['style']) ? $attr['style'] : [];
                    $headerFormat[] = isset($attr['formatting']) ? $attr['formatting'] : 'GENERAL';
                }

                // Any additional custom headers that require to be appended on the right side of the sheet
                // Header column count must be more than the additional data columns
                if (isset($sheet['additionalHeader'])) {
                    $headerRow = array_merge($headerRow, $sheet['additionalHeader']);
                }

                $writer->writeSheetHeader($sheetName, $headerFormat, true);
                $writer->writeSheetRow($sheetName, $headerRow, $headerStyle);

                $this->dispatchEvent($table, $this->eventKey('onExcelAfterHeader'), 'onExcelAfterHeader', [$settings], true);

                // process every page based on the limit
                for ($pageNo=0; $pageNo<$pages; $pageNo++) {
                    $resultSet = $query
                    ->limit($this->getConfig('limit'))
                    ->page($pageNo+1)
                    ->all();

                    // Data to be appended on the right of spreadsheet
                    $additionalRows = [];
                    if (isset($sheet['additionalData'])) {
                        $additionalRows = $sheet['additionalData'];
                    }

                    // process each row based on the result set
                    foreach ($resultSet as $entity) {
                        if ($sheetRowCount >= $this->getConfig('sheet_limit')) {
                            $sheetCount++;
                            $sheetName = $baseSheetName . '_' . $sheetCount;

                            // rewrite header into new sheet
                            $writer->writeSheetRow($sheetName, $headerRow, $headerStyle);

                            $sheetRowCount= 0;
                        }

                        $settings['entity'] = $entity;

                        $row = [];
                        $rowStyle = [];
                        foreach ($fields as $attr) {
                            $rowDataWithStyle = $this->getValue($entity, $table, $attr);
                            $row[] = $rowDataWithStyle['rowData'];
                            $rowStyle[] = $rowDataWithStyle['style'];
                        }

                        $sheetRowCount++;
                        $rowCount++;
                        $event = $this->dispatchEvent($table, $this->eventKey('onExcelBeforeWrite'), null, [$settings, $rowCount, $percentCount]);
                        if (!$event->getResult()) {
                            $writer->writeSheetRow($sheetName, $row, $rowStyle);
                        }
                    }
                }
            } else {
                $entity = $query->first();
                foreach ($fields as $attr) {
                    $row = [$attr['label']];
                    $rowStyle = [[]];
                    $rowDataWithStyle = $this->getValue($entity, $table, $attr);
                    $row[] = $rowDataWithStyle['rowData'];
                    $rowStyle[] = $rowDataWithStyle['style'];
                    $writer->writeSheetRow($sheetName, $row, $rowStyle);
                }

                // Any additional custom headers that require to be appended on the left column of the sheet
                $additionalHeader = [];
                if (isset($sheet['additionalHeader'])) {
                    $additionalHeader = $sheet['additionalHeader'];
                }
                // Data to be appended on the right column of spreadsheet
                $additionalRows = [];
                if (isset($sheet['additionalData'])) {
                    $additionalRows = $sheet['additionalData'];
                }

                for ($i = 0; $i < count($additionalHeader); $i++) {
                    $row = [$additionalHeader[$i]];
                    $row[] = $additionalRows[$i];
                    $rowStyle = [[], []];
                    $writer->writeSheetRow($sheetName, $row, $rowStyle);
                }
                $rowCount++;
            }
            $writer->writeSheetRow($sheetName, ['']);
            $writer->writeSheetRow($sheetName, $footer);
            $this->dispatchEvent($table, $this->eventKey('onExcelEndSheet'), 'onExcelEndSheet', [$settings, $rowCount], true);
        }
    }

    private function getFields($table, $settings)
    {
        $schema = $table->getSchema();
        //$columns = $schema->columns();
        //POCOR-5852 added 'identity_type', 'identity_number' starts
		$columns = ['institution_code','institution_name','academic_period_id',
					'Class_Name','education_grade','subject_name','subject_code',
					'teachers','rooms','openEMIS_ID','student_name',
					'gender','student_status', 'identity_type', 'identity_number'
					];
        //POCOR-5852 ends
        $excludes = $this->getConfig('excludes');

        if (!is_array($table->getPrimaryKey())) { //if not composite key
            $excludes[] = $table->getPrimaryKey();
        }

        $fields = new ArrayObject();
        $module = $table->getAlias();
        $language = I18n::getLocale();
        $excludedTypes = ['binary'];
        /*POCOR-6635 starts - added condition to export individual subject with student's list*/
        //$encodedSubjectId = $this->_table->request->getAttribute('params')['pass'][1];//POCOR-8324
        $checkEncodedSubjectId = $this->_table->request->getAttribute('params')['pass'][1];//POCOR-8324
        $encodedSubjectId = $this->_table->paramsDecode($checkEncodedSubjectId);//POCOR-8324
        if (isset($encodedSubjectId['institution_subject_id'])) {//POCOR-8324
            $columns = ['institution_code', 'institution_name', 'academic_period_id', 'Class_Name', 'education_grade', 'subject_name','subject_code', 'teachers', 'rooms', 'openEMIS_ID', 'student_name', 'gender', 'student_status'];
        } else {
            $columns = array_diff($columns, $excludes);
        }
        /*POCOR-6635 ends */

        foreach ($columns as $col) {
            $field = $schema->getColumn($col);
            if (!in_array($field['type'], $excludedTypes)) {
                $label = $table->aliasField($col);

                $event = $this->dispatchEvent($table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, $col, $language], true);
                if (strlen($event->getResult())) {
                    $label = $event->getResult();
                }

                $fields[] = [
                    'key' => '',
                    'field' => $col,
                    'type' => 'string',
                    'label' => $label,
                    'style' => [],
                    'formatting' => 'GENERAL'
                ];
            }
        }

        // Event to add or modify the fields to fetch from the table
        $event = $this->dispatchEvent($table, $this->eventKey('onExcelUpdateFields'), 'onExcelUpdateFields', [$settings, $fields], true);

        $newFields = [];
        foreach ($fields->getArrayCopy() as $field) {
            if (empty($field['label'])) {
                $key = explode('.', $field['key']);
                $module = $key[0];
                $column = $key[1];
                // Redispatch get label
                $event = $this->dispatchEvent($table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, $column, $language], true);
                if (strlen($event->getResult())) {
                    $field['label'] = $event->getResult();
                }
            }
            $newFields[] = $field;
        }

        // Replace the ArrayObject with the new fields
        $fields->exchangeArray($newFields);

        // Add the fields into the sheet
        $settings['sheet']['fields'] = $fields;
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
                if ($event->getResult()) {
                    $returnedResult = $event->getResult();
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
                if ($event->getResult()) {
                    $returnedResult = $event->getResult();
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
                if ($field === $assoc->getForeignKey()) {
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
                if ($field === $assoc->getForeignKey()) {
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
            $key = Inflector::underscore(Inflector::singularize($tableObj->getAlias()));
        }
        return $key;
    }

    private function contain(Query $query, $fields, $table)
    {
        $contain = [];
        foreach ($fields as $attr) {
            $field = $attr['field'];
            if ($this->isForeignKey($table, $field)) {
                $contain[] = $this->getAssociatedTable($table, $field)->getAlias();
            }
        }
        $query->contain($contain);
    }

    private function download($path)
    {
        $filename = basename($path);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=".$filename);
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($path));
        readfile($path);
        exit; // better to use exit than die
    }


    private function purge($path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function implementedEvents(): array
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

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $action = $this->_table->action;
        //POCOR-5852 starts add  || $action == 'index' condition
        if (in_array($action, $this->getConfig('pages')) || $action == 'index') {
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
        //POCOR-5852 ends
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
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

            $pages = $this->getConfig('pages');
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

            $pages = $this->getConfig('pages');
            if ($pages != false) {
                if (in_array($action, $pages)) {
                    $toolbarButtons['export'] = $export;
                }
            }
        }
    }
}
