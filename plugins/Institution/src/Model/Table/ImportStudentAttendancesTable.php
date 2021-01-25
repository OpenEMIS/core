<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use DateTime;
use PHPExcel_Worksheet;

class ImportStudentAttendancesTable extends AppTable {
    private $institutionId = false;

    public function initialize(array $config) {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'StudentAbsencesPeriodDetails']);
        $this->StudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $this->Students = TableRegistry::get('Institution.Students');
        $this->Users = TableRegistry::get('User.Users');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $this->StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
    }

    public function beforeAction($event) {
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
        $this->systemDateFormat = TableRegistry::get('Configuration.ConfigItems')->value('date_format');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportPopulateUsersData' => 'onImportPopulateUsersData',
            'Model.import.onImportPopulateAbsenceTypesData' => 'onImportPopulateAbsenceTypesData',
            'Model.import.onImportPopulateStudentAttendanceTypesData' => 'onImportPopulateStudentAttendanceTypesData',
            'Model.import.onImportPopulateSubjectData' => 'onImportPopulateSubjectData',
            'Model.import.onImportPopulatePeriodData' => 'onImportPopulatePeriodData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.import.onImportGetPeriodId' => 'onImportGetPeriodId',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $request = $this->request;
        if (empty($request->query('class'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $request = $this->request;
        unset($request->query['class']);
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->dependency = [];
        $this->dependency["class"] = ["select_file"];

        $this->ControllerAction->field('class', ['type' => 'select']);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['class', 'select_file']);

        //Assumption - onChangeReload must be named in this format: change<field_name>. E.g changeClass
        $currentFieldName = strtolower(str_replace("change", "", $entity->submit));

        if (isset($this->request->data[$this->alias()])) {

            $unsetFlag = false;
            $aryRequestData = $this->request->data[$this->alias()];

            foreach ($aryRequestData as $requestData => $value) {
                if (isset($this->dependency[$requestData]) && $value) {
                    $aryDependencies = $this->dependency[$requestData];
                    foreach ($aryDependencies as $dependency) {
                        $this->request->query = $this->request->data[$this->alias()];
                        $this->ControllerAction->field($dependency, ['visible' => true]);
                    }
                }
            }
        }
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {

            $tempRow['entity'] = $this->StudentAbsencesPeriodDetails->newEntity();
           
    }

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {}

    /**
     * Currently only populates students based on current academic period
     */
    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $currentPeriodId = $this->AcademicPeriods->getCurrent();
        if (!$currentPeriodId) {
            $array = $this->AcademicPeriods->getAvailableAcademicPeriods();
            reset($array);
            $currentPeriodId = key($array);
        }

        $classId = (!empty($this->request->query('class'))) ? $this->request->query('class') : '';

        if (!empty($classId)) {
            //Query to find all students from the selected classes in the institutions and academic period for absentee
            $currentPeriod = $this->AcademicPeriods->get($currentPeriodId);
            $allStudents = $this->Students
                                ->find('all')
                                ->select([
                                    'student_id',
                                    'EducationGrades.name','EducationGrades.order',
                                    'Users.first_name', 'Users.middle_name', 'Users.third_name', 'Users.last_name', 'Users.'.$lookupColumn
                                ])
                                ->where([
                                    $this->Students->aliasField('academic_period_id') => $currentPeriodId,
                                    $this->Students->aliasField('institution_id') => $this->institutionId,
                                    'InstitutionClassStudents.institution_class_id' => $classId,
                                    'Users.id IS NOT NULL',
                                ])
                                ->contain([
                                    'EducationGrades',
                                    'Users'
                                ])
                                ->join([
                                 'InstitutionClasseStudents' => [
                                     'table' => 'institution_class_students',
                                     'alias' => 'InstitutionClassStudents',
                                     'conditions' => 'InstitutionClassStudents.student_id = '.$this->Students->aliasField('student_id'),
                                 ],
                                ])
                                ->order(['EducationGrades.order']);
        }

        $institution = $this->Institutions->get($this->institutionId);
        $institutionHeader = $this->getExcelLabel('Imports', 'institution_id') . ": " . $institution->name;
        $periodHeader = $this->getExcelLabel($lookedUpTable, 'academic_period_id') . ": " . $currentPeriod->name;
        $gradeHeader = $this->getExcelLabel($lookedUpTable, 'education_grade_id');
        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 5;
        $data[$columnOrder]['data'][] = [
            $institutionHeader,
            $periodHeader,
            $gradeHeader,
            $nameHeader,
            $columnHeader
        ];
        if (!empty($allStudents)) {
            foreach($allStudents->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $institution->name,
                    $currentPeriod->name,
                    $row->education_grade->name,
                    $row->user->name,
                    $row->user->{$lookupColumn}
                ];
            }
        }
    }

    // public function onImportPopulateUsersDataBasedOnAllAcademicPeriods(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
    //  $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
    //  $editablePeriods = $this->AcademicPeriods->getAvailableAcademicPeriods();
    //  $allStudents = $this->Students
    //                      ->find('all')
    //                      ->select([
    //                          'EducationGrades.name', 'AcademicPeriods.name', 'AcademicPeriods.order', 'Users.id', 'Users.first_name', 'Users.middle_name', 'Users.third_name', 'Users.last_name', 'Users.'.$lookupColumn
    //                      ])
    //                      ->where([
    //                          $this->Students->aliasField('academic_period_id').' IN' => array_keys($editablePeriods),
    //                          $this->Students->aliasField('institution_id') => $this->institutionId
    //                      ])
    //                      ->contain([
    //                          'EducationGrades',
    //                          'AcademicPeriods',
    //                          'Users'
    //                      ])
    //                      ->order(['AcademicPeriods.order'])
    //                      ;
    //  $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
    //  $periodHeader = $this->getExcelLabel($lookedUpTable, 'academic_period_id');
    //  $gradeHeader = $this->getExcelLabel($lookedUpTable, 'education_grade_id');
    //  $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
    //  $data[$sheetName][] = [
    //      $nameHeader,
    //      $periodHeader,
    //      $gradeHeader,
    //      $columnHeader
    //  ];
    //  if (!empty($allStudents)) {
    //      foreach($allStudents->toArray() as $row) {
    //          $data[$sheetName][] = [
    //              $row->Users->name,
    //              $row->AcademicPeriods->name,
    //              $row->EducationGrades->name,
    //              $row->Users->{$lookupColumn}
    //          ];
    //      }
    //  }
    // }

    public function onImportPopulateSubjectData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $classId = !empty($this->request->query('class')) ? $this->request->query('class') : '';

        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $modelData = $InstitutionSubjects->getSubjectsByClass($classId);

        $nameHeader = $this->getExcelLabel($InstitutionSubjects, 'Subject');
        $columnHeader = $this->getExcelLabel($InstitutionSubjects, $lookupColumn);

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [
            $nameHeader,
            $columnHeader
        ];

        if (!empty($modelData)) {
            foreach($modelData as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->institution_subject->name,
                    $row->institution_subject->id
                ];
            }
        }

    }

    public function onImportPopulateStudentAttendanceTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')->select(['id', 'name', $lookupColumn]);

        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [
            $nameHeader,
            $columnHeader
        ];

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateAbsenceTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')->select(['id', 'name', $lookupColumn]);

        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [
            $nameHeader,
            $columnHeader
        ];

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulatePeriodData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {

        $classId = !empty($this->request->query('class')) ? $this->request->query('class') : '';
        $academicPeriodId = $this->AcademicPeriods->getCurrent();

        //Get the attendance per day that class needs to mark
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $attendancePerDay = $StudentAttendanceMarkTypes->getAttendancePerDayByClass($classId,$academicPeriodId);

        

        $nameHeader = $this->getExcelLabel($StudentAttendanceMarkTypes, 'Number of Periods');
        $columnHeader = $this->getExcelLabel($StudentAttendanceMarkTypes, 'Id');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [
            $nameHeader,
            $columnHeader
        ];


        //Set the select options in excel base on the number of attendance per day that class needs to mark
        if (!empty($attendancePerDay)) {
            foreach($attendancePerDay as $row) {
                $name = isset($row->name) ? $row->name : $row['name'];
                $id = isset($row->id) ? $row->id : $row['id'];
                $data[$columnOrder]['data'][] = [
                    $name,
                    $id
                ];
            }
        }

        
    }


    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {

        if (empty($tempRow['student_id'])) {
            $rowInvalidCodeCols['student_id'] = __('OpenEMIS ID was not defined');
            return false;
        }
        
        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }

        $tempRow['institution_id'] = $this->institutionId;
        $currentPeriodId = $this->AcademicPeriods->getCurrent();
        $tempRow['academic_period_id'] = $currentPeriodId;
        $classId = $this->request->query('class');
        $tempRow['institution_class_id'] = $classId;
       
        if (empty($tempRow['date'])) {
            $rowInvalidCodeCols['date'] = __('This field cannot be left empty');
            return false;
        } else {
            // from string to dateObject
            $formattedDate = DateTime::createFromFormat('d/m/Y', $tempRow['date']);
            $tempRow['date'] = $formattedDate;

            $periods = $this->getAcademicPeriodByStartDate($tempRow['date']);
            if (!$periods) {
                $rowInvalidCodeCols['date'] = __('No matching academic period based on the start date');
                $tempRow['academic_period_id'] = false;
                return false;
            }
            $periods = new Collection($periods);
            $periodIds = $periods->extract('id');
            $periodIds = $periodIds->toArray();
            if (!in_array($currentPeriodId, $periodIds)) {
                $currentPeriod = [];
                $currentPeriod =  $this->AcademicPeriods->get($currentPeriodId);
                $rowInvalidCodeCols['date'] = __('Date:- '.$tempRow['date']->format('d/m/Y').' is not within current academic year: '.$currentPeriod->name);
                $tempRow['academic_period_id'] = false;
                return false;
            }
            $tempRow['academic_period_id'] = $currentPeriodId;
        }

        $student = $this->Students->find()->where([
            'academic_period_id' => $tempRow['academic_period_id'],
            'institution_id' => $tempRow['institution_id'],
            'student_id' => $tempRow['student_id'],
        ])->first();

        if (!$student) {
            $rowInvalidCodeCols['student_id'] = __('No such student in the institution');
            $tempRow['student_id'] = false;
            return false;
        }
       

        //Check if period column is empty and value is within range of valid period
        if (empty($tempRow['period'])) {
            $rowInvalidCodeCols['period'] = __('This field cannot be left empty');
            return false;
        } else {
            //check if period within options
            $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
            $attendancePerDay = $StudentAttendanceMarkTypes->getAttendancePerDayByClass($classId,$currentPeriodId);

            if ($tempRow['period'] > $attendancePerDay || $tempRow['period'] < 1) {
                $rowInvalidCodeCols['period'] = __('Selected Period does not exists');
                return false;
            }
        }

        //If type is not EXCUSED and no absence reason was selected set to null
        if($tempRow['absence_type_id'] !=  1 && empty($tempRow['student_absence_reason_id'])) {
            $tempRow['student_absence_reason_id'] = NULL;
        }

        //add identifier that later will be used on StudentAbsencesPeriodDetails
        $tempRow['record_source'] = 'import_student_attendances';

        return true;
    }

    public function onImportGetPeriodId(Event $event, $cellValue)
    {
        return $cellValue;
    }

    public function onImportSetModelPassedRecord(Event $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow) {
        $flipped = array_flip($columns);
        $original = $originalRow->getArrayCopy();
        $key = $flipped['student_id'];
        $tempPassedRecord['data'][$key] = $original[$key];
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, Request $request) {
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();
            $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

            $userId = $this->Auth->user('id');
            $AccessControl = $this->AccessControl;


            $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
            $query = $this->InstitutionClasses->find();

            if (!$AccessControl->isAdmin()) {
                if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles)) {
                    $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
                    $query->innerJoin(['ClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                        'ClassesSecondaryStaff.institution_class_id = InstitutionClasses.id'
                    ]);
                    if (!$classPermission) {
                        $query->where(['1 = 0'], [], true);
                    } else {
                        $query->where([
                            'OR' => [
                                ['InstitutionClasses.staff_id' => $userId],
                                ['ClassesSecondaryStaff.secondary_staff_id' => $userId]
                            ]
                        ]);
                    }
                }
            }

            $classOptions = $query
                ->find('list')
                ->where([
                    $this->InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                    $this->InstitutionClasses->aliasField('institution_id') => $institutionId])
                ->group([
                    $this->InstitutionClasses->aliasField('id')
                ])
                ->toArray();

            $attr['options'] = $classOptions;
            // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeClass';
        }

        return $attr;
    }

}
