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
    }

    public function beforeAction($event) {
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
            $this->gradesInInstitution = $this->InstitutionGrades
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'education_grade_id'
                    ])
                    ->where([
                        $this->InstitutionGrades->aliasField('institution_id') => $this->institutionId
                    ])
                    ->toArray();
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
        $modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
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

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) 
    {
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

    public function onImportSetModelPassedRecord(Event $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow) {
        $flipped = array_flip($columns);
        $key = $flipped['student_id'];
        $tempPassedRecord['data'][$key] = $originalRow[$key];
    }
}
