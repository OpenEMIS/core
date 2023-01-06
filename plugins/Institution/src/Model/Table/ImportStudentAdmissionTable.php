<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use DateTimeInterface;
use DateTime;
use PHPExcel_Worksheet;
use Workflow\Model\Behavior\WorkflowBehavior;

class ImportStudentAdmissionTable extends AppTable { 
    private $institutionId;
    private $gradesInInstitution;
    private $systemDateFormat;
    private $studentStatusId;
    private $availableClasses;

    public function initialize(array $config) { 
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'StudentAdmission',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students']
        ]);
        $this->addBehavior('Institution.ImportStudent');

        // register the target table once
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $this->StudentAdmission = TableRegistry::get('Institution.StudentAdmission');
        $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $this->Students = TableRegistry::get('Security.Users');
        $this->Workflows = TableRegistry::get('Workflow.Workflows'); 
        $this->EducationGrades = TableRegistry::get('Education.EducationGrades');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->ControllerAction->field('academic_period_id', [
            'type' => 'select',
            'select' => false,
            'entity' => $entity,
            // 'before' => 'select_file',
            'after' => 'feature'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriod($this->request->query('period'), true));
        if ($action == 'add') {
            # $attr['default'] = $selectedPeriod;
            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                    $this->gradesInInstitution = $this->getCustomInstitudeGradeIds($request->query['period']);
                }
            }
        }
    }

    public function getAcademicPeriod($querystringPeriod, $withOptions = false)
    {
        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }
        if ($withOptions){
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            return compact('periodOptions', 'selectedPeriod');
        } else {
            return $selectedPeriod;
        }
    }

    private function getCustomInstitudeGradeIds($acedamicId)
    {
        return $this->InstitutionGrades->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'education_grade_id'
                    ])
                    ->LeftJoin(['EducationGrades' => 'education_grades'],[
                        'EducationGrades.id = '.$this->InstitutionGrades->aliasField('education_grade_id')
                    ])
                    ->LeftJoin(['EducationProgrammes' => 'education_programmes'],[
                        'EducationProgrammes.id = EducationGrades.education_programme_id'
                    ])
                    ->LeftJoin(['EducationCycles' => 'education_cycles'],[
                        'EducationCycles.id = EducationProgrammes.education_cycle_id'
                    ])
                    ->LeftJoin(['EducationLevels' => 'education_levels'],[
                        'EducationLevels.id = EducationCycles.education_level_id'
                    ])
                    ->LeftJoin(['EducationSystems' => 'education_systems'],[
                        'EducationSystems.id = EducationLevels.education_system_id'
                    ])
                    ->LeftJoin(['AcademicPeriods' => 'academic_periods'],[
                        'AcademicPeriods.id = EducationSystems.academic_period_id'
                    ])
                    ->where([
                        $this->InstitutionGrades->aliasField('institution_id') => $this->institutionId,
                        'AcademicPeriods.id' => $acedamicId
                    ])
                    ->toArray();
    }

    public function beforeAction($event) {
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
            $acedemicId = $this->AcademicPeriods->getCurrent();
            if (isset($this->request->query['period']) && !empty($this->request->query['period'])) {
                $acedemicId = $this->request->query['period'];
            }
            $this->gradesInInstitution = $this->getCustomInstitudeGradeIds($acedemicId);
        } else {
            $this->institutionId = false;
            $this->gradesInInstitution = [];
        }
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
        $columns = new Collection($columns);
        $filtered = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'student_id';
        });
        $studentIdIndex = key($filtered->toArray());
        $studentId = $sheet->getCellByColumnAndRow($studentIdIndex, $row)->getValue();

        if (in_array($studentId, $importedUniqueCodes->getArrayCopy())) {
            $rowInvalidCodeCols['student_id'] = $this->getExcelLabel('Import', 'duplicate_unique_key');
            return false;
        }

        $tempRow['entity'] = $this->StudentAdmission->newEntity();
        $tempRow['end_date'] = false;
        $tempRow['assignee_id'] = WorkflowBehavior::AUTO_ASSIGN;
        $tempRow['institution_id'] = $this->institutionId;
        // Optional fields which will be validated should be set with a default value on initialisation
        $tempRow['institution_class_id'] = null;
    }

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
        $importedUniqueCodes[] = $entity->student_id;
    }

    public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $acedemicId = $this->AcademicPeriods->getCurrent();
        if (isset($this->request->query['period']) && !empty($this->request->query['period'])) {
            $acedemicId = $this->request->query['period'];
        }
        $modelData = $lookedUpTable->getAvailableAcademicPeriodsById($acedemicId, false);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
        $endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData as $row) {
                if ($row->academic_period_level_id == 1) { //validate that only period level "year" will be shown
                    $date = $row->start_date;
                    $data[$columnOrder]['data'][] = [
                        $row->name,
                        $row->start_date->format('d/m/Y'),
                        $row->end_date->format('d/m/Y'),
                        $row->{$lookupColumn}
                    ];
                }
            }
        }
    }

    public function onImportPopulateEducationGradesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $programmeHeader = $this->getExcelLabel($lookedUpTable, 'education_programme_id');
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$programmeHeader, $translatedReadableCol, $translatedCol];
        if (!empty($this->gradesInInstitution)) {
            $modelData = $lookedUpTable->find('all')
                                    ->contain(['EducationProgrammes'])
                                    ->select(['code', 'name', 'EducationProgrammes.name'])
                                    ->where([
                                        $lookedUpTable->aliasField('visible').' = 1'
                                    ])
                                    ->order([
                                        $lookupModel.'.order',
                                        $lookupModel.'.education_programme_id'
                                    ])
                                    ->where([
                                        $lookedUpTable->aliasField('id').' IN' => $this->gradesInInstitution
                                    ]);
            if (!empty($modelData)) {
                foreach($modelData->toArray() as $row) {
                    $data[$columnOrder]['data'][] = [
                        $row->education_programme->name,
                        $row->name,
                        $row->{$lookupColumn}
                    ];
                }
            }
        }
    }

    public function onImportPopulateStudentUserData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        unset($data[$columnOrder]);
    }

    public function onImportPopulateInstitutionClassesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        try {
            $institution = $this->Institutions->get($this->institutionId);
            $modelData = $this->populateInstitutionClassesData();

            $institutionNameLabel = $this->getExcelLabel('Imports', 'institution_name');
            $academicPeriodCodeLabel = $this->getExcelLabel('Imports', 'period_code');
            $classNameLabel = $this->getExcelLabel($lookupModel, 'name');
            $classCodeLabel = $this->getExcelLabel('Imports', 'institution_classes_code');

            // unset($data[$sheetName]);
            $sheetName = $this->getExcelLabel('Imports', $lookupModel);
            $data[$columnOrder]['sheetName'] = $sheetName;
            $data[$columnOrder]['lookupColumn'] = 4;
            $data[$columnOrder]['data'][] = [
                $institutionNameLabel,
                $academicPeriodCodeLabel,
                $classNameLabel,
                $classCodeLabel
            ];
            if (!empty($modelData)) {
                foreach($modelData as $periodCode=>$periodClasses) {
                    if (!empty($periodClasses)) {
                        foreach($periodClasses as $id=>$name) {
                            $data[$columnOrder]['data'][] = [
                                $institution->name,
                                $periodCode,
                                $name,
                                $id
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'error');
        }

    }

    private function populateInstitutionClassesData() {
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $availableAcademicPeriods = $AcademicPeriods->getAvailableAcademicPeriods(false);

        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $modelData = [];
        foreach ($availableAcademicPeriods as $key=>$value) {
            $modelData[$value->code] = $InstitutionClasses->getClassOptions($value->id, $this->institutionId);
        }
        return $modelData;
    }

    public function onImportPopulateWorkflowStepsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);

        $workflowResult = $this->Workflows
            ->find()
            ->select([
                'workflow_id' => $this->Workflows->aliasField('id'),
                'workflow_step_id' => $lookedUpTable->aliasField('id'),
                'workflow_step_name' => $lookedUpTable->aliasField('name')
            ])
            ->matching('WorkflowModels', function ($q) {
                return $q->where(['WorkflowModels.model' => 'Institution.StudentAdmission']);
            })
            ->matching($lookedUpTable->alias())
            ->order([
                $this->Workflows->aliasField('name'),
                $lookupModel.'.category'
            ])
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        if (!$workflowResult->isEmpty()) {
            $modelData = $workflowResult->toArray();

            foreach ($modelData as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->workflow_step_name,
                    $row->workflow_step_id
                ];
            }
        }
    }

    private function getCustomEducationGradeIdByEducationGrade($acedemicPeriodId, $educationGradeCode, $educationGradeId)
    {
        $result = $this->EducationGrades->find('all')
        ->LeftJoin(['EducationProgrammes' => 'education_programmes'],[
            'EducationProgrammes.id = '.$this->EducationGrades->aliasField('education_programme_id')
        ])
        ->LeftJoin(['EducationCycles' => 'education_cycles'],[
            'EducationCycles.id = EducationProgrammes.education_cycle_id'
        ])
        ->LeftJoin(['EducationLevels' => 'education_levels'],[
            'EducationLevels.id = EducationCycles.education_level_id'
        ])
        ->LeftJoin(['EducationSystems' => 'education_systems'],[
            'EducationSystems.id = EducationLevels.education_system_id'
        ])
        ->select(['id'])
        ->where([
            $this->EducationGrades->aliasField('visible') =>'1',
            $this->EducationGrades->aliasField('code') => $educationGradeCode,
            'EducationSystems.academic_period_id' => $acedemicPeriodId
        ])->toArray();
        if (count($result) > 0 && isset($result[0]->id)) {
            return $result[0]->id;
        }
        return $educationGradeId;
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) 
    {
        $originalEducationGradeCode = '';
        if (isset($originalRow[1])) {
            $originalEducationGradeCode = $originalRow[1];
        }
        $educationGradeId = $this->getCustomEducationGradeIdByEducationGrade($tempRow['academic_period_id'], $originalEducationGradeCode, $tempRow['education_grade_id']);
        $tempRow['education_grade_id'] = $educationGradeId;
        $tempRow['education_grade_code'] = $originalEducationGradeCode;
        if (empty($tempRow['student_id'])) {
            return false;
        }
        try {
            $student = $this->Students->get($tempRow['student_id']);
        } catch (RecordNotFoundException $e) {
            $rowInvalidCodeCols['student_id'] = __('No such student in the system');
            return false;
        } catch (InvalidPrimaryKeyException $e) {
            $rowInvalidCodeCols['student_id'] = __('Invalid OpenEMIS ID');
            return false;
        }
        if (empty($student->date_of_birth)) {
            $rowInvalidCodeCols['date_of_birth'] = __('Student\'s date of birth is empty. Please correct it at Directory page');
            return false;
        }
        $tempRow['student_name'] = $tempRow['student_id'];

        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            return false;
        }
        $tempRow['institution_id'] = $this->institutionId;

        // from string to dateObject
        $formattedDate = DateTime::createFromFormat('d/m/Y', $tempRow['start_date']);
        $tempRow['start_date'] = $formattedDate;

        if (empty($tempRow['start_date'])) {
            $rowInvalidCodeCols['start_date'] = __('No start date specified');
            return false;
        } else if (!$tempRow['start_date'] instanceof DateTimeInterface) {
            $rowInvalidCodeCols['start_date'] = __('Unknown date format');
            return false;
        }

        //check the level of academic period chosen for "year"
        $academicPeriodLevel = $this->getAcademicPeriodLevel($tempRow['academic_period_id']);

        if (count($academicPeriodLevel)>0) {
            if ($academicPeriodLevel[0]['academic_period_level_id']!=1) { //if the level is not year
                $rowInvalidCodeCols['academic_period_id'] = __('Academic period must be in year level');
                return false;
            }
        }

        $periods = $this->getAcademicPeriodByStartDate($tempRow['start_date']->format('Y-m-d'));
        if (!$periods) {
            $rowInvalidCodeCols['start_date'] = __('No matching academic period based on the start date');
            return false;
        }
        $period='';
        foreach ($periods as $value) {
            if ($value->id == $tempRow['academic_period_id']) {
                $period = $value;
                break;
            }
        }

        if (empty($period)) {
            $rowInvalidCodeCols['start_date'] = __('Start date is not within selected academic period');
            return false;
        }
        if (!$period->start_date instanceof DateTimeInterface) {
            $rowInvalidCodeCols['academic_period_id'] = __('Please check the selected academic period start date in Administration');
            return false;
        }
        $periodStartDate = $period->start_date->toUnixString();
        if (!$period->end_date instanceof DateTimeInterface) {
            $rowInvalidCodeCols['academic_period_id'] = __('Please check the selected academic period end date in Administration');
            return false;
        }
        $periodEndDate = $period->end_date->toUnixString();
        $tempRow['end_date'] = $period->end_date;
    
        if (!in_array($tempRow['education_grade_id'], $this->gradesInInstitution)) {
            $rowInvalidCodeCols['education_grade_id'] = __('Selected education grade is not being offered in this institution');
            return false;
        }
        $institutionGrade = $this->InstitutionGrades
                                ->find()
                                ->contain('EducationGrades.EducationProgrammes.EducationCycles')
                                ->where([
                                    $this->InstitutionGrades->aliasField('education_grade_id') => $tempRow['education_grade_id'],
                                    $this->InstitutionGrades->aliasField('institution_id') => $this->institutionId
                                ])
                                ;
        if ($institutionGrade->isEmpty()) {
            $rowInvalidCodeCols['education_grade_id'] = __('No matching education grade.');
            return false;
        }

        $institutionGrade = $institutionGrade->first();
        if (!$institutionGrade->start_date instanceof DateTimeInterface) {
            $rowInvalidCodeCols['education_grade_id'] = __('Please check the selected education grade start date at the institution');
            return false;
        }

        $gradeStartDate = $institutionGrade->start_date->toUnixString();
        $gradeEndDate = (!empty($institutionGrade->end_date) && (!$institutionGrade->end_date instanceof DateTimeInterface)) ? $institutionGrade->end_date->toUnixString() : '';
        if (!empty($gradeEndDate) && $gradeEndDate < $periodEndDate) {
            $rowInvalidCodeCols['education_grade_id'] = __('Selected education grade will end before academic period ends');
            return false;
        }
        if ($gradeStartDate > $periodStartDate) {
            $rowInvalidCodeCols['education_grade_id'] = __('Selected education grade start date should be before academic period starts');
            return false;
        }

        if (!empty($tempRow['institution_class_id'])) {
            if (empty($this->availableClasses)) {
                $this->availableClasses = $this->populateInstitutionClassesData();
            }
            $this->availableClasses;
            $selectedClassIdFound = null;
            if (!empty($this->availableClasses)) {
                foreach($this->availableClasses as $periodCode=>$periodClasses) {
                    if (!empty($periodClasses)) {
                        foreach($periodClasses as $id=>$name) {
                            if ($id == $tempRow['institution_class_id']) {

                                if ($periodCode == $period->code) {
                                    $selectedClassIdFound = true;

                                    //check class grade against selected grade
                                    $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
                                    $classGrade = $InstitutionClasses->getClassGradeOptions($id);

                                    if (!in_array($tempRow['education_grade_id'], $classGrade)) { //if selected grade cant be found on class grade
                                        $rowInvalidCodeCols['education_grade_id'] = __('Selected education grade does not match with grades offered by the class');
                                        return false;
                                    } else {
                                        //checking class capacity if student imported straight to the class.
                                        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
                                        $countStudent = $InstitutionClassStudents->getStudentCountByClass($id);
                                        $classCapacity = $InstitutionClasses->get($id)->capacity; 

                                        if ($countStudent + 1 > $classCapacity) {
                                            return false;
                                        }
                                    }

                                } else {
                                    $selectedClassIdFound = false;
                                }
                                break;
                            }
                        }
                    }
                }
            }
            if (is_null($selectedClassIdFound)) {
                $rowInvalidCodeCols['institution_class_id'] = __('Selected class does not exists in this institution');
                return false;
            } else if (!$selectedClassIdFound) {
                $rowInvalidCodeCols['institution_class_id'] = __('Selected class does not exists during the selected Academic Period');
                return false;
            }
        }
        
        //check student gender against institution gender (use existing function from validation behavior)
        $result = $this->checkStudentGenderAgainstInstitutionGender($tempRow['student_id'], $this->institutionId);
            
        if ($result !== true) {
            $rowInvalidCodeCols['student_id'] = __($result);
            return false;
        }
        //end of checking student gender

        return true;
    }

    private function checkStudentGenderAgainstInstitutionGender($studentId, $institutionId) 
    {
        if (!empty($institutionId)) {
            //get institution gender
            $query = $this->Institutions->find()
                    ->contain('Genders')
                    ->where([$this->Institutions->aliasField('id') => $institutionId])
                    ->select(['Genders.code', 'Genders.name'])
                    ->first();
            $institutionGender = $query->Genders->name;
            $institutionGenderCode = $query->Genders->code;

            if ($institutionGenderCode == 'X') { //if mixed then always true
                return true;
            } else {
                $query = $this->Students->find()
                        ->contain('Genders')
                        ->where([$this->Students->aliasField('id') => $studentId])
                        ->select(['Genders.code'])
                        ->first();
                
                $userGender = $query->gender->code;

                if ($userGender != $institutionGenderCode) {
                    return sprintf('Institution only accepts %s student.', $institutionGender);
                } else {
                    return true;
                }
            }
        }
    }

    public function onImportSetModelPassedRecord(Event $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow) 
    {
        //POCOR-6995 Start
        $institutionClass =  TableRegistry::get('Institution.InstitutionClasses');
        /*$classIds = [];
        foreach($clonedEntity->institution_class_id as $keys=>$val){
            print_r($val);die;
           if($val->institution_class_id != NULL){
                $classIds[] = $val->institution_class_id;
           }
        }*/

        $classIds = $clonedEntity->institution_class_id;
        if(!empty($classIds))
        {
            $bodyData = $institutionClass->find('all',
                        [ 'contain' => [
                            'Institutions',
                            'EducationGrades',
                            'Staff',
                            'AcademicPeriods',
                            'InstitutionShifts',
                            'InstitutionShifts.ShiftOptions',
                            'ClassesSecondaryStaff.SecondaryStaff',
                            'Students',
                            'Students.Genders'
                        ],
                        ])->where([
                            $institutionClass->aliasField('id IN') => $classIds
                        ]);
            $grades = $gradeId = $secondaryTeachers = $students = [];
            $dataVal = [];
            if (!empty($bodyData)) {
                foreach ($bodyData as $key => $value) {
                    $dataVal[$key]['institutions_id'] = $value->institution->id;
                    $dataVal[$key]['institutions_name'] = $value->institution->name;
                    $dataVal[$key]['institutions_code'] = $value->institution->code;
                    $dataVal[$key]['institutions_classes_name'] = $value->name;
                    $dataVal[$key]['institutions_classes_id'] = $value->id;
                    $dataVal[$key]['shift_options_name'] = $value->institution_shift->shift_option->name;
                    $dataVal[$key]['academic_periods_name'] = $value->academic_period->name;
                    $dataVal[$key]['institutions_classes_capacity'] = $value->capacity;
                    $dataVal[$key]['institution_classes_staff_openemis_no'] = $value->staff->openemis_no; // for home room teacher
                    $dataVal[$key]['institution_classes_id'] = $value->id; 
                    $dataVal[$key]['institution_classes_name'] = $value->name; 

                    if(!empty($value->education_grades)) {
                        foreach ($value->education_grades as $i => $gradeOptions) {
                            $dataVal[$key]['Grades'][$i]['education_grades_name'] = $gradeOptions->name;
                            $dataVal[$key]['Grades'][$i]['education_grades_id'] = $gradeOptions->id;
                        }
                    }else{
                        $dataVal[$key]['Grades']['education_grades_name'] = NULL;
                        $dataVal[$key]['Grades']['education_grades_name'] = NULL;
                    }

                    if(!empty($value->classes_secondary_staff)) {
                        foreach ($value->classes_secondary_staff as $j => $secondaryStaffs) {
                           $dataVal[$key]['secondaryTeachers'][$j]['institution_classes_secondary_staff_openemis_no'] = $secondaryStaffs->secondary_staff->openemis_no;
                        }

                    }else{
                       $dataVal[$key]['secondaryTeachers']['institution_classes_secondary_staff_openemis_no'] = NULL;
                    }
                    $maleStudents = 0;
                    $femaleStudents = 0;
                    if(!empty($value->students)) {
                        foreach ($value->students as $k => $studentsData) {
                            $dataVal[$key]['students'][$k]['institution_class_students_openemis_no'] = $studentsData->openemis_no;
                            if($studentsData->gender->code == 'M') {
                                $maleStudents = $maleStudents + 1;
                                $dataVal[$key]['maleStudents']['institution_classes_total_male_students'] = $maleStudents;
                            }
                            if($studentsData->gender->code == 'F') {
                                $femaleStudents = $femaleStudents + 1;
                                $dataVal[$key]['femaleStudents']['institution_classes_total_female_studentss'] = $femaleStudents;
                            }

                        }
                        $totalStudent = $maleStudents + $femaleStudents ;
                        $dataVal[$key]['total_students'] = $totalStudent;  
                    }else{
                        $dataVal[$key]['total_students'] = NULL;
                        $dataVal[$key]['students']['institution_class_students_openemis_no'] = NULL;
                        $dataVal[$key]['maleStudents']['institution_classes_total_male_students'] = NULL;
                        $dataVal[$key]['femaleStudents']['institution_classes_total_female_studentss'] = NULL;
                    }
                    

                }
            }

            $body = array();
            $body = [
                'institutions_classes' => !empty($dataVal) ? $dataVal : NULL,
            ];

            //print_r($body);die;
            $Webhooks = TableRegistry::get('Webhook.Webhooks');
            $Webhooks->triggerShell('class_update', ['username' => ''], $body);
            // end POCOR-6995
        }

        $flipped = array_flip($columns);
        $key = $flipped['student_id'];
        $tempPassedRecord['data'][$key] = $originalRow[$key];
    }

    
}
