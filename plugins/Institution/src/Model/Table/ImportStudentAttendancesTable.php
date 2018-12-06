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

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'InstitutionStudentAbsences']);

        $this->StudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $this->Students = TableRegistry::get('Institution.Students');
        $this->Users = TableRegistry::get('User.Users');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
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
            'Model.import.onImportPopulateStudentAttendanceMarkTypesData' => 'onImportPopulateStudentAttendanceMarkTypesData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
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

        //Assumptiopn - onChangeReload must be named in this format: change<field_name>. E.g changeClass
        $currentFieldName = strtolower(str_replace("change", "", $entity->submit));

        if (isset($this->request->data[$this->alias()])) {

            $unsetFlag = false;
            $aryRequestData = $this->request->data[$this->alias()];

            foreach ($aryRequestData as $requestData => $value) {
                if ($unsetFlag) {
                    unset($this->request->query[$requestData]);
                    $this->request->data[$this->alias()][$requestData] = 0;
                }

                if ($currentFieldName == str_replace("_", "", $requestData)) {
                    $unsetFlag = true;
                }
            }

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
        $tempRow['entity'] = $this->StudentAbsences->newEntity();
        $tempRow['full_day'] = 1;
        $tempRow['institution_id'] = false;
        $tempRow['academic_period_id'] = false;
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
                                     // 'type' => 'LEFT',
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

    public function onImportPopulateStudentAttendanceMarkTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {

        $classId = !empty($this->request->query('class')) ? $this->request->query('class') : '';
        $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');
        $academicPeriodId = $this->AcademicPeriods->getCurrent();

        //Get education grade id
        $educationGradeId = $this->InstitutionClassStudents->find()
                                ->select([$this->InstitutionClassStudents->aliasField('education_grade_id')])
                                ->where([
                                    $this->InstitutionClassStudents->aliasField('institution_class_id') => $classId,
                                    $this->InstitutionClassStudents->aliasField('academic_period_id') => $academicPeriodId,
                                    $this->InstitutionClassStudents->aliasField('institution_id') => $institutionId
                                ])
                                ->first();

        //select from student_attendance_mark_types based on the education_grade_id and academic_period_id to get attendance_per_day which the periods
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
                        ->select(['attendance_per_day'])
                        ->where([
                            $lookedUpTable->aliasField('education_grade_id') => $educationGradeId->education_grade_id,
                            $lookedUpTable->aliasField('academic_period_id') => $academicPeriodId
                        ])
                        ->first();

        $nameHeader = $this->getExcelLabel($lookedUpTable, 'Number of Periods');
        // $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 1;
        $data[$columnOrder]['data'][] = [
            'Number of Periods'
        ];

        // pr($modelData); die;
        // $value;

        $value = !empty($modelData) ? $modelData->attendance_per_day : 1;
        // if (empty($modelData)) {
        //     $value = 1;
        // } else {
        //     $value =  $modelData->attendance_per_day;
        // }

        for ($i = 1; $i <= $value; $i++) {
            $data[$columnOrder]['data'][] = [$i];
        }
        // pr($data[$columnOrder]['data']); die;
        // $data[$columnOrder]['data'][] = 1;
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
        if (!$currentPeriodId) {
            $array = $this->AcademicPeriods->getAvailableAcademicPeriods();
            reset($array);
            $currentPeriodId = key($array);
        }
        $isEditable = $this->AcademicPeriods->getAvailableAcademicPeriods($currentPeriodId);
        if (!$isEditable) {
            $rowInvalidCodeCols['academic_period_id'] = __('No data changes can be made for the current academic period');
            $tempRow['academic_period_id'] = false;
            return false;
        }

        if (empty($tempRow['start_date'])) {
            $rowInvalidCodeCols['start_date'] = __('This field cannot be left empty');
            return false;
        } else {
            // from string to dateObject
            $formattedDate = DateTime::createFromFormat('d/m/Y', $tempRow['start_date']);
            $tempRow['start_date'] = $formattedDate;

            $periods = $this->getAcademicPeriodByStartDate($tempRow['start_date']);
            if (!$periods) {
                $rowInvalidCodeCols['academic_period_id'] = __('No matching academic period based on the start date');
                $tempRow['academic_period_id'] = false;
                return false;
            }
            $periods = new Collection($periods);
            $periodIds = $periods->extract('id');
            $periodIds = $periodIds->toArray();
            if (!in_array($currentPeriodId, $periodIds)) {
                $rowInvalidCodeCols['academic_period_id'] = __('Date is not within current academic period');
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

        return true;
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

                    if (!$classPermission) {
                        $query->where(['1 = 0'], [], true);
                    } else {
                        $query->where([
                            'OR' => [
                                ['InstitutionClasses.staff_id' => $userId],
                                ['InstitutionClasses.secondary_staff_id' => $userId]
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

            // $classOptions = ['class1', 'class2'];

            $attr['options'] = $classOptions;
            // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeClass';
        }

        return $attr;
    }

}
