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
use Cake\Log\Log;
class ImportAssessmentItemResultsTable extends AppTable {
    private $institutionId = false;

    public function initialize(array $config) {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin'=>'Institution', 
            'model'=>'AssessmentItemResults',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Assessments']
        ]);
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
        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $this->Users = TableRegistry::get('User.Users');
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
            'Model.import.onImportPopulateUsersData' => 'onImportPopulateUsersData',
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
        if (empty($request->query('class_name'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
        
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $request = $this->request;
        unset($request->query['class_name']);
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->dependency = [];
        $this->dependency["class_name"] = ["select_file"];

        $this->ControllerAction->field('class_name', ['type' => 'select']);
        $this->ControllerAction->field('education_subject', ['type' => 'select']);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['class_name', 'education_subject', 'select_file']);

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

    public function onUpdateFieldClassName(Event $event, array $attr, $action, Request $request) {
        if ($action == 'add') {
            $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');
            
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
            $classNameOption = $InstitutionClasses->find('list', [
                                    'keyField' => 'id',
                                    'valueField' => 'name'
                                ])
                                ->leftJoin([$InstitutionClassGrades->alias() => $InstitutionClassGrades->table()],[
                                    $InstitutionClassGrades->aliasField('institution_class_id = ') . $this->InstitutionClasses->aliasField('id')
                                ])
                                ->leftJoin([$this->EducationGrades->alias() => $this->EducationGrades->table()],[
                                    $this->EducationGrades->aliasField('id = ') . $this->InstitutionClassGrades->aliasField('education_grade_id')
                                ])
                                ->leftJoin([$this->Assessments->alias() => $this->Assessments->table()], [
                                    $this->Assessments->aliasField('education_grade_id = ') . $this->EducationGrades->aliasField('id')
                                ])
                                ->where([
                                    $InstitutionClasses->aliasField('institution_id') => $institutionId,
                                    $InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                                    $this->Assessments->aliasField('academic_period_id') => $academicPeriodId
                                ])
                                ->toArray();
            
            
            $attr['options'] = $classNameOption;
            // using onChangeReload to do visible
            $attr['onChangeReload'] = 'changeClassName';
        }
        
        return $attr;
    }

    public function onUpdateFieldEducationSubject(Event $event, array $attr, $action, Request $request) {
        if ($action == 'add') {
            $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');
            $classId = $request->data['ImportAssessmentItemResults']['class_name'];
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();
            $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
            $InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
            $superAdmin = $this->Auth->user('super_admin');
            $where = [];
            if ($superAdmin != 1) {
                $where[$InstitutionSubjectStaff->aliasField('staff_id')] =  $this->Auth->user('id');
            }

            $educationSubjectOption = $this->EducationSubjects->find('list', [
                                        'keyField' => 'id',
                                        'valueField' => 'name'
                                    ])
                                    ->leftJoin([$this->InstitutionSubjects->alias() => $this->InstitutionSubjects->table()],[
                                         $this->InstitutionSubjects->aliasField('education_subject_id = ') . $this->EducationSubjects->aliasField('id')
                                    ])
                                    ->leftJoin([$InstitutionClassSubjects->alias() => $InstitutionClassSubjects->table()],[
                                         $InstitutionClassSubjects->aliasField('institution_subject_id = ') . $this->InstitutionSubjects->aliasField('id')
                                    ])
                                    ->leftJoin([$InstitutionSubjectStaff->alias() => $InstitutionSubjectStaff->table()],[
                                         $InstitutionSubjectStaff->aliasField('institution_subject_id = ') . $this->InstitutionSubjects->aliasField('id')
                                    ])
                                    ->where([
                                       $InstitutionClassSubjects->aliasField('institution_class_id') => $classId,
                                       $where
                                    ])
                                    ->toArray();
            
            $attr['options'] = $educationSubjectOption;
            // using onChangeReload to do visible
            $attr['onChangeReload'] = 'changeEducationSubject';
        }
        
        return $attr;
    }

    public function onImportPopulateEducationSubjectsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $subjectId = $this->request->query['education_subject'];
        $academicPeriodId = $this->AcademicPeriods->getCurrent();
        $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

        $EducationSubjectsResults = $this->EducationSubjects->find()
                        ->select([
                            $this->EducationSubjects->aliasField('id'),
                            $this->EducationSubjects->aliasField('code'),
                            $this->EducationSubjects->aliasField('name')
                        ])
                        ->where([$this->EducationSubjects->aliasField('id') => $subjectId]); 
        
        $translatedReadableCol = $this->getExcelLabel($EducationSubjectsResults, 'Name');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];

        $modelData = $EducationSubjectsResults->find('all')
        ->select([
            'name',
            'code'
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
        $classId = $this->request->query['class_name'];
        $educationData = $this->InstitutionClassGrades->find()
                        ->select([$this->InstitutionClassGrades->aliasField('education_grade_id')])
                        ->where([$this->InstitutionClassGrades->aliasField('institution_class_id') => $classId])
                        ->first();
        $educationGradeId = $educationData->education_grade_id;
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
            'name',
            'code'
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

    public function onImportPopulateInstitutionClassesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $classId = $this->request->query['class_name'];
        $classData = $this->InstitutionClasses->find()
                        ->select([ 
                            $this->InstitutionClasses->aliasField('id'),
                            $this->InstitutionClasses->aliasField('name')
                        ])
                        ->where([$this->InstitutionClasses->aliasField('id') => $classId]);
        
        $translatedReadableCol = $this->getExcelLabel($classData, 'Name');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];

        $modelData = $classData->find('all')
        ->select([
            'name',
            'id'
        ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->id
                ];
            }
        }
    }

    public function onImportGetAssessmentPeriodsId(Event $event, $cellValue)
    {
        /*POCOR-6377 starts*/
        $academicPeriodId = $this->AcademicPeriods->getCurrent();
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $dataRecord = $this->AssessmentPeriods->find()
                    ->select([$this->AssessmentPeriods->aliasField('id')])
                    ->leftJoin([$Assessments->alias() => $Assessments->table()], [
                        $this->AssessmentPeriods->aliasField('assessment_id = ') . $Assessments->aliasField('id')
                    ])
                    ->where([
                        $Assessments->aliasField('academic_period_id') => $academicPeriodId,
                        $this->AssessmentPeriods->aliasField('code') => $cellValue
                    ])->first();
        /*POCOR-6377 ends*/
        $assessmentPeriodsId = $dataRecord->id;
        
        return $assessmentPeriodsId;
    }

    public function onImportGetInstitutionClassesId(Event $event, $cellValue)
    {
        $record = $this->InstitutionClasses->find()
                ->select([$this->InstitutionClasses->aliasField('id')])
                ->where([$this->InstitutionClasses->aliasField('id') => $cellValue])
                ->first();

        $classId = $record->id;
        return $classId;
    }

    public function onImportGetEducationSubjectsId(Event $event, $cellValue)
    {
        $data = $this->EducationSubjects->find()->select([$this->EducationSubjects->aliasField('id')])->where([$this->EducationSubjects->aliasField('code') => $cellValue])->first();
        
        $educationSubjectsId = $data->id;

        return $educationSubjectsId;
    }

    public function onImportGetUsersId(Event $event, $cellValue)
    {  
        $record = $this->Users->find()->select([$this->Users->aliasField('id')])->where([$this->Users->aliasField('openemis_no') => $cellValue])->first();
        
        $userId = $record->id;

        return $userId;
    }

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        //POCOR-6613 starts
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;// for enrolled status //POCOR-6613 ends
        $classId = $this->request->query['class_name'];
        $academicPeriodId = !is_null($this->request->query('period')) ? $this->request->query('period') : $this->AcademicPeriods->getCurrent();
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $Users = TableRegistry::get('User.Users');
        $studentData = $InstitutionClassStudents->find()
                        ->where([
                            $InstitutionClassStudents->aliasField('institution_class_id') => $classId,
                            $InstitutionClassStudents->aliasField('academic_period_id') => $academicPeriodId,
                            $InstitutionClassStudents->aliasField('student_status_id') => $enrolledStatus //POCOR-6613 
                        ])->toArray();
        $studentIds = [];
        if (!empty($studentData)) {
            foreach ($studentData as $value) {
                $studentIds[] = $value->student_id;
            }

            $UsersData = $Users->find()
                            ->select([
                                $Users->aliasField('id'),
                                $Users->aliasField('first_name'),
                                $Users->aliasField('middle_name'),
                                $Users->aliasField('third_name'),
                                $Users->aliasField('last_name'),
                                $Users->aliasField('openemis_no')
                            ])
                            ->where([$Users->aliasField('id IN') => $studentIds]);

            $translatedReadableCol = $this->getExcelLabel($UsersData, 'Name');

            $data[$columnOrder]['lookupColumn'] = 2;
            $data[$columnOrder]['data'][] = ['Name', $translatedCol];
            
            $modelData = $UsersData->find('all')
            ->select([ 
                'first_name', 
                'middle_name', 
                'third_name', 
                'last_name',
                'openemis_no'
            ]);

            if (!empty($modelData)) {
                foreach($modelData->toArray() as $row) {
                    $name = $row->first_name.' '.$row->middle_name.' '.$row->third_name.' '.$row->last_name; 
                    $data[$columnOrder]['data'][] = [
                        $name,
                        $row->openemis_no
                    ];
                }
            }
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
       
        $educationGradeId = $this->request->query['education_grade'];
        $academicPeriodId = $this->AcademicPeriods->getCurrent();
        $institutionId = $this->request->session()->read('Institution.Institutions.id');
        $tempRow['institution_id'] = $institutionId;
		/*POCOR-6528 starts*/
		$this->AssessmentItemsGradingTypes = TableRegistry::get('Institution.AssessmentItemsGradingTypes');
		$this->AssessmentGradingTypes = TableRegistry::get('Institution.AssessmentGradingTypes');
		/*POCOR-6528 ends*/
        $tempRow['academic_period_id'] = $academicPeriodId;
        $classId = $this->request->query['class_name'];
        $educationData = $this->InstitutionClassGrades->find()
                        ->select([$this->InstitutionClassGrades->aliasField('education_grade_id')])
                        ->where([$this->InstitutionClassGrades->aliasField('institution_class_id') => $classId])
                        ->first();
        $educationGradeId = $educationData->education_grade_id;
        $tempRow['education_grade_id'] = $educationGradeId;
        $assessment = $this->AssessmentPeriods->find()
                        ->select([$this->AssessmentPeriods->aliasField('assessment_id')])
                        ->where([$this->AssessmentPeriods->aliasField('id') => $tempRow['assessment_period_id']])
                        ->first();
        $tempRow['assessment_id'] = $assessment->assessment_id;
        $tempRow['institution_classes_id'] = $tempRow['class_id'];
		/*POCOR-6528 starts*/
		$maxvalue = $this->Assessments->find()
		->select(['maximumvalue'=>$this->AssessmentGradingTypes->aliasField('max')])
		 ->InnerJoin([$this->AssessmentItems->alias() => $this->AssessmentItems->table()],[
                                    $this->AssessmentItems->aliasField('assessment_id = ') . $this->Assessments->aliasField('id')
                                ])
		->InnerJoin([$this->AssessmentItemsGradingTypes->alias() => $this->AssessmentItemsGradingTypes->table()],[
                                    $this->AssessmentItemsGradingTypes->aliasField('assessment_id = ') . $this->AssessmentItems->aliasField('assessment_id'),
									
                                    $this->AssessmentItemsGradingTypes->aliasField('education_subject_id = ') . $this->AssessmentItems->aliasField('education_subject_id')
                                ])
		->InnerJoin([$this->AssessmentGradingTypes->alias() => $this->AssessmentGradingTypes->table()],[
                                    $this->AssessmentGradingTypes->aliasField('id =') . $this->AssessmentItemsGradingTypes->aliasField('assessment_grading_type_id')
                                ])
		->InnerJoin([$this->AssessmentPeriods->alias() => $this->AssessmentPeriods->table()],[
                                    $this->AssessmentPeriods->aliasField('assessment_id =') . $this->Assessments->aliasField('id')	
                                ])									
		->InnerJoin([$this->InstitutionClassGrades->alias() => $this->InstitutionClassGrades->table()],[
                                    $this->InstitutionClassGrades->aliasField('education_grade_id =') . $this->Assessments->aliasField('education_grade_id')
                                ])
		->where([$this->InstitutionClassGrades->aliasField('institution_class_id') => $classId])
		->first();
		
		$maxval = $maxvalue->maximumvalue;
		$value = preg_replace('~\.0+$~','',$maxval);
		/*POCOR-6528 ends*/
        /*POCOR-6486 starts*/
        $enteredMarks = $tempRow['marks'];
        if (!empty($enteredMarks) && $enteredMarks > 100) {
            $rowInvalidCodeCols['marks'] = __('Marks Should be between 0 to 100');
            $tempRow['marks'] = false;
            return false;
        
		/*POCOR-6528 starts*/
        }elseif (!empty($enteredMarks) && $enteredMarks > $maxval) {
            $rowInvalidCodeCols['marks'] = __('Marks Should be less then to max Marks');
            $tempRow['marks'] = false;
            return false;
        }
		/*POCOR-6528 ends*/
        /*POCOR-6486 ends*/
        return true;
    }
}