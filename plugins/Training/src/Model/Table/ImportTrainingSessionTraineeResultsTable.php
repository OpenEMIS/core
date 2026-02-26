<?php
namespace Training\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
// use Cake\Network\Request;
use Cake\Http\ServerRequest;
use DateTime;
use PHPExcel_Worksheet;
use Cake\Utility\Inflector;

class ImportTrainingSessionTraineeResultsTable extends AppTable
{
    private $institutionId = false;

    public function initialize(array $config): void {
        $this->setTable('import_mapping');
        parent::initialize($config);
        $this->addBehavior('Import.Import', [
            'plugin'=>'Training', 
            'model'=>'TrainingSessionTraineeResults',
            'backUrl' => ['plugin' => 'Training', 'controller' => 'Trainings', 'action' => 'Results']
        ]);
        // register table once
        $this->Users = TableRegistry::getTableLocator()->get('User.Users');
        $this->TrainingSessions = TableRegistry::getTableLocator()->get('Training.TrainingSessions');
        $this->TrainingSessionTraineeResults = TableRegistry::getTableLocator()->get('Training.TrainingSessionTraineeResults');
    }

    public function beforeAction($event) {
        $session = $this->request->getSession();
        $this->systemDateFormat = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('date_format');
    }

    /**
     * Merge session-stored add form params into $this->request so getData(alias) has training_courses, education_subject.
     * Template download is a separate request; this makes request look like the add form request.
     * POCOR-9566
     */
    
    private function ensureAddParamsInRequest(): void
    {
        $alias = $this->getAlias();
        if ($this->request->getData($alias) && !empty($this->request->getData($alias)['training_courses'])) {
            return; // already in request
        }
        $session = $this->request->getSession();
        $sessionKey = $this->getRegistryAlias() . '.add_params';
        if (!$session->check($sessionKey)) {
            return;
        }
        $params = $session->read($sessionKey);
        $parsed = $this->request->getParsedBody();
        if (!is_array($parsed)) {
            $parsed = [];
        }
        $parsed[$alias] = array_merge($parsed[$alias] ?? [], $params);
        $this->request = $this->request->withParsedBody($parsed);
    }

    public function implementedEvents(): array { 
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

    public function onGetBreadcrumb(EventInterface $event, Request $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->getAlias());
        $url = ['plugin' => 'Training', 'controller' => 'Trainings', 'action' => 'TrainingSessionTraineeResults'];

        $Navigation->substituteCrumb($crumbTitle, 'TrainingSessionTraineeResults', $url);
        $Navigation->addCrumb($crumbTitle);
    }

    public function onImportCheckUnique(EventInterface $event, $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
        $tempRow['entity'] = $this->TrainingSessionTraineeResults->newEntity([]);
    }

    public function onImportUpdateUniqueKeys(EventInterface $event, ArrayObject $importedUniqueCodes, Entity $entity) {}

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        if (isset($buttons[1])) {
            $buttons[1]['url'] = $this->ControllerAction->url('Results');
        }
        $trainingCourses = $this->request->getQuery('training_courses');
        if ($trainingCourses === null || $trainingCourses === '') {
            $alias = $this->getAlias();
            $requestData = $this->request->getData($alias);
            $trainingCourses = is_array($requestData) && isset($requestData['training_courses']) ? $requestData['training_courses'] : null;
        }
        if ($trainingCourses === null || $trainingCourses === '') {
            $session = $this->request->getSession();
            $sessionKey = $this->getRegistryAlias() . '.add_params';
            if ($session->check($sessionKey)) {
                $params = $session->read($sessionKey);
                $trainingCourses = $params['training_courses'] ?? null;
            }
        }
        if (empty($trainingCourses)) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    { 
        $request = $this->request;
        unset($request->getQuery['training_courses']);
    }

