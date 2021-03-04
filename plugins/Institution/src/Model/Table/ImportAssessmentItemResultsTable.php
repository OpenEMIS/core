<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
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
use Cake\Utility\Inflector;

class ImportAssessmentItemResultsTable extends AppTable {
    private $institutionId = false;

    public function initialize(array $config) {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'AssessmentItemResults']);
        // register table once
        $this->AssessmentItemResults = TableRegistry::get('Institution.AssessmentItemResults');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->EducationSubjects = TableRegistry::get('Education.EducationSubjects');
        $this->EducationGrades = TableRegistry::get('Education.EducationGrades');
        $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $this->Assessments = TableRegistry::get('Assessment.Assessments');
        $this->AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
        $this->AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
        $this->InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $this->Student = TableRegistry::get('Security.Users');
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
            'Model.import.onImportPopulateAssessmentPeriodsData' => 'onImportPopulateAssessmentPeriodsData',
            'Model.import.onImportPopulateEducationSubjectsData' => 'onImportPopulateEducationSubjectsData',
            'Model.import.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->alias());
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'AssessmentItemResults'];
        $Navigation->substituteCrumb($crumbTitle, 'AssessmentItemResults', $url);
        $Navigation->addCrumb($crumbTitle);
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
            $tempRow['entity'] = $this->AssessmentItemResults->newEntity();   
    }

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {}

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if (isset($buttons[1])) {
            $buttons[1]['url'] = $this->ControllerAction->url('index');
            $buttons[1]['url']['action'] = 'Assessments';
        }
        $request = $this->request;
        if (empty($request->query('education_grade'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
        
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $request = $this->request;
        unset($request->query['education_grade']);
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->dependency = [];
        $this->dependency["education_grade"] = ["select_file"];

        $this->ControllerAction->field('education_grade', ['type' => 'select']);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['education_grade', 'select_file']);

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

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
       if (isset($toolbarButtons['back'])) {
            $toolbarButtons['back']['url'] = $this->ControllerAction->url('index');
            $toolbarButtons['back']['url']['action'] = 'Assessments';
        }
    }

    public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, Request $request) {
        if ($action == 'add') {
            $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');
            
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();
            
            $educationGradeOptions = $this->InstitutionGrades
            ->find('list', [
                'keyField' => 'education_grade_id',
                'valueField' => 'EducationGrades'
            ])
            ->leftJoin([$this->Assessments->alias() => $this->Assessments->table()],
                            [
                                $this->Assessments->aliasField('education_grade_id = ') . $this->InstitutionGrades->aliasField('education_grade_id')
                            ])
            ->leftJoin(['EducationGrades' => 'education_grades'], [
                'EducationGrades.id = ' . $this->InstitutionGrades->aliasField('education_grade_id')
            ])
            ->select(['EducationGrades' => 'EducationGrades.name', 'education_grade_id' => 'EducationGrades.id'])
            ->where([$this->InstitutionGrades->aliasField('institution_id') => $institutionId,
                $this->Assessments->aliasField('academic_period_id') => $academicPeriodId 
                ])
            ->group([
                'EducationGrades.id',
            ])
            ->toArray();
            
            $attr['options'] = $educationGradeOptions;
                // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeEducationGrade';
        }
        
        return $attr;
    }

    public function onImportPopulateEducationSubjectsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $educationGradeId = $this->request->query['education_grade'];
        $academicPeriodId = $this->AcademicPeriods->getCurrent();
        $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

        $EducationSubjectsResults = $this->EducationSubjects->find()
                        ->select([
                            $this->EducationSubjects->aliasField('id'),
                            $this->EducationSubjects->aliasField('code'),
                            $this->EducationSubjects->aliasField('name')
                        ])
                        ->leftJoin([$this->AssessmentItems->alias() => $this->AssessmentItems->table()],
                            [
                                $this->EducationSubjects->aliasField('id = ') . $this->AssessmentItems->aliasField('education_subject_id')
                        ])
                        ->leftJoin([$this->Assessments->alias() => $this->Assessments->table()],
                            [
                                $this->AssessmentItems->aliasField('assessment_id = ') . $this->Assessments->aliasField('id')
                        ])
                        ->where([
                            $this->Assessments->aliasField('academic_period_id') => $academicPeriodId,
                            //$this->InstitutionSubjects->aliasField('education_grade_id') => $educationGradeId
                        ]); 

               
        $translatedReadableCol = $this->getExcelLabel($EducationSubjectsResults, 'Name');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];

        $modelData = $EducationSubjectsResults->find('all')
        ->select([
            'code',
            'name'
        ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {

                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->code
                ];
            }
        }
    }

    public function onImportPopulateAssessmentPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $educationGradeId = $this->request->query['education_grade'];
        $academicPeriodId = $this->AcademicPeriods->getCurrent();

        $Assessments = TableRegistry::get('Assessment.Assessments');
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');

        $assessmentPeriodsResult = $AssessmentPeriods->find()
                        ->select([
                            $AssessmentPeriods->aliasField('id'),
                            $AssessmentPeriods->aliasField('code'),
                            $AssessmentPeriods->aliasField('name')
                        ])
                        ->leftJoin([$Assessments->alias() => $Assessments->table()], [
                            $AssessmentPeriods->aliasField('assessment_id = ') . $Assessments->aliasField('id')
                        ])
                        ->where([
                            $Assessments->aliasField('academic_period_id') => $academicPeriodId,
                            $Assessments->aliasField('education_grade_id') => $educationGradeId
                        ]);

        $translatedReadableCol = $this->getExcelLabel($assessmentPeriodsResult, 'Name');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];

        $modelData = $assessmentPeriodsResult->find('all')
        ->select([
            'code',
            'name'
        ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->code
                ];
            }
        }
    }

    public function onImportPopulateAssessmentsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $educationGradeId = $this->request->query['education_grade'];
        $academicPeriodId = $this->AcademicPeriods->getCurrent();

        $assessmentsResult = $this->Assessments->find()
                        ->select([
                            $this->Assessments->aliasField('id'),
                            $this->Assessments->aliasField('code'),
                            $this->Assessments->aliasField('name')
                        ])
                        ->where([
                            $this->Assessments->aliasField('academic_period_id') => $academicPeriodId,
                            $this->Assessments->aliasField('education_grade_id') => $educationGradeId
                        ]);

        $translatedReadableCol = $this->getExcelLabel($assessmentsResult, 'Name');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];

        $modelData = $assessmentsResult->find('all')
        ->select([
            'code',
            'name'
        ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->code
                ];
            }
        }
    }

    public function onImportGetAssessmentPeriodsId(Event $event, $cellValue)
    {
        
        $dataRecord = $this->AssessmentPeriods->find()->select([$this->AssessmentPeriods->aliasField('id')])->where([$this->AssessmentPeriods->aliasField('code') => $cellValue])->first();
        
        $assessmentPeriodsId = $dataRecord->id;
        
        return $assessmentPeriodsId;
    }

    public function onImportGetAssessmentsId(Event $event, $cellValue)
    {  
        $record = $this->Assessments->find()->select([$this->Assessments->aliasField('id')])->where([$this->Assessments->aliasField('code') => $cellValue])->first();
        
        $assessmentsId = $record->id;

        return $assessmentsId;
    }

    public function onImportGetEducationSubjectsId(Event $event, $cellValue)
    {
        $data = $this->EducationSubjects->find()->select([$this->EducationSubjects->aliasField('id')])->where([$this->EducationSubjects->aliasField('code') => $cellValue])->first();
        
        $educationSubjectsId = $data->id;

        return $educationSubjectsId;
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
        
        $educationGradeId = $this->request->query['education_grade'];
        $academicPeriodId = $this->AcademicPeriods->getCurrent();
        $institutionId = $this->ControllerAction->paramsDecode($this->request->params['institutionId']);
        $id = $institutionId['id'];
        $tempRow['institution_id'] = $id;
        $tempRow['education_grade_id'] = $educationGradeId;
        $tempRow['academic_period_id'] = $academicPeriodId;
    
        $studentRecord = $this->Student->find()
                           ->select([$this->Student->aliasField('id')]) 
                           ->where([$this->Student->aliasField('openemis_no') => $tempRow['student_id']])
                           ->first();

        $stdId = $studentRecord['id'];
        $tempRow['student_id'] = $stdId;
        
        return true;
    }
}