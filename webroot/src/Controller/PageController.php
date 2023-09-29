<?php
namespace App\Controller;

use Cake\Event\Event;
use Page\Controller\PageController as BaseController;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use Cake\Routing\Router;
use Cake\ORM\TableRegistry;//POCOR-7534

class PageController extends BaseController
{
    public $helpers = ['Page.Page'];

    public function initialize()
    {
        parent::initialize();

        $labels = [
            'openemis_no' => 'OpenEMIS ID',
            'modified' => 'Modified On',
            'modified_user_id' => 'Modified By',
            'created' => 'Created On',
            'created_user_id' => 'Created By'
        ];

        $this->Page->config('sequence', 'order');
        $this->Page->config('is_visible', 'visible');
        $this->Page->config('labels', $labels);

        $this->loadComponent('Page.RenderLink');
        $this->loadComponent('RenderDate');
        $this->loadComponent('RenderTime');
        $this->loadComponent('RenderDatetime');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.beforeRender'] = ['callable' => 'beforeRender', 'priority' => 5];
        $events['Controller.Page.onRenderBinary'] = 'onRenderBinary';
        return $events;
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        //POCOR-7534 Starts comment it only for POCOR-7534 ticket's given urls in task
        $session = $this->request->session();
        $superAdmin = $session->read('Auth.User.super_admin');
        if($superAdmin == 0){ 
            $UserData = $session->read('Auth.User')['id'];
            $GroupRoles = TableRegistry::get('Security.SecurityGroupUsers');
            $userRole = $GroupRoles->find()
                        ->contain('SecurityRoles')
                        ->order(['SecurityRoles.order'])
                        ->where([
                            $GroupRoles->aliasField('security_user_id') => $UserData
                        ])
                        ->group([$GroupRoles->aliasField('security_role_id')])
                        ->toArray();
            
            if(!empty($this->request->params['controller']) && !empty($userRole)){
                $RoleIds = [];
                foreach ($userRole as $Role_key => $Role_val) {  $RoleIds[] = $Role_val->security_role_id; }
                $SecurityFunctionIds = $this->getIdBySecurityFunctionName($this->request->params['action'], $this->request->params['controller']);
                if(!empty($SecurityFunctionIds)){
                    $result = $this->checkAuthrizationForRoles($SecurityFunctionIds, $RoleIds);
                    if($result == 0){
                        $event->stopPropagation();
                        $this->Alert->warning('general.notAccess');
                        $this->redirect($this->referer());
                    }
                }
            }
        }//POCOR-7534 Ends

        $page = $this->Page;
        $request = $this->request;
        $action = $request->action;
        $ext = $this->request->params['_ext'];

        if ($ext != 'json') {
            if ($request->is(['put', 'post'])) {
                $page->showElements(true);
            }
            $this->set('menuItemSelected', [$this->name]);

            if ($page->isAutoRender() && in_array($action, ['index', 'view', 'add', 'edit', 'delete'])) {
                $viewFile = 'Page.Page/' . $action;
                $this->viewBuilder()->template($viewFile);
            }
        }
    }
    //POCOR-7534 Starts
    public function getIdBySecurityFunctionName($actionParam, $controllerParam){
        $name = '';
        if($controllerParam == 'Securities'){
            if($actionParam == 'Users'){
                $name = 'Users';
            }else if(($actionParam == 'UserGroups' || $actionParam == 'SystemGroups')){
                $name = 'Groups';  
            }else if($actionParam == 'Roles'){
                $name = ($this->request->query['type'] == 'system') ? 'System Roles' : 'User Roles';
            }else if($actionParam == 'Accounts'){
                $name = 'Accounts';  
            }else if($actionParam == 'UserGroupsList'){
                $name = 'User Group List';  
            }
        }else if($controllerParam == 'Credentials'){
            if($actionParam == 'add' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' ||  $actionParam == 'delete'){
                $name = 'Credentials';  
            }
        }else if($controllerParam == 'Areas'){
            if($actionParam == 'Levels' || $actionParam == 'AdministrativeLevels'){
                $name = 'Area Levels';  
            }else if($actionParam == 'Areas' || $actionParam == 'Administratives'){
                $name = 'Areas';  
            }
        }else if($controllerParam == 'AcademicPeriods'){
            if($actionParam == 'Levels'){
                $name = 'Academic Period Levels';  
            }else if($actionParam == 'Periods'){
                $name = 'Academic Periods';  
            }
        }else if($controllerParam == 'Educations'){
            if($actionParam == 'Systems'){
                $name = 'Education Systems';  
            }else if($actionParam == 'Levels'){
                $name = 'Education Levels';  
            }else if($actionParam == 'Cycles'){
                $name = 'Education Cycles';  
            }else if($actionParam == 'Programmes'){
                $name = 'Education Programmes';  
            }else if($actionParam == 'Grades'){
                $name = 'Education Grades';  
            }else if($actionParam == 'Stages' || $actionParam == 'GradeSubjects'){
                $name = 'Setup';  
            }
        }else if($controllerParam == 'Attendances'){
            if($actionParam == 'StudentMarkTypes' || $actionParam == 'StudentMarkTypeStatuses'){
                $name = 'Attendances';  
            }
        }else if($controllerParam == 'FieldOptions'){
            $actionParam = $this->request->params['pass'][0];
            if(($actionParam == '' || $actionParam == 'index') || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'add'  || $actionParam == 'remove' ||  $actionParam == 'transfer'){
                $name = 'Setup';  
            }
        }else if($controllerParam == 'Labels'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit'){
                $name = 'Labels';  
            }
        }else if($controllerParam == 'Configurations'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit'){
                $name = 'Configurations';  
            }else if($actionParam == 'AuthSystemAuthentications'){
                $name = 'Authentication';  
            }else if($actionParam == 'ExternalDataSource'){
                $name = 'External Data Source';  
            }else if($actionParam == 'ProductLists'){
                $name = 'Product Lists';  
            }else if($actionParam == 'Webhooks'){
                $name = 'Webhooks';  
            }
        }else if($controllerParam == 'Themes'){
            $controllerParam = 'Configurations';
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit'){
                $name = 'Configurations';  
            }
        }else if($controllerParam == 'Notices'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete'){
                $name = 'Notices';  
            }
        }else if($controllerParam == 'Risks'){
            if($actionParam == 'Risks'){
                $name = 'Risks';  
            }
        }else if($controllerParam == 'InstitutionCustomFields'){
            if($actionParam == 'Fields' || $actionParam == 'Pages'){
                $name = 'Institution';  
            }
        }else if($controllerParam == 'StudentCustomFields'){
            if($actionParam == 'Fields' || $actionParam == 'Pages'){
                $name = 'Student';  
            }
        }else if($controllerParam == 'StaffCustomFields'){
            if($actionParam == 'Fields' || $actionParam == 'Pages'){
                $name = 'Staff';  
            }
        }else if($controllerParam == 'Infrastructures'){
            if($actionParam == 'Fields' || $actionParam == 'Pages' || $actionParam == 'LandPages' || $actionParam == 'LandTypes' || $actionParam == 'BuildingPages' || $actionParam == 'BuildingTypes' || $actionParam == 'FloorPages' || $actionParam == 'FloorTypes' || $actionParam == 'RoomPages' || $actionParam == 'RoomTypes'){
                $name = 'Infrastructure';  
            }
        }else if($controllerParam == 'Locales'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete'){
                $name = 'Languages';  
            }
        }else if($controllerParam == 'LocaleContents'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' || $actionParam == 'delete'){
                $name = 'Translations';  
            }
        }else if($controllerParam == 'ProfileTemplates'){
            if($actionParam == 'Institutions' || $actionParam == 'InstitutionProfiles'){
                $name = 'Institutions';  
            }else if($actionParam == 'Staff' || $actionParam == 'StaffProfiles'){
                $name = 'Staff';  
            }else if($actionParam == 'Students' || $actionParam == 'StudentProfiles'){
                $name = 'Students';  
            }else if($actionParam == 'Classes' || $actionParam == 'ClassesProfiles'){
                $name = 'Classes';  
            }
        }else if($controllerParam == 'Surveys'){
            if($actionParam == 'Questions'){
                $name = 'Questions';  
            }else if($actionParam == 'Forms'){
                $name = 'Forms';  
            }else if($actionParam == 'Status'){
                $name = 'Status';  
            }else if($actionParam == 'Rules'){
                $name = 'Rules';  
            }
        }else if($controllerParam == 'Rubrics'){
            if($actionParam == 'Templates' || $actionParam == 'Sections' ||  $actionParam == 'Criterias' || $actionParam == 'Options'){
                $name = 'Setup';  
            }else if($actionParam == 'Status'){
                $name = 'Status';  
            }
        }else if($controllerParam == 'Alerts'){
            if($actionParam == 'Alerts'){
                $name = 'Alerts';  
            }else if($actionParam == 'Logs'){
                $name = 'Logs';  
            }else if($actionParam == 'AlertRules'){
                $name = 'AlertRules';  
            }
        }else if($controllerParam == 'Trainings'){
            if($actionParam == 'Courses'){
                $name = 'Courses';  
            }else if($actionParam == 'Sessions' || $actionParam == 'ImportTrainees'){
                $name = 'Sessions';  
            }else if($actionParam == 'Results' || $actionParam == 'ImportTrainingSessionTraineeResults'){
                $name = 'Results';  
            }else if($actionParam == 'Applications'){
                $name = 'Applications';  
            }
        }else if($controllerParam == 'Competencies'){
            if($actionParam == 'Templates' || $actionParam == 'Items' || $actionParam == 'Criterias'){
                $name = 'Competency Setup';  
            }else if($actionParam == 'Periods'){
                $name = 'Periods';  
            }else if($actionParam == 'GradingTypes'){
                $name = 'GradingTypes';  
            }else if($actionParam == 'ImportCompetencyTemplates'){
                $name = 'Import Competency Templates';  
            }
        }else if($controllerParam == 'Outcomes'){
            if($actionParam == 'Templates' || $actionParam == 'ImportOutcomeTemplates' || $actionParam == 'Criterias'){
                $name = 'Outcome Setup';  
            }else if($actionParam == 'Periods'){
                $name = 'Periods';  
            }else if($actionParam == 'GradingTypes'){
                $name = 'Grading Types';  
            }
        }else if($controllerParam == 'Assessments'){
            if($actionParam == 'Assessments'){
                $name = 'Assessments';  
            }else if($actionParam == 'GradingTypes' || $actionParam == 'GradingOptions'){
                $name = 'Grading Types';  
            }else if($actionParam == 'Status'){
                $name = 'Status';  
            }else if($actionParam == 'AssessmentPeriods'){
                $name = 'Assessment Periods';  
            }
        }else if($controllerParam == 'ReportCards'){
            if($actionParam == 'Templates'){
                $name = 'Templates';  
            }else if($actionParam == 'ReportCardEmail'){
                $name = 'Email Templates';  
            }else if($actionParam == 'Processes'){
                $name = 'Processes';  
            }
        }else if($controllerParam == 'Examinations'){
            if($actionParam == 'Exams'){
                $name = 'Exams';  
            }else if($actionParam == 'GradingTypes'){
                $name = 'Grading Types';  
            }else if($actionParam == 'ExamCentres' || $actionParam == 'ExamCentreExams'){
                $name = 'Exam Centres';  
            }else if($actionParam == 'ImportExaminationCentreRooms'){
                $name = 'Import Examination Rooms';  
            }else if($actionParam == 'RegisteredStudents' || $actionParam == 'RegistrationDirectory' || $actionParam == 'BulkStudentRegistration'){
                $name = 'Registered Students';  
            }else if($actionParam == 'NotRegisteredStudents' || $actionParam == 'RegistrationDirectory' || $actionParam == 'BulkStudentRegistration'){
                $name = 'Not Registered Students';  
            }else if($actionParam == 'ExamResults' || $actionParam == 'Results'){
                $name = 'Results';  
            }else if($actionParam == 'ImportResults'){
                $name = 'Import Results';  
            }else if($actionParam == 'ExamCentreLinkedInstitutions'){
                $name = 'Exam Centre Invigilators';  
            }else if($actionParam == 'ExamCentreInvigilators'){
                $name = 'Exam Centre Linked Institutions';  
            }else if($actionParam == 'ExamCentreSubjects'){
                $name = 'Exam Centre Subjects';  
            }else if($actionParam == 'ExamCentreRooms'){
                $name = 'Exam Centre Rooms';  
            }else if($actionParam == 'ExamCentreStudents'){
                $name = 'Exam Centre Students';  
            }
        }else if($controllerParam == 'Scholarships'){
            if($actionParam == 'Scholarships'){
                $name = 'Scholarships';  
            }else if($actionParam == 'Applications'){
                $name = 'Applications';  
            }else if($actionParam == 'Identities'){
                $name = 'Identities';  
            }else if($actionParam == 'Nationalities'){
                $name = 'Nationalities';  
            }else if($actionParam == 'Contacts'){
                $name = 'Contacts';  
            }else if($actionParam == 'Guardians'){
                $name = 'Guardians';  
            }else if($actionParam == 'Histories'){
                $name = 'Histories';  
            }else if($actionParam == 'RecipientPaymentStructures'){
                $name = 'Payment Structures';  
            }else if($actionParam == 'RecipientPayments'){
                $name = 'Disbursements';  
            }
        }else if($controllerParam == 'UsersDirectory'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit'){
                $name = 'Users Directory';
            }
        }else if($controllerParam == 'ScholarshipRecipients'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit'){
                $name = 'Recipients';
            }
        }else if($controllerParam == 'ScholarshipRecipientInstitutionChoices'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit'){
                $name = 'Institution Choices';
            }
        }else if($controllerParam == 'ScholarshipRecipientCollections'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' ||  $actionParam == 'delete'){
                $name = 'Collections';
            }
        }else if($controllerParam == 'ScholarshipRecipientAcademicStandings'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' ||  $actionParam == 'delete'){
                $name = 'Academic Standings';
            }
        }else if($controllerParam == 'ScholarshipApplicationInstitutionChoices'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' ||  $actionParam == 'delete'){
                $name = 'Institution Choices';
            }
        }else if($controllerParam == 'ScholarshipApplicationAttachments'){
            if($actionParam == '' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' ||  $actionParam == 'delete'){
                $name = 'Application Attachments';
            }
        }else if($controllerParam == 'StaffAppraisals'){
            if($actionParam == 'Criterias'){
                $name = 'Criterias';
            }else if($actionParam == 'Forms' || $actionParam == 'Scores'){
                $name = 'Forms';
            }else if($actionParam == 'Types'){
                $name = 'Types';
            }else if($actionParam == 'Periods'){
                $name = 'Periods';
            }
        }else if($controllerParam == 'Textbooks'){
            if($actionParam == 'Textbooks'){
                $name = 'Textbooks';
            }else if($actionParam == 'ImportTextbooks'){
                $name = 'Import Textbooks';
            }
        }else if($controllerParam == 'Meals'){
            if($actionParam == 'programme'){
                $name = 'Meals Programme';
            }
        }else if($controllerParam == 'Workflows'){
            if($actionParam == 'Workflows'){
                $name = 'Workflows';
            }else if($actionParam == 'Steps'){
                $name = 'Steps';
            }else if($actionParam == 'Statuses'){
                $name = 'Statuses';
            }else if($actionParam == 'Actions'){
                $name = 'Actions';
            }else if($actionParam == 'Rules'){
                $name = 'Rules';
            }
        }else if($controllerParam == 'Systems'){
            if($actionParam == 'Updates'){
                $name = 'Updates';
            }
        }else if($controllerParam == 'Calendars'){
            if($actionParam == 'add' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' ||  $actionParam == 'delete'){
                $name = 'Calendars';
            }
        }else if($controllerParam == 'MoodleApiLog'){
            if($actionParam == 'add' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' ||  $actionParam == 'delete'){
                $name = 'MoodleApi Log';
            }
        }else if($controllerParam == 'Archives'){
            if($actionParam == 'add' || $actionParam == 'index' || $actionParam == 'view' || $actionParam == 'edit' ||  $actionParam == 'delete'){
                $name = 'Archive';
            }
        }
        $module = 'Administration';
        $SecurityFunctionsTbl = TableRegistry::get('security_functions');
        $SecurityFunctionsData = $SecurityFunctionsTbl->find()->where([
                                        $SecurityFunctionsTbl->aliasField('name') => $name,
                                        $SecurityFunctionsTbl->aliasField('controller') => $controllerParam,
                                        $SecurityFunctionsTbl->aliasField('module') => $module
                                    ])->toArray();
        $SecurityFunctionIds = [];
        if(!empty($SecurityFunctionsData)){
            foreach ($SecurityFunctionsData as $Function_key => $Function_val) { $SecurityFunctionIds[] = $Function_val->id; }
        }
        return $SecurityFunctionIds;
    }

    public function checkAuthrizationForRoles($securityFunctionsId, $roleId)
    {
        $SecurityRoleFunctionsTbl = TableRegistry::get('security_role_functions');
        $SecurityRoleFunctionsTblData = $SecurityRoleFunctionsTbl->find()->where([
                                            $SecurityRoleFunctionsTbl->aliasField('security_role_id IN') => $roleId,
                                            $SecurityRoleFunctionsTbl->aliasField('security_function_id IN') => $securityFunctionsId,
                                            $SecurityRoleFunctionsTbl->aliasField('_view') => 1
                                        ])->toArray();
        $flag = 0;
        if(!empty($SecurityRoleFunctionsTblData)){
            $dataArray = [];
            foreach ($SecurityRoleFunctionsTblData as $key => $value) {
                if($value->_view == 1){ $dataArray[] = $value->_view; }
            }
            $flag = count($dataArray);
        }
        return $flag;
    }//POCOR-7534 Ends

    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);
        $this->initializeToolbars();
    }

    public function onRenderBinary(Event $event, Entity $entity, PageElement $element)
    {
        $attributes = $element->getAttributes();
        $type = isset($attributes['type']) ? $attributes['type'] : 'binary';
        $fileNameField = isset($attributes['fileNameField']) ? $attributes['fileNameField'] : 'file_name';
        $fileContentField = $element->getKey();
        if ($type == 'image') {
            if ($this->request->param('_ext') == 'json') {
                $primaryKey = $entity->primaryKey;
                $source = isset($attributes['source']) ? $attributes['source'] : $entity->source();
                if (isset($attributes['keyField'])) {
                    $key = TableRegistry::get($source)->primaryKey();
                    if (!is_array($key)) {
                        $primaryKey = $this->encode([$key => $entity->{$attributes['keyField']}]);
                    }
                }
                if ($entity->{$fileContentField}) {
                    return Router::url([
                        'plugin' => null,
                        '_method' => 'GET',
                        'version' => 'v2',
                        'model' => $source,
                        'controller' => 'Restful',
                        'action' => 'image',
                        'id' => $primaryKey,
                        'fileName' => $fileNameField,
                        'fileContent' => $fileContentField,
                        '_ext' => 'json'
                    ], true);
                }
            } else {
                switch ($this->request->param('action')) {
                    case 'view':
                        $fileName = $entity->{$fileNameField};
                        $pathInfo = pathinfo($fileName);
                        if ($entity->{$fileContentField}) {
                            $file = stream_get_contents($entity->{$fileContentField});
                            rewind($entity->{$fileContentField});
                            $entity->{$fileNameField} = 'data:'.$this->response->getMimeType($pathInfo['extension']).';base64,'. base64_encode($file);
                            return $entity->{$fileNameField};
                        }
                        break;
                    case 'index':
                        $primaryKey = $entity->primaryKey;
                        $source = isset($attributes['source']) ? $attributes['source'] : $entity->source();
                        if (isset($attributes['keyField'])) {
                            $key = TableRegistry::get($source)->primaryKey();
                            if (!is_array($key)) {
                                $primaryKey = $this->encode([$key => $entity->{$attributes['keyField']}]);
                            }
                        }
                        if ($entity->{$fileContentField}) {
                            return Router::url([
                                'plugin' => null,
                                '_method' => 'GET',
                                'version' => 'v2',
                                'model' => $source,
                                'controller' => 'Restful',
                                'action' => 'image',
                                'id' => $primaryKey,
                                'fileName' => $fileNameField,
                                'fileContent' => $fileContentField,
                                '_ext' => 'json'
                            ], true);
                        }
                        break;
                    case 'edit':
                    case 'delete':
                        $fileName = $entity->{$fileNameField};
                        $pathInfo = pathinfo($fileName);
                        if ($entity->{$fileContentField}) {
                            if (is_resource($entity->{$fileContentField})) {
                                $file = stream_get_contents($entity->{$fileContentField});
                            } else {
                                $file = $entity->{$fileContentField};
                            }

                            $returnValue = [
                                'extension' => $pathInfo['extension'],
                                'filename' => $fileName,
                                'src' => 'data:'.$this->response->getMimeType($pathInfo['extension']).';base64,'. base64_encode($file)
                            ];

                            rewind($entity->{$fileContentField});
                            return $returnValue;
                        }
                        break;
                }
            }
        } else {
            switch ($this->request->param('action')) {
                case 'view':
                    $primaryKey = $entity->primaryKey;
                    $source = isset($attributes['source']) ? $attributes['source'] : $entity->source();
                    if (isset($attributes['keyField'])) {
                        $key = TableRegistry::get($source)->primaryKey();
                        if (!is_array($key)) {
                            $primaryKey = $this->encode([$key => $entity->{$attributes['keyField']}]);
                        }
                    }
                    $fileName = $entity->{$fileNameField};
                    $element->setAttributes('file_name', $fileName);
                    if ($entity->{$fileContentField}) {
                        return Router::url([
                            'plugin' => null,
                            '_method' => 'GET',
                            'version' => 'v2',
                            'model' => $source,
                            'controller' => 'Restful',
                            'action' => 'download',
                            'id' => $primaryKey,
                            'fileName' => $fileNameField,
                            'fileContent' => $fileContentField,
                            '_ext' => 'json'
                        ], true);
                    }
                    break;
            }
        }
    }

    private function initializeToolbars()
    {
        $request = $this->request;
        $currentAction = $request->action;

        $page = $this->Page;
        $data = $page->getData();

        $actions = $page->getActions();
        $disabledActions = [];
        foreach ($actions as $action => $value) {
            if ($value == false) {
                $disabledActions[] = $action;
            }
        }

        switch ($currentAction) {
            case 'index':
                if (!in_array('add', $disabledActions)) {
                    $page->addToolbar('add', [
                        'type' => 'element',
                        'element' => 'Page.button',
                        'data' => [
                            'title' => __('Add'),
                            'url' => ['action' => 'add'],
                            'iconClass' => 'fa kd-add',
                            'linkOptions' => ['title' => __('Add')]
                        ],
                        'options' => []
                    ]);
                }
                if (!in_array('search', $disabledActions)) {
                    $page->addToolbar('search', [
                        'type' => 'element',
                        'element' => 'Page.search',
                        'data' => [],
                        'options' => []
                    ]);
                }

                break;
            case 'view':
                $primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

                $page->addToolbar('back', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('Back'),
                        'url' => ['action' => 'index'],
                        'urlParams' => 'QUERY',
                        'iconClass' => 'fa kd-back',
                        'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']
                    ],
                    'options' => []
                ]);

                if (!in_array('edit', $disabledActions)) {
                    $page->addToolbar('edit', [
                        'type' => 'element',
                        'element' => 'Page.button',
                        'data' => [
                            'title' => __('Edit'),
                            'url' => ['action' => 'edit', $primaryKey],
                            'iconClass' => 'fa kd-edit',
                            'linkOptions' => ['title' => __('Edit')]
                        ],
                        'options' => []
                    ]);
                }

                if (!in_array('delete', $disabledActions)) {
                    $page->addToolbar('remove', [
                        'type' => 'element',
                        'element' => 'Page.button',
                        'data' => [
                            'title' => __('Delete'),
                            'url' => ['action' => 'delete', $primaryKey],
                            'iconClass' => 'fa kd-trash',
                            'linkOptions' => ['title' => __('Delete')]
                        ],
                        'options' => []
                    ]);
                }
                break;
            case 'add':
                $page->addToolbar('back', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('Back'),
                        'url' => ['action' => 'index'],
                        'urlParams' => 'QUERY',
                        'iconClass' => 'fa kd-back',
                        'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']
                    ],
                    'options' => []
                ]);
                break;
            case 'edit':
                $primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

                $page->addToolbar('view', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('View'),
                        'url' => ['action' => 'view', $primaryKey],
                        'iconClass' => 'fa kd-back',
                        'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']
                    ],
                    'options' => []
                ]);

                $page->addToolbar('list', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('List'),
                        'url' => ['action' => 'index'],
                        'urlParams' => 'QUERY',
                        'iconClass' => 'fa kd-lists',
                        'linkOptions' => ['title' => __('List')]
                    ],
                    'options' => []
                ]);
                break;
            case 'delete':
                $primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

                $page->addToolbar('view', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('Back'),
                        'url' => ['action' => 'view', $primaryKey],
                        'iconClass' => 'fa kd-back',
                        'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']
                    ],
                    'options' => []
                ]);

                $page->addToolbar('list', [
                    'type' => 'element',
                    'element' => 'Page.button',
                    'data' => [
                        'title' => __('List'),
                        'url' => ['action' => 'index'],
                        'urlParams' => 'QUERY',
                        'iconClass' => 'fa kd-lists',
                        'linkOptions' => ['title' => __('List')]
                    ],
                    'options' => []
                ]);
                break;
            
            default:
                break;
        }
    }
}