    public function addAfterAction(EventInterface $event, Entity $entity)
    {  
        $this->dependency = [];
        $this->dependency["training_courses"] = ["select_file"];

        $this->ControllerAction->field('training_courses', ['type' => 'select']);
        $this->ControllerAction->field('select_file', ['visible' => true]);
        $this->ControllerAction->setFieldOrder(['training_courses', 'select_file']);

        //Assumption - onChangeReload must be named in this format: change<field_name>. E.g changeClass
        $currentFieldName = strtolower(str_replace("change", "", $entity->submit));
        if (isset($this->request->getData()[$this->getAlias()])) {

            $unsetFlag = false;
            $aryRequestData = $this->request->getData()[$this->getAlias()];

            // Store in session so template download request can have same data in $this->request
            $session = $this->request->getSession();
            $sessionKey = $this->getRegistryAlias() . '.add_params';
            $session->write($sessionKey, [
                'training_courses' => $aryRequestData['training_courses'] ?? null,
                'education_subject' => $aryRequestData['education_subject'] ?? null,
            ]);

            foreach ($aryRequestData as $requestData => $value) {
                if (isset($this->dependency[$requestData]) && $value) {
                    $aryDependencies = $this->dependency[$requestData];
                    $requestDataArray = $this->request->getData()[$this->getAlias()]; // Get request data
                    foreach ($aryDependencies as $dependency) {
                         $this->request = $this->request->withQueryParams($requestDataArray); // Set modified query parameters
                        $this->ControllerAction->field($dependency, ['visible' => true]);
                    }
                }
            }
        }
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {  
        if (isset($toolbarButtons['back'])) {
            $toolbarButtons['back']['url'] = $this->ControllerAction->url('Results');
        }
    }

    public function onUpdateFieldTrainingCourses(EventInterface $event, array $attr, $action, ServerRequest $request) { 
        if ($action == 'add') {
            $TrainingCourses =  TableRegistry::getTableLocator()->get('Training.TrainingCourses');
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

    public function addEditOnChangeTrainingCourses(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->getAlias();
        $training_courses_id = $data[$alias]['training_courses'];
        $data['training_courses'] = $training_courses_id;
        $this->request = $this->request->withQueryParams(['training_courses' => $training_courses_id]);

    }

    public function onImportGetTrainingResultTypesId(EventInterface $event, $cellValue)
    {  
        $this->ensureAddParamsInRequest();
        return $cellValue;
    }

    public function onImportPopulateTrainingResultTypesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        $this->ensureAddParamsInRequest();
        $requestData = $this->request->getData($this->getAlias());
        $training_courses = is_array($requestData) && isset($requestData['training_courses']) ? $requestData['training_courses'] : $this->request->getQuery('training_courses');
        if (empty($training_courses)) {
            $data[$columnOrder]['lookupColumn'] = 1;
            $data[$columnOrder]['data'][] = ['Result Type'];
            return;
        }

        $TrainingResultTypes = TableRegistry::getTableLocator()->get('Training.TrainingResultTypes');
        $TrainingCoursesResultTypes = TableRegistry::getTableLocator()->get('Training.TrainingCoursesResultTypes');
        $TrainingCoursesResultTypesData = $TrainingCoursesResultTypes->find()
                                        ->select([
                                            $TrainingCoursesResultTypes->aliasField('training_course_id'),
                                            $TrainingCoursesResultTypes->aliasField('training_result_type_id'),
                                            $TrainingResultTypes->aliasField('id'),
                                            $TrainingResultTypes->aliasField('name')
                                        ])
                                        ->leftJoin([$TrainingResultTypes->getAlias() => $TrainingResultTypes->getTable()], [
                                            $TrainingResultTypes->aliasField('id = ') . $TrainingCoursesResultTypes->aliasField('training_result_type_id')
                                        ])
                                        ->where([
                                            $TrainingCoursesResultTypes->aliasField('training_course_id') => $training_courses,
                                        ]);   

        if (!empty($TrainingCoursesResultTypesData)) {

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

    public function onImportGetTrainingSessionsId(EventInterface $event, $cellValue)
    {  
        $this->ensureAddParamsInRequest();
        $record = $this->TrainingSessions->find()->select([$this->TrainingSessions->aliasField('id')])->where([$this->TrainingSessions->aliasField('code') => $cellValue])->first();
        
        $trainingSessionsId = $record->id;
        return $trainingSessionsId;
    }

    public function onImportPopulateTrainingSessionsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {   
        $this->ensureAddParamsInRequest();
        $requestData = $this->request->getData($this->getAlias());
        $training_courses = is_array($requestData) && isset($requestData['training_courses']) ? $requestData['training_courses'] : $this->request->getQuery('training_courses');
        if (empty($training_courses)) {
            $data[$columnOrder]['lookupColumn'] = 1;
            $data[$columnOrder]['data'][] = [$translatedCol, 'Name'];
            return;
        }

        $TrainingSession = TableRegistry::getTableLocator()->get('Training.TrainingSessions');
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
                        
    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
        $this->ensureAddParamsInRequest();
        $openemis_no = $tempRow['OpenEMIS_ID'];
        $training_session_id = $tempRow['training_session'];
        $tempRow['training_session_id'] = $training_session_id;
        $Users = TableRegistry::getTableLocator()->get('User.Users');
        $userData = $Users->find()
                        ->where([$Users->aliasField('openemis_no') => $openemis_no])
                        ->first();
        if(!empty($userData)){
            $tempRow['trainee_id'] = $userData->id;
        }

        $TrainingSessionTraineeResults = TableRegistry::getTableLocator()->get('Training.TrainingSessionTraineeResults');
        $TraineeData = $TrainingSessionTraineeResults->find()
                        ->where([$TrainingSessionTraineeResults->aliasField('trainee_id') => $userData->id,  $TrainingSessionTraineeResults->aliasField('training_session_id') => $training_session_id])
                        ->first();

        if(!empty($TraineeData)){
            $tempRow['id'] = $TraineeData->id;

            if($tempRow['result_types'] == 'Attendance'){
                $tempRow['attendance_days'] = $tempRow['results'];
            }else if($tempRow['result_types'] == 'Practical'){
                $tempRow['practical'] = $tempRow['results'];
            }else if($tempRow['result_types'] == 'Certificate'){
                $tempRow['certificate_number'] = $tempRow['results'];
            }else{ //Exam
                $tempRow['result'] = $tempRow['results'];
            }
        }     
        $tempRow['training_result_type_id'] = 0;          
        return true;
    }
}