<?php
namespace Institution\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;

use Institution\Controller\AppController;

class InstitutionsController extends AppController
{
    public $activeObj = null;

    public function initialize()
    {
        parent::initialize();

        $this->ControllerAction->model('Institution.Institutions', [], ['deleteStrategy' => 'restrict']);
        $this->ControllerAction->models = [
            'Attachments'       => ['className' => 'Institution.InstitutionAttachments'],
            'History'           => ['className' => 'Institution.InstitutionActivities', 'actions' => ['search', 'index']],

            'Programmes'        => ['className' => 'Institution.InstitutionGrades', 'actions' => ['!search'], 'options' => ['deleteStrategy' => 'restrict']],
            'Infrastructures'   => ['className' => 'Institution.InstitutionInfrastructures', 'options' => ['deleteStrategy' => 'restrict']],
            'Rooms'             => ['className' => 'Institution.InstitutionRooms', 'options' => ['deleteStrategy' => 'restrict']],

            'Staff'             => ['className' => 'Institution.Staff'],
            'StaffUser'         => ['className' => 'Institution.StaffUser', 'actions' => ['add', 'view', 'edit']],
            'StaffAccount'      => ['className' => 'Institution.StaffAccount', 'actions' => ['view', 'edit']],
            'StaffAttendances'  => ['className' => 'Institution.StaffAttendances', 'actions' => ['index']],
            'StaffAbsences'     => ['className' => 'Institution.StaffAbsences'],

            'StaffBehaviours'   => ['className' => 'Institution.StaffBehaviours'],

            'Students'          => ['className' => 'Institution.Students'],
            'StudentUser'       => ['className' => 'Institution.StudentUser', 'actions' => ['add', 'view', 'edit']],
            'StudentAccount'    => ['className' => 'Institution.StudentAccount', 'actions' => ['view', 'edit']],
            'StudentSurveys'    => ['className' => 'Student.StudentSurveys', 'actions' => ['index', 'view', 'edit']],
            'StudentAbsences'   => ['className' => 'Institution.InstitutionStudentAbsences'],
            'StudentAttendances'=> ['className' => 'Institution.StudentAttendances', 'actions' => ['index']],
            'AttendanceExport'  => ['className' => 'Institution.AttendanceExport', 'actions' => ['excel']],
            'StudentBehaviours' => ['className' => 'Institution.StudentBehaviours'],
            'Promotion'         => ['className' => 'Institution.StudentPromotion', 'actions' => ['add']],
            'Transfer'          => ['className' => 'Institution.StudentTransfer', 'actions' => ['index', 'add']],
            'TransferApprovals' => ['className' => 'Institution.TransferApprovals', 'actions' => ['edit', 'view']],
            'StudentDropout'    => ['className' => 'Institution.StudentDropout', 'actions' => ['index', 'edit', 'view']],
            'DropoutRequests'   => ['className' => 'Institution.DropoutRequests', 'actions' => ['add', 'edit', 'remove']],
            'TransferRequests'  => ['className' => 'Institution.TransferRequests', 'actions' => ['index', 'view', 'add', 'edit', 'remove']],
            'StudentAdmission'  => ['className' => 'Institution.StudentAdmission', 'actions' => ['index', 'edit', 'view', 'search']],
            'Undo'              => ['className' => 'Institution.UndoStudentStatus', 'actions' => ['view', 'add']],
            'ClassStudents'     => ['className' => 'Institution.InstitutionClassStudents', 'actions' => ['excel']],

            'BankAccounts'      => ['className' => 'Institution.InstitutionBankAccounts'],

            // Surveys
            'Surveys'           => ['className' => 'Institution.InstitutionSurveys', 'actions' => ['index', 'view', 'edit', 'remove']],

            // Quality
            'Rubrics'           => ['className' => 'Institution.InstitutionRubrics', 'actions' => ['index', 'view', 'remove']],
            'RubricAnswers'     => ['className' => 'Institution.InstitutionRubricAnswers', 'actions' => ['view', 'edit']],
            'Visits'            => ['className' => 'Institution.InstitutionQualityVisits'],

            'ImportInstitutions'        => ['className' => 'Institution.ImportInstitutions', 'actions' => ['add']],
            'ImportStaffAttendances'    => ['className' => 'Institution.ImportStaffAttendances', 'actions' => ['add']],
            'ImportStudentAttendances'  => ['className' => 'Institution.ImportStudentAttendances', 'actions' => ['add']],
            'ImportInstitutionSurveys'  => ['className' => 'Institution.ImportInstitutionSurveys', 'actions' => ['add']],
            'ImportStudents'            => ['className' => 'Institution.ImportStudents', 'actions' => ['add']],
            'ImportStaff'               => ['className' => 'Institution.ImportStaff', 'actions' => ['add']]
        ];

        $this->loadComponent('Institution.InstitutionAccessControl');
        $this->attachAngularModules();
    }

