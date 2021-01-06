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
use Cake\Network\Request;
use Cake\Network\Session;
// Events
// public function onExcelBeforeGenerate(Event $event, ArrayObject $settings) {}
// public function onExcelGenerate(Event $event, $writer, ArrayObject $settings) {}
// public function onExcelGenerateComplete(Event $event, ArrayObject $settings) {}
// public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {}
// public function onExcelStartSheet(Event $event, ArrayObject $settings, $totalCount) {}
// public function onExcelEndSheet(Event $event, ArrayObject $settings, $totalProcessed) {}
// public function onExcelGetLabel(Event $event, $column) {}

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
                'name' => $this->_table->alias(),
                'table' => $this->_table,
                'query' => $this->_table->find(),
            ];
        }

        $sheetNameArr = [];
        //POCOR-5852 starts
        $session = $this->_table->request->session();
        $institution_id = $session->read('Institution.Institutions.id') ? $session->read('Institution.Institutions.id'): 0;
        $class_id = $academic_period_id = '';
        $condition = [];
        if(isset($this->_table->request->query['class_id']) && isset($this->_table->request->query['academic_period_id'])){
            $class_id = $this->_table->request->query['class_id'];
            $academic_period_id = $this->_table->request->query['academic_period_id'];

            $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
            $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
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
			
			$EducationGrades = TableRegistry::get('Education.EducationGrades');
			$InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
			$InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
			$InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
			$InstitutionStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
			$InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
			$InstitutionSubjectsRooms = TableRegistry::get('Institution.InstitutionSubjectsRooms');
		
			$query = $Query
				->select([
					'academic_period_id' => 'InstitutionSubjects.academic_period_id',
					'education_grade' => 'EducationGrades.name',
					'institution_code' => 'Institutions.code',
					'institution_name' => 'Institutions.name',
					'Class_Name' => $InstitutionClasses->alias().'.name',
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
					'student_status' => 'StudentStatuses.name',
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
				->leftJoin([$InstitutionClassSubjects->alias() => $InstitutionClassSubjects->table()], [
					$InstitutionSubjects->aliasField('id =') . $InstitutionClassSubjects->aliasField('institution_subject_id')
				])
				->leftJoin([$InstitutionClasses->alias() => $InstitutionClasses->table()], [
					$InstitutionClassSubjects->aliasField('institution_class_id =') . $InstitutionClasses->aliasField('id')
				])
				->leftJoin(
				['EducationGrades' => 'education_grades'],
				[
					$InstitutionSubjects->aliasField('education_grade_id ='). $EducationGrades->aliasField('id')
				]
				) 
				->leftJoin(
				['InstitutionSubjectStudents' => 'institution_subject_students'],
				[
					'InstitutionSubjectStudents.institution_subject_id = '. $InstitutionSubjects->aliasField('id')
				]
				) 
				->leftJoin(
				['StudentStatuses' => 'student_statuses'],
				[
					'StudentStatuses.id = InstitutionSubjectStudents.student_status_id'
				]
				)
				->leftJoin(
				['SubjectStudents' => 'security_users'],
				[
					'SubjectStudents.id = '. $InstitutionStudents->aliasField('student_id')
				]
				)
				->leftJoin([$InstitutionSubjectStaff->alias() => $InstitutionSubjectStaff->table()], [
					$InstitutionSubjects->aliasField('id =') . $InstitutionSubjectStaff->aliasField('institution_subject_id')
				])
				->leftJoin(['SubjectTeachers' => 'security_users'], [
					'SubjectTeachers.id = '. $InstitutionSubjectStaff->aliasField('staff_id')
				])
				->leftJoin([$InstitutionSubjectsRooms->alias() => $InstitutionSubjectsRooms->table()], [
					$InstitutionSubjects->aliasField('id =') . $InstitutionSubjectsRooms->aliasField('institution_subject_id')
				])
				->leftJoin(['SubjectRooms' => 'institution_rooms'], [
					'SubjectRooms.id = '. $InstitutionSubjectsRooms->aliasField('institution_room_id')
				])
				->leftJoin(
				['Genders' => 'genders'],
				[
					'SubjectStudents.gender_id = Genders.id'
				]
				)
                //POCOR-5852 starts
                ->where($conditions)
                //POCOR-5852 ends
				->group([
					'SubjectStudents.id'
				])
				->order([
					'AcademicPeriods.order',
					'Institutions.code',
					'InstitutionSubjects.id'
				]);
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
                        $Users = TableRegistry::get('security_users');
                        $user_data= $Users
                                    ->find()
                                    ->where(['security_users.openemis_no' => $row->openEMIS_ID])
                                    ->first();
                        $UserIdentities = TableRegistry::get('user_identities');//POCOR-5852 starts
                        $IdentityTypes = TableRegistry::get('identity_types');//POCOR-5852 ends
                        $conditions = [
                            $UserIdentities->aliasField('security_user_id') => $user_data->id,
                        ];
                        $data = $UserIdentities
                                    ->find()    
                                    ->select([
                                        'identity_type' => $IdentityTypes->alias().'.name',//POCOR-5852 starts
                                        'identity_number' => $UserIdentities->alias().'.number',
                                        'default' => $IdentityTypes->alias().'.default'
                                        //POCOR-5852 ends
                                    ])
                                    ->leftJoin(
                                    [$IdentityTypes->alias() => $IdentityTypes->table()],
                                        [
                                            $IdentityTypes->aliasField('id = '). $UserIdentities->aliasField('identity_type_id')
                                        ]
                                    )
                                    ->where($conditions)->toArray();
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
            if (array_key_exists('id', $settings)) {
                $id = $settings['id'];
                if ($id != 0) {
                    $primaryKey = $table->primaryKey();
                    $query->where([$table->aliasField($primaryKey) => $id]);
                }
            }

            if ($this->config('auto_contain')) {
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
            $pages = ceil($count / $this->config('limit'));

            // Debugging 
            $pages = 1;

            if (isset($sheet['orientation'])) {
                if ($sheet['orientation'] == 'landscape') {
                    $this->config('orientation', 'landscape');
                } else {
                    $this->config('orientation', 'portrait');
                }
            } elseif ($count == 1) {
                $this->config('orientation', 'portrait');
            }

            $this->dispatchEvent($table, $this->eventKey('onExcelStartSheet'), 'onExcelStartSheet', [$settings, $count], true);
            $this->onEvent($table, $this->eventKey('onExcelBeforeWrite'), 'onExcelBeforeWrite');
            if ($this->config('orientation') == 'landscape') {
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

                        if (array_key_exists('group', $attr)) {
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
                    ->limit($this->config('limit'))
                    ->page($pageNo+1)
                    ->all();

                    // Data to be appended on the right of spreadsheet
                    $additionalRows = [];
                    if (isset($sheet['additionalData'])) {
                        $additionalRows = $sheet['additionalData'];
                    }

                    // process each row based on the result set
                    foreach ($resultSet as $entity) {
                        if ($sheetRowCount >= $this->config('sheet_limit')) {
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
                        if (!$event->result) {
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
        $schema = $table->schema();
        //$columns = $schema->columns();
        //POCOR-5852 added 'identity_type', 'identity_number' starts
		$columns = ['institution_code','institution_name','academic_period_id',
					'Class_Name','education_grade','subject_name','subject_code',
					'teachers','rooms','openEMIS_ID','student_name',
					'gender','student_status', 'identity_type', 'identity_number'
					];
        //POCOR-5852 ends            
        $excludes = $this->config('excludes');

        if (!is_array($table->primaryKey())) { //if not composite key
            $excludes[] = $table->primaryKey();
        }

        $fields = new ArrayObject();
        $module = $table->alias();
        $language = I18n::locale();
        $excludedTypes = ['binary'];
        $columns = array_diff($columns, $excludes);

        foreach ($columns as $col) {
            $field = $schema->column($col);
            if (!in_array($field['type'], $excludedTypes)) {
                $label = $table->aliasField($col);

                $event = $this->dispatchEvent($table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, $col, $language], true);
                if (strlen($event->result)) {
                    $label = $event->result;
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
                if (strlen($event->result)) {
                    $field['label'] = $event->result;
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
        //POCOR-5852 starts add  || $action == 'index' condition
        if (in_array($action, $this->config('pages')) || $action == 'index') {
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
