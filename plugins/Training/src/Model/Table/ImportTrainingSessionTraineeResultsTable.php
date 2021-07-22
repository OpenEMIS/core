<?php
namespace Training\Model\Table;

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

class ImportTrainingSessionTraineeResultsTable extends AppTable
{
    private $institutionId = false;

    public function initialize(array $config) {
        $this->table('import_mapping');
        parent::initialize($config);
        $this->addBehavior('Import.Import', [
            'plugin'=>'Training', 
            'model'=>'TrainingSessionTraineeResults',
            'backUrl' => ['plugin' => 'Training', 'controller' => 'Trainings', 'action' => 'Results']
        ]);
        // $this->addBehavior('Import.Import');
        // register table once
        $this->Users = TableRegistry::get('User.Users');
        $this->TrainingSessions = TableRegistry::get('training_sessions');
        $this->TrainingSessionTraineeResults = TableRegistry::get('Training.TrainingSessionTraineeResults');
    }

    public function beforeAction($event) {
        $session = $this->request->session();
        /*if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }*/
        $this->systemDateFormat = TableRegistry::get('Configuration.ConfigItems')->value('date_format');
    }

    public function implementedEvents() { 
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportPopulateTrainingResultTypesData' => 'onImportPopulateTrainingResultTypesData',
            'Model.import.onImportPopulateTrainingSessionsData' => 'onImportPopulateTrainingSessionsData',
            'Model.import.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->alias());
        $url = ['plugin' => 'Training', 'controller' => 'Trainings', 'action' => 'TrainingSessionTraineeResults'];

        $Navigation->substituteCrumb($crumbTitle, 'TrainingSessionTraineeResults', $url);
        $Navigation->addCrumb($crumbTitle);
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
        $tempRow['entity'] = $this->TrainingSessionTraineeResults->newEntity();   
    }

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {}

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {   
        if (isset($buttons[1])) {
            $buttons[1]['url'] = $this->ControllerAction->url('Results');
            $buttons[1]['url']['action'] = 'TrainingSessionTraineeResults';
        }
        $request = $this->request;
        if (empty($request->query('training_courses'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(Event $event, Entity $entity)
    { 
        $request = $this->request;
        unset($request->query['training_courses']);
    }

    public function addAfterAction(Event $event, Entity $entity)
    {  
        $this->dependency = [];
        $this->dependency["training_courses"] = ["select_file"];

        $this->ControllerAction->field('training_courses', ['type' => 'select']);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['training_courses', 'select_file']);

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
            $toolbarButtons['back']['url'] = $this->ControllerAction->url('Results');
            //$toolbarButtons['back']['url']['action'] = 'Assessments';
        }
    }

    public function onUpdateFieldTrainingCourses(Event $event, array $attr, $action, Request $request) { 
        if ($action == 'add') {
            $TrainingCourses =  TableRegistry::get('training_courses');
            $training_courses_options = $TrainingCourses->find('list', [
                                        'keyField' => 'id',
                                        'valueField' => 'name'
                                    ])
                                    ->toArray();
                
            $attr['options'] = $training_courses_options;
            $attr['onChangeReload'] = 'changeTrainingCourses';
        }
        return $attr;
    }

    /*public function onImportGetUsersId(Event $event, $cellValue)
    {  
        $record = $this->Users->find()->select([$this->Users->aliasField('id')])->where([$this->Users->aliasField('openemis_no') => $cellValue])->first();
        
        $userId = $record->id;
        return $userId;
    }

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {   
        $training_courses = $this->request->query['training_courses'];
        $TrainingSession = TableRegistry::get('training_sessions');
        $TrainingSessionData = $TrainingSession->find()
                                ->where([
                                    $TrainingSession->aliasField('code') => $training_courses,
                                ])->toArray();     
        
        if(!empty($TrainingSessionData)){
            $TrainingSessionsTrainees = TableRegistry::get('training_sessions_trainees');
            $traineeData = $TrainingSessionsTrainees->find()
                            ->where([
                                $TrainingSessionsTrainees->aliasField('training_session_id') => $TrainingSessionData['training_sessions']['id'],
                            ])->toArray();
            $traineeIds = [];
            if (!empty($traineeData)) {
                foreach ($traineeData as $value) {
                   $traineeIds[] = $value->trainee_id;
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
                            ->where([$Users->aliasField('id IN') => $traineeIds]);

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
                        //$name = $row->first_name.' '.$row->middle_name.' '.$row->third_name.' '.$row->last_name; 
                        $data[$columnOrder]['data'][] = [
                            //$name,
                            $row->openemis_no
                        ];
                    }
                }
            }
        }                        
    }*/

    public function onImportGetTrainingResultTypesId(Event $event, $cellValue)
    {  
        return $cellValue;
    }

    public function onImportPopulateTrainingResultTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        $training_courses = $this->request->query['training_courses'];

        $TrainingResultTypes = TableRegistry::get('training_result_types');
        $TrainingCoursesResultTypes = TableRegistry::get('training_courses_result_types');
        $TrainingCoursesResultTypesData = $TrainingCoursesResultTypes->find()
                                        ->select([
                                            $TrainingCoursesResultTypes->aliasField('training_course_id'),
                                            $TrainingCoursesResultTypes->aliasField('training_result_type_id'),
                                            $TrainingResultTypes->aliasField('id'),
                                            $TrainingResultTypes->aliasField('name')
                                        ])
                                        ->leftJoin([$TrainingResultTypes->alias() => $TrainingResultTypes->table()], [
                                            $TrainingResultTypes->aliasField('id = ') . $TrainingCoursesResultTypes->aliasField('training_result_type_id')
                                        ])
                                        ->where([
                                            $TrainingCoursesResultTypes->aliasField('training_course_id') => $training_courses,
                                        ]);   

        if (!empty($TrainingCoursesResultTypesData)) {

            //$translatedReadableCol = $this->getExcelLabel($TrainingCoursesResultTypesData, '');

            $data[$columnOrder]['lookupColumn'] = 1;
            $data[$columnOrder]['data'][] = ['Result Type'];

            $modelData = $TrainingCoursesResultTypesData->find('all')
            ->select([
                $TrainingResultTypes->aliasField('name')
            ]); 

            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->training_result_types['name']
                ];
            }
        }

    }

    public function onImportGetTrainingSessionsId(Event $event, $cellValue)
    {  
        $record = $this->TrainingSessions->find()->select([$this->TrainingSessions->aliasField('id')])->where([$this->TrainingSessions->aliasField('code') => $cellValue])->first();
        
        $trainingSessionsId = $record->id;
        return $trainingSessionsId;
    }

    public function onImportPopulateTrainingSessionsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {   
        $training_courses = $this->request->query['training_courses'];
        
        $TrainingSession = TableRegistry::get('training_sessions');
        $TrainingSessionData = $TrainingSession->find()
                                ->where([
                                    $TrainingSession->aliasField('training_course_id') => $training_courses,
                                ]);

        $translatedReadableCol = $this->getExcelLabel($TrainingSessionData, '');

        $data[$columnOrder]['lookupColumn'] = 1;
        $data[$columnOrder]['data'][] = [$translatedCol, 'Name'];

        $modelData = $TrainingSessionData->find('all')
        ->select([
            'code',
            'name'
        ]);  
                              
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->code,
                    $row->name
                ];
            }
        }
    }
                        
    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
        echo "<pre>"; print_r($tempRow);die;


        return true;
    }
             
        
        
    /*public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
       
        $educationGradeId = $this->request->query['education_grade'];
        $academicPeriodId = $this->AcademicPeriods->getCurrent();
        $institutionId = $this->request->session()->read('Institution.Institutions.id');
        $tempRow['institution_id'] = $institutionId;
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
        
        return true;
    }*/
}