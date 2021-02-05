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

class ImportAssessmentItemResultsTable extends AppTable {
    private $institutionId = false;

    public function initialize(array $config) {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Assessment', 'model'=>'AssessmentItemResults']);
        
        // register table once
        $this->AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->EducationSubjects = TableRegistry::get('Education.EducationSubjects');
        $this->EducationGrades = TableRegistry::get('Education.EducationGrades');
        $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $this->Assessments = TableRegistry::get('Assessment.Assessments');
        $this->AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
    }

    public function beforeAction($event) {
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
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

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
            $tempRow['entity'] = $this->AssessmentItemResults->newEntity();
    }

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {}

    public function onImportPopulateAssessmentPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $educationGradeId = $this->request->query['education_grade'];
        $academicPeriodId = $this->AcademicPeriods->getCurrent();

        $Assessments = TableRegistry::get('Assessment.Assessments');
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');

        $assessmentPeriodsResult = $Assessments->find()
                        ->select([
                            $AssessmentPeriods->aliasField('id'),
                            $AssessmentPeriods->aliasField('code'),
                            $AssessmentPeriods->aliasField('name')
                        ])
                        ->leftJoin([$AssessmentPeriods->alias() => $AssessmentPeriods->table()], [
                            $AssessmentPeriods->aliasField('assessment_id = ') . $AssessmentPeriods->aliasField('id')
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
                        ->leftJoin([$this->InstitutionSubjects->alias() => $this->InstitutionSubjects->table()],
                            [
                                $this->EducationSubjects->aliasField('id = ') . $this->InstitutionSubjects->aliasField('education_subject_id')
                            ])
                        ->where([
                            $this->InstitutionSubjects->aliasField('institution_id') => $institutionId,
                            $this->InstitutionSubjects->aliasField('academic_period_id') => $academicPeriodId,
                            $this->InstitutionSubjects->aliasField('education_grade_id') => $educationGradeId
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


    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
        //echo "<pre>";print_r($tempRow );die("poonam");
        return true;
    }

    public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $academicPeriodId =  !empty($request->query['academic_period_id']) ? $request->query['academic_period_id'] : $this->AcademicPeriods->getCurrent();
            
            $educationGradeOptions = $this->Assessments
            ->find('list', [
                'keyField' => 'education_grade_id',
                'valueField' => 'EducationGrades'
            ])
            ->leftJoin(['EducationGrades' => 'education_grades'], [
                'EducationGrades.id = ' . $this->Assessments->aliasField('education_grade_id')
            ])
            ->select(['EducationGrades' => 'EducationGrades.name', 'education_grade_id' => 'EducationGrades.id'])
            ->where([$this->Assessments->aliasField('academic_period_id') => $academicPeriodId])
            ->group([
                'EducationGrades.id',
            ])
            ->toArray();
            
            $attr['options'] = $educationGradeOptions;
                // using onChangeReload to do visible
            $attr['onChangeReload'] = 'changeEducationGrade';
        }
        
        return $attr;
    }

}