    // CAv4
    public function Positions()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionPositions']); }
    public function Shifts()                { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionShifts']); }
    public function Fees()                  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionFees']); }
    public function StudentFees()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentFees']); }
    public function StaffTransferRequests() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTransferRequests']); }
    public function StaffTransferApprovals(){ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTransferApprovals']); }
    public function StaffPositionProfiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffPositionProfiles']); }
    public function Classes()               { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionClasses']); }
    public function Subjects()              { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionSubjects']); }
    public function Assessments()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionAssessments']); }
    public function StudentProgrammes()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Programmes']); }
    public function Exams()                 { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionExaminations']); }
    public function UndoExaminationRegistration() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionExaminationsUndoRegistration']); }
    public function ExaminationStudents()   { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionExaminationStudents']); }
    public function Contacts()              { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionContacts']); }
    // public function StaffAbsences() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffAbsences']); }
    // End

    // AngularJS
    public function Results()
    {
        $session = $this->request->session();
        $roles = [];

        if (!$this->AccessControl->isAdmin() && $session->check('Institution.Institutions.id')) {
            $userId = $this->Auth->user('id');
            $institutionId = $session->read('Institution.Institutions.id');
            $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
        }

        $this->set('_edit', $this->AccessControl->check(['Institutions', 'Results', 'edit'], $roles));
        $this->set('_excel', $this->AccessControl->check(['Institutions', 'Assessments', 'excel'], $roles));
        $url = $this->ControllerAction->url('index');
        $url['plugin'] = 'Institution';
        $url['controller'] = 'Institutions';
        $url['action'] = 'ClassStudents';
        $url[0] = 'excel';
        $this->set('excelUrl', Router::url($url));
        $this->set('ngController', 'InstitutionsResultsCtrl');
    }
    // End

    public function Students($pass = 'index') {
        if ($pass == 'addExisting') {
            $this->set('ngController', 'InstitutionsStudentsCtrl as InstitutionStudentController');
            $externalDataSource = false;
        	$ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
        	$externalSourceType = $ConfigItemTable->find()->where([$ConfigItemTable->aliasField('code') => 'external_data_source_type'])->first();
        	if (!empty($externalSourceType) && $externalSourceType['value'] != 'None') {
        		$externalDataSource = true;
        	}
        	$this->set('externalDataSource', $externalDataSource);
            $this->render('studentAdd');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Students']);
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.AccessControl.checkIgnoreActions'] = 'checkIgnoreActions';
        return $events;
    }

    public function checkIgnoreActions(Event $event, $controller, $action)
    {
        $ignore = false;
        if ($controller == 'Institutions') {
            $ignoredList = ['downloadFile'];
            if (in_array($action, $ignoredList)) {
                $ignore = true;
            } else {
                $ignoredList = [
                    'StudentUser' => ['downloadFile'],
                    'StaffUser' => ['downloadFile'],
                    'Infrastructures' => ['downloadFile'],
                    'Surveys' => ['downloadFile']
                ];

                if (array_key_exists($action, $ignoredList)) {
                    $pass = $this->request->params['pass'];
                    if (count($pass) > 0 && in_array($pass[0], $ignoredList[$action])) {
                        $ignore = true;
                    }
                }
            }
        }
        return $ignore;
    }

    private function checkInstitutionAccess($id, $event)
    {
        if (!$this->AccessControl->isAdmin()) {
            $institutionIds = $this->AccessControl->getInstitutionsByUser();

            if (!array_key_exists($id, $institutionIds)) {
                $this->Alert->error('security.noAccess');
                $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index'];
                $event->stopPropagation();

                return $this->redirect($url);
            }
        }
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
        $session = $this->request->session();
        $action = $this->request->params['action'];
        $header = __('Institutions');

        // this is to cater for back links
        $query = $this->request->query;

        if (array_key_exists('institution_id', $query)) {
            //check for permission
            $this->checkInstitutionAccess($query['institution_id'], $event);
            if ($event->isStopped()) {
                return false;
            }
            $session->write('Institution.Institutions.id', $query['institution_id']);
        }

        if ($action == 'index') {
            $session->delete('Institution.Institutions');
        }

        if ($session->check('Institution.Institutions.id') || in_array($action, ['view', 'edit', 'dashboard'])) {
            $id = 0;
            if (isset($this->request->pass[0]) && (in_array($action, ['view', 'edit', 'dashboard']))) {
                $id = $this->request->pass[0];
                $this->checkInstitutionAccess($id, $event);
                if ($event->isStopped()) {
                    return false;
                }
                $session->write('Institution.Institutions.id', $id);

            } else if ($session->check('Institution.Institutions.id')) {
                $id = $session->read('Institution.Institutions.id');
            }
            if (!empty($id)) {
                $this->activeObj = $this->Institutions->get($id);
                $name = $this->activeObj->name;
                $session->write('Institution.Institutions.name', $name);
                if ($action == 'view') {
                    $header = $name .' - '.__('Overview');
                } else {
                    $header = $name .' - '.__(Inflector::humanize($action));
                }
                $this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', $id]);
            } else {
                return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
            }
        }

        $this->set('contentHeader', $header);
    }

<<<<<<< HEAD
=======
    private function attachAngularModules()
    {
        $action = $this->request->action;
>>>>>>> origin/master

	private function attachAngularModules() {
		$action = $this->request->action;

		switch ($action) {
			case 'Results':
				$this->Angular->addModules([
					'alert.svc',
					'institutions.results.ctrl',
					'institutions.results.svc'
				]);
				break;
			case 'Surveys':
				$this->Angular->addModules([
					'relevancy.rules.ctrl'
				]);
				$this->set('ngController', 'RelevancyRulesCtrl as RelevancyRulesController');
				break;
            case 'Students':
            	if (isset($this->request->pass[0])) {
            		if ($this->request->param('pass')[0] == 'addExisting') {
	                    $this->Angular->addModules([
	                        'alert.svc',
	                        'institutions.students.ctrl',
	                        'institutions.students.svc'
	                    ]);
	                } elseif ($this->request->param('pass')[0] == 'addExternal') {
	                	$this->Angular->addModules([
	                        'alert.svc',
	                        'institutions.external_students.ctrl',
	                        'institutions.external_students.svc'
	                    ]);
	                }
            	}
                break;
		}
	}

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        if (!is_null($this->activeObj)) {
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
            $action = false;
            $params = $this->request->params;
            // do not hyperlink breadcrumb for Infrastructures and Rooms
            if (isset($params['pass'][0]) && !in_array($model->alias, ['Infrastructures', 'Rooms'])) {
                $action = $params['pass'][0];
            }
            $isDownload = $action == 'downloadFile' ? true : false;

            $alias = $model->alias;
            $crumbTitle = Inflector::humanize(Inflector::underscore($alias));
            $crumbOptions = [];
            if ($action) {
                $crumbOptions = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias];
            }

            $studentModels = [
                'StudentProgrammes' => __('Programmes')
            ];
            if (array_key_exists($alias, $studentModels)) {
                // add Students and student name
                if ($session->check('Student.Students.name')) {
                    $studentName = $session->read('Student.Students.name');
                    $studentId = $session->read('Student.Students.id');

                    // Breadcrumb
                    $this->Navigation->addCrumb('Students', ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'Students']);
                    $this->Navigation->addCrumb($studentName, ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $studentId]);
                    $this->Navigation->addCrumb($studentModels[$alias]);

                    // header name
                    $header = $studentName;
                }
            } else {
                $this->Navigation->addCrumb($crumbTitle, $crumbOptions);
                $header = $this->activeObj->name;
            }

            $persona = false;
            $requestQuery = $this->request->query;
            if (isset($params['pass'][1])) {
                if ($model->table() == 'security_users' && !$isDownload) {
                    $persona = $model->get($params['pass'][1]);
                }
            } else if (isset($requestQuery['user_id'][1])) {
                $persona = $model->Users->get($requestQuery['user_id']);
            }

            if (is_object($persona) && get_class($persona)=='User\Model\Entity\User') {
                $header = $persona->name . ' - ' . $model->getHeader($alias);
                $model->addBehavior('Institution.InstitutionUserBreadcrumbs');
            } else {
                $header .= ' - ' . $model->getHeader($alias);
            }

            $event = new Event('Model.Navigation.breadcrumb', $this, [$this->request, $this->Navigation, $persona]);
            $event = $model->eventManager()->dispatch($event);

            if ($model->hasField('institution_id')) {
                if (!in_array($model->alias(), ['TransferRequests'])) {
                    $model->fields['institution_id']['type'] = 'hidden';
                    $model->fields['institution_id']['value'] = $institutionId;
                }

                if (count($this->request->pass) > 1) {
                    $modelId = $this->request->pass[1]; // id of the sub model
                    $exists = false;

                    if (in_array($model->alias(), ['TransferRequests', 'StaffTransferApprovals'])) {
                        $exists = $model->exists([
                            $model->aliasField($model->primaryKey()) => $modelId,
                            $model->aliasField('previous_institution_id') => $institutionId
                        ]);
                    } else if (in_array($model->alias(), ['InstitutionShifts'])) { //this is to show information for the occupier
                        $exists = $model->exists([
                            $model->aliasField($model->primaryKey()) => $modelId,
                            'OR' => [ //logic to check institution_id or location_institution_id equal to $institutionId
                                $model->aliasField('institution_id') => $institutionId,
                                $model->aliasField('location_institution_id') => $institutionId
                            ]
                        ]);
                    } else {
                        $primaryKey = $this->ControllerAction->getPrimaryKey($model);
                        $checkExists = function($model, $params) {
                            return $model->exists($params);
                        };

                        $event = $model->dispatchEvent('Model.isRecordExists', [], $this);
                        if (is_callable($event->result)) {
                            $checkExists = $event->result;
                        }
                        $exists = $checkExists($model, [$primaryKey => $modelId, 'institution_id' => $institutionId]);
                    }

                    /**
                     * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                     */

                    // replaced 'action' => $alias to 'action' => $model->alias, since only the name changes but not url
                    if (!$exists && !$isDownload) {
                        $this->Alert->warning('general.notExists');
                        return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);
                    }
                }
            }

            $this->set('contentHeader', $header);
        } else {
            if ($model->alias() == 'ImportInstitutions') {
                $this->Navigation->addCrumb($model->getHeader($model->alias()));
                $header = __('Institutions') . ' - ' . $model->getHeader($model->alias());
                $this->set('contentHeader', $header);
            } else {
                $this->Alert->warning('general.notExists');
                $event->stopPropagation();
                return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
            }
        }
    }

    public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options)
    {
        $session = $this->request->session();

        if (!$this->request->is('ajax')) {
            if ($model->hasField('institution_id')) {
                if (!$session->check('Institution.Institutions.id')) {
                    $this->Alert->error('general.notExists');
                    // should redirect
                } else {
                    if ($model->alias() != 'Programmes') {
                        $query->where([$model->aliasField('institution_id') => $session->read('Institution.Institutions.id')]);
                    }
                }
            }
        }
    }

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    public function excel($id=0)
    {
        $this->Institutions->excel($id);
        $this->autoRender = false;
    }

    public function dashboard($id)
    {
        $this->ControllerAction->model->action = $this->request->action;

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $currentPeriod = $AcademicPeriods->getCurrent();
        if (empty($currentPeriod)) {
            $this->Alert->warning('Institution.Institutions.academicPeriod');
        }

        // $highChartDatas = ['{"chart":{"type":"column","borderWidth":1},"xAxis":{"title":{"text":"Position Type"},"categories":["Non-Teaching","Teaching"]},"yAxis":{"title":{"text":"Total"}},"title":{"text":"Number Of Staff"},"subtitle":{"text":"For Year 2015-2016"},"series":[{"name":"Male","data":[0,2]},{"name":"Female","data":[0,1]}]}'];
        $highChartDatas = [];
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();

        //Students By Year, excludes transferred and dropoout students
        $params = array(
            'conditions' => array('institution_id' => $id, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['DROPOUT']])
        );

        $InstitutionStudents = TableRegistry::get('Institution.Students');
        $highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_year', $params);

        //Students By Grade for current year, excludes transferred and dropoout students
        $params = array(
            'conditions' => array('institution_id' => $id, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['DROPOUT']])
        );

        $highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_grade', $params);

        $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
        $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');

        //Staffs By Position for current year, only shows assigned staff
        $params = array(
            'conditions' => array('institution_id' => $id, 'staff_status_id' => $assignedStatus)
        );
        $InstitutionStaff = TableRegistry::get('Institution.Staff');
        $highChartDatas[] = $InstitutionStaff->getHighChart('number_of_staff', $params);

        $this->set('highChartDatas', $highChartDatas);
    }

    //autocomplete used for InstitutionSiteShift
    public function ajaxInstitutionAutocomplete()
    {
        $this->ControllerAction->autoRender = false;
        $data = [];
        $Institutions = TableRegistry::get('Institution.Institutions');
        if ($this->request->is(['ajax'])) {
            $term = trim($this->request->query['term']);
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
            $params['conditions'] = [$Institutions->aliasField('id').' IS NOT ' => $institutionId];
            if (!empty($term))
                $data = $Institutions->autocomplete($term, $params);

            echo json_encode($data);
            die;
        }
    }

    public function getUserTabElements($options = [])
    {
        $userRole = (array_key_exists('userRole', $options))? $options['userRole']: null;
        $action = (array_key_exists('action', $options))? $options['action']: 'add';
        $id = (array_key_exists('id', $options))? $options['id']: 0;
        $userId = (array_key_exists('userId', $options))? $options['userId']: 0;
        $type = 'Students';

        switch ($userRole) {
            case 'Staff':
                $pluralUserRole = 'Staff'; // inflector unable to handle
                $type = 'Staff';
                break;
            default:
                $pluralUserRole = Inflector::pluralize($userRole);
                break;
        }

        $url = ['plugin' => $this->plugin, 'controller' => $this->name];
        $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];

        $tabElements = [
            $pluralUserRole => ['text' => __('Academic')],
            $userRole.'User' => ['text' => __('Overview')],
            $userRole.'Account' => ['text' => __('Account')],

            // $userRole.'Nationality' => ['text' => __('Identities')],
        ];

        $studentTabElements = [
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' => ['url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Nationalities', $id], 'text' => __('Nationalities'), 'urlModel' => 'Nationalities'],
            'Contacts' => ['text' => __('Contacts')],
            'Guardians' => ['text' => __('Guardians')],
            'Languages' => ['text' => __('Languages')],
            'SpecialNeeds' => ['text' => __('Special Needs')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')],
            'History' => ['text' => __('History')],
        ];

        if ($type == 'Staff') {
            $studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
            unset($studentTabElements['Guardians']);
        }

        $tabElements = array_merge($tabElements, $studentTabElements);

        if ($action == 'add') {
            $tabElements[$pluralUserRole]['url'] = array_merge($url, ['action' => $pluralUserRole, 'add']);
            $tabElements[$userRole.'User']['url'] = array_merge($url, ['action' => $userRole.'User', 'add']);
            $tabElements[$userRole.'Account']['url'] = array_merge($url, ['action' => $userRole.'Account', 'add']);
        } else {
            unset($tabElements[$pluralUserRole]);
            // $tabElements[$pluralUserRole]['url'] = array_merge($url, ['action' => $pluralUserRole, 'view']);
            $tabElements[$userRole.'User']['url'] = array_merge($url, ['action' => $userRole.'User', 'view']);
            $tabElements[$userRole.'Account']['url'] = array_merge($url, ['action' => $userRole.'Account', 'view']);

            // $tabElements[$userRole.'Account']['url'] = array_merge($url, ['action' => $userRole.'Account', 'view']);

            // Only Student has Survey tab
            if ($userRole == 'Student') {
                $tabElements[$userRole.'Surveys'] = ['text' => __('Survey')];
                $tabElements[$userRole.'Surveys']['url'] = array_merge($url, ['action' => $userRole.'Surveys', 'index']);
            }

            foreach ($studentTabElements as $key => $value) {
                $urlModel = (array_key_exists('urlModel', $value))? $value['urlModel'] : $key;
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>$urlModel, 'index']);
            }
        }

        foreach ($tabElements as $key => $tabElement) {
            switch ($key) {
                case $userRole.'User':
                    $params = [$userId];
                    break;
                case $userRole.'Account':
                    $params = [$userId];
                    break;
                case $userRole.'Surveys':
                    $params = ['user_id' => $userId];
                    break;
                default:
                    $params = [];
            }
            $tabElements[$key]['url'] = array_merge($tabElements[$key]['url'], $params);
        }

        $session = $this->request->session();
        $session->write('Institution.'.$type.'.tabElements', $tabElements);

        return $tabElements;
    }

    public function getAcademicTabElements($options = [])
    {
        $id = (array_key_exists('id', $options))? $options['id']: 0;
        $type = (array_key_exists('type', $options))? $options['type']: null;

        $tabElements = [];
        $studentTabElements = [
            'Programmes' => ['text' => __('Programmes')],
            'Classes' => ['text' => __('Classes')],
            'Subjects' => ['text' => __('Subjects')],
            'Absences' => ['text' => __('Absences')],
            'Behaviours' => ['text' => __('Behaviours')],
            'Results' => ['text' => __('Results')],
            'Awards' => ['text' => __('Awards')],
            'Extracurriculars' => ['text' => __('Extracurriculars')],
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        // Programme will use institution controller, other will be still using student controller
        foreach ($studentTabElements as $key => $tab) {
            if ($key == 'Programmes') {
                $studentUrl = ['plugin' => 'Institution', 'controller' => 'Institutions'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', 'type' => $type]);
            } else {
                $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>$key, 'index']);
            }
        }
        return $tabElements;
    }
}
