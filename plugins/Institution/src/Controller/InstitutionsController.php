<?php
namespace Institution\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use Cake\I18n\Date;
use ControllerAction\Model\Traits\UtilityTrait;
use App\Model\Traits\OptionsTrait;
use Institution\Controller\AppController;

class InstitutionsController extends AppController
{
    use OptionsTrait;
    use UtilityTrait;
    public $activeObj = null;

    public function initialize()
    {
        parent::initialize();

        $this->ControllerAction->model('Institution.Institutions', [], ['deleteStrategy' => 'restrict']);
        $this->ControllerAction->models = [
            'Attachments'       => ['className' => 'Institution.InstitutionAttachments'],
            'History'           => ['className' => 'Institution.InstitutionActivities', 'actions' => ['search', 'index']],

            'Infrastructures'   => ['className' => 'Institution.InstitutionInfrastructures', 'options' => ['deleteStrategy' => 'restrict']],
            'Rooms'             => ['className' => 'Institution.InstitutionRooms', 'options' => ['deleteStrategy' => 'restrict']],

            'Staff'             => ['className' => 'Institution.Staff'],
            'StaffAccount'      => ['className' => 'Institution.StaffAccount', 'actions' => ['view', 'edit']],
            'StaffAttendances'  => ['className' => 'Institution.StaffAttendances', 'actions' => ['index']],

            'StaffBehaviours'   => ['className' => 'Institution.StaffBehaviours'],
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
            'StudentAdmission'  => ['className' => 'Institution.StudentAdmission', 'actions' => ['index', 'edit', 'view', 'search']],
            'Undo'              => ['className' => 'Institution.UndoStudentStatus', 'actions' => ['view', 'add']],
            'ClassStudents'     => ['className' => 'Institution.InstitutionClassStudents', 'actions' => ['excel']],

            'BankAccounts'      => ['className' => 'Institution.InstitutionBankAccounts'],

            // Surveys
            'Surveys'           => ['className' => 'Institution.InstitutionSurveys', 'actions' => ['index', 'view', 'edit', 'remove']],

            // Quality
            'Rubrics'           => ['className' => 'Institution.InstitutionRubrics', 'actions' => ['index', 'view', 'remove']],
            'RubricAnswers'     => ['className' => 'Institution.InstitutionRubricAnswers', 'actions' => ['view', 'edit']],

            'ImportInstitutions'        => ['className' => 'Institution.ImportInstitutions', 'actions' => ['add']],
            'ImportStaffAttendances'    => ['className' => 'Institution.ImportStaffAttendances', 'actions' => ['add']],
            'ImportStudentAttendances'  => ['className' => 'Institution.ImportStudentAttendances', 'actions' => ['add']],
            'ImportInstitutionSurveys'  => ['className' => 'Institution.ImportInstitutionSurveys', 'actions' => ['add']],
            'ImportStudents'            => ['className' => 'Institution.ImportStudents', 'actions' => ['add']],
            'ImportStaff'               => ['className' => 'Institution.ImportStaff', 'actions' => ['add']],
            'ImportTextbooks'           => ['className' => 'Institution.ImportTextbooks', 'actions' => ['add']]
        ];

        $this->loadComponent('Institution.InstitutionAccessControl');
        $this->loadComponent('Training.Training');
        $this->loadComponent('Institution.UserOpenEMISID');
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
    public function IndividualPromotion()   { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.IndividualPromotion']); }
    public function StudentUser()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentUser']); }
    public function StaffUser()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffUser']); }
    public function TransferRequests()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.TransferRequests']); }
    public function StaffTrainingResults()  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTrainingResults']); }
    public function StaffTrainingNeeds()    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTrainingNeeds']); }
    public function StaffTrainingApplications()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTrainingApplications']); }
    public function CourseCatalogue()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.CourseCatalogue']); }
    public function StaffLeave()            { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffLeave']); }
    public function VisitRequests()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.VisitRequests']); }
    public function Visits()                { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionQualityVisits']); }
    public function StaffAppraisals()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffAppraisals']); }
    public function Programmes()            { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionGrades']); }
    public function StaffAbsences()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffAbsences']); }
    public function Textbooks()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionTextbooks']); }
    // public function StaffAttendances()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffAttendances']); }
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
        if ($pass == 'add') {
            $session = $this->request->session();
            $roles = [];

            if (!$this->AccessControl->isAdmin() && $session->check('Institution.Institutions.id')) {
                $userId = $this->Auth->user('id');
                $institutionId = $session->read('Institution.Institutions.id');
                $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
            }
            $this->set('ngController', 'InstitutionsStudentsCtrl as InstitutionStudentController');
            $this->set('_createNewStudent', $this->AccessControl->check(['Institutions', 'getUniqueOpenemisId'], $roles));
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

    public function Staff($pass = 'index') {
        if ($pass == 'add') {
            $session = $this->request->session();
            $roles = [];

            if (!$this->AccessControl->isAdmin() && $session->check('Institution.Institutions.id')) {
                $userId = $this->Auth->user('id');
                $institutionId = $session->read('Institution.Institutions.id');
                $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
            }
            $this->set('ngController', 'InstitutionsStaffCtrl as InstitutionStaffController');
            $this->set('_createNewStaff', $this->AccessControl->check(['Institutions', 'getUniqueOpenemisId'], $roles));
            $externalDataSource = false;
            $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
            $externalSourceType = $ConfigItemTable->find()->where([$ConfigItemTable->aliasField('code') => 'external_data_source_type'])->first();
            if (!empty($externalSourceType) && $externalSourceType['value'] != 'None') {
                $externalDataSource = true;
            }
            $this->set('externalDataSource', $externalDataSource);
            $this->render('staffAdd');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Staff']);
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        $pass = $this->request->pass;
        if (isset($pass[0]) && $pass[0] == 'downloadFile') {
            return true;
        }
    }

    public function changeUserHeader($model, $modelAlias, $userType)
    {
        $session = $this->request->session();
        // add the student name to the header
        $id = 0;
        if ($session->check('Staff.Staff.id')) {
            $id = $session->read('Staff.Staff.id');
        }
        if (!empty($id)) {
            $Users = TableRegistry::get('Users');
            $entity = $Users->get($id);
            $name = $entity->name;
            $crumb = Inflector::humanize(Inflector::underscore($modelAlias));
            $header = $name . ' - ' . __($crumb);
            $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
            $this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $userType, 'view', $this->ControllerAction->paramsEncode(['id' => $id])]);
            $this->Navigation->addCrumb($crumb);
            $this->set('contentHeader', $header);
        }
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
                $id = $this->ControllerAction->paramsDecode($id)['id'];
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
                } elseif ($action == 'Results') {
                    $header = $name .' - '.__('Assessments');
                } else {
                    $header = $name .' - '.__(Inflector::humanize($action));
                }
                $this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', $this->ControllerAction->paramsEncode(['id' => $id])]);
            } else {
                return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
            }
        }

        $this->set('contentHeader', $header);

    }

    public function getUniqueOpenemisId()
    {
        $this->autoRender = false;
        echo $this->UserOpenEMISID->getUniqueOpenemisId();
    }

	private function attachAngularModules()
    {
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
            		if ($this->request->param('pass')[0] == 'add') {
	                    $this->Angular->addModules([
	                        'alert.svc',
	                        'institutions.students.ctrl',
	                        'institutions.students.svc'
	                    ]);
	                }
            	}
                break;
            case 'Staff':
                if (isset($this->request->pass[0])) {
                    if ($this->request->param('pass')[0] == 'add') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'institutions.staff.ctrl',
                            'institutions.staff.svc'
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
                    $this->Navigation->addCrumb($studentName, ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $studentId])]);
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
                    $ids = $this->ControllerAction->paramsDecode($params['pass'][1]);
                    $persona = $model->get($ids);
                }
            } else if (isset($requestQuery['user_id'][1])) {
                $persona = $model->Users->get($requestQuery['user_id']);
            }

            if (is_object($persona) && get_class($persona)=='User\Model\Entity\User') {
                $header = $persona->name . ' - ' . $model->getHeader($alias);
                $model->addBehavior('Institution.InstitutionUserBreadcrumbs');
            } else if ($model->alias() == 'IndividualPromotion') {
                $header .= ' - '. __('Individual Promotion / Repeat');
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
                    $modelIds = $this->request->pass[1]; // id of the sub model
                    $primaryKey = $model->primaryKey();
                    $modelIds = $this->ControllerAction->paramsDecode($modelIds);
                    $params = [];
                    if (is_array($primaryKey)) {
                        foreach ($primaryKey as $key) {
                            $params[$model->aliasField($key)] = $modelIds[$key];
                        }
                    } else {
                        $params[$primaryKey] = $modelIds[$primaryKey];
                    }


                    $exists = false;

                    if (in_array($model->alias(), ['TransferRequests', 'StaffTransferApprovals'])) {
                        $params[$model->aliasField('previous_institution_id')] = $institutionId;
                        $exists = $model->exists($params);
                    } else if (in_array($model->alias(), ['InstitutionShifts'])) { //this is to show information for the occupier
                        $params['OR'] = [
                            $model->aliasField('institution_id') => $institutionId,
                            $model->aliasField('location_institution_id') => $institutionId
                        ];
                        $exists = $model->exists($params);
                    } else {
                        $checkExists = function($model, $params) {
                            return $model->exists($params);
                        };

                        $event = $model->dispatchEvent('Model.isRecordExists', [], $this);
                        if (is_callable($event->result)) {
                            $checkExists = $event->result;
                        }
                        $params[$model->aliasField('institution_id')] = $institutionId;
                        $exists = $checkExists($model, $params);
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
        $id = $this->ControllerAction->paramsDecode($id)['id'];
        $this->ControllerAction->model->action = $this->request->action;

        $classification = $this->Institutions->get($id)->classification;
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $currentPeriod = $AcademicPeriods->getCurrent();
        if (empty($currentPeriod)) {
            $this->Alert->warning('Institution.Institutions.academicPeriod');
        }

        // $highChartDatas = ['{"chart":{"type":"column","borderWidth":1},"xAxis":{"title":{"text":"Position Type"},"categories":["Non-Teaching","Teaching"]},"yAxis":{"title":{"text":"Total"}},"title":{"text":"Number Of Staff"},"subtitle":{"text":"For Year 2015-2016"},"series":[{"name":"Male","data":[0,2]},{"name":"Female","data":[0,1]}]}'];
        $highChartDatas = [];

        // only show student charts if institution is academic
        if ($classification) {
            $InstitutionStudents = TableRegistry::get('Institution.Students');
            $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
            $statuses = $StudentStatuses->findCodeList();

            //Students By Year, excludes transferred and dropoout students
            $params = array(
                'conditions' => array('institution_id' => $id, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['DROPOUT']])
            );

            $highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_year', $params);

            //Students By Grade for current year, excludes transferred and dropoout students
            $params = array(
                'conditions' => array('institution_id' => $id, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['DROPOUT']])
            );

            $highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_grade', $params);
        }

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
            $params = [];
            switch ($key) {
                case $userRole.'User':
                    $params = [$this->ControllerAction->paramsEncode(['id' =>$userId]), 'id' => $id];
                    break;
                case $userRole.'Account':
                    $params = [$this->ControllerAction->paramsEncode(['id' =>$userId]), 'id' => $id];
                    break;
                case $userRole.'Surveys':
                    $params = ['user_id' => $userId];
                    break;
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
            'Results' => ['text' => __('Assessments')],
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

    public function getCareerTabElements($options = []) {
        $options['url'] = ['plugin' => 'Institution', 'controller' => 'Institutions'];
        return TableRegistry::get('Staff.Staff')->getCareerTabElements($options);
    }

    public function getTrainingTabElements($options = []) {
        $tabElements = [];
        $trainingUrl = ['plugin' => 'Institution', 'controller' => 'Institutions'];
        $trainingTabElements = [
            'StaffTrainingResults' => ['text' => __('Results')],
            'StaffTrainingApplications' => ['text' => __('Applications')],
            'StaffTrainingNeeds' => ['text' => __('Needs')]
        ];

        $tabElements = array_merge($tabElements, $trainingTabElements);

        foreach ($trainingTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($trainingUrl, ['action' => $key, 'index']);
        }
        return $tabElements;
    }

    public function getProfessionalDevelopmentTabElements($options = []) {
        $options['url'] = ['plugin' => 'Institution', 'controller' => 'Institutions'];
        return TableRegistry::get('Staff.Staff')->getProfessionalDevelopmentTabElements($options);
    }

    public function getInstitutionPositions($institutionId, $academicPeriodId, $fte, $startDate, $endDate = '')
    {
        $this->autoRender= false;
        $StaffTable = TableRegistry::get('Institution.Staff');
        $positionTable = TableRegistry::get('Institution.InstitutionPositions');
        $userId = $this->Auth->user('id');

        $selectedFTE = empty($fte) ? 0 : $fte;
        $excludePositions = $StaffTable->find();

        $startDate = new Date($startDate);

        $excludePositions = $excludePositions
            ->select([
                'position_id' => $StaffTable->aliasField('institution_position_id'),
            ])
            ->where([
                $StaffTable->aliasField('institution_id') => $institutionId,
                $StaffTable->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->group($StaffTable->aliasField('institution_position_id'))
            ->having([
                'OR' => [
                    'SUM('.$StaffTable->aliasField('FTE') .') >= ' => 1,
                    'SUM('.$StaffTable->aliasField('FTE') .') > ' => (1-$selectedFTE),
                ]
            ])
            ->hydrate(false);

        $endDate = null;
        if (!empty($endDate)) {
            $endDate = new Date($endDate);
            $excludePositions = $excludePositions->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate]);
        } else {
            $orCondition = [
                $StaffTable->aliasField('end_date') . ' >= ' => $startDate,
                $StaffTable->aliasField('end_date') . ' IS NULL'
            ];
            $excludePositions = $excludePositions->where([
                    $StaffTable->aliasField('start_date').' <= ' => $startDate,
                    'OR' => $orCondition
                ]);
        }

        if ($this->AccessControl->isAdmin()) {
            $userId = null;
            $roles = [];
        } else {
            $roles = $StaffTable->Institutions->getInstitutionRoles($userId, $institutionId);
        }

        // Filter by active status
        $activeStatusId = $this->Workflow->getStepsByModelCode($positionTable->registryAlias(), 'ACTIVE');
        $positionConditions = [];
        $positionConditions[$StaffTable->Positions->aliasField('institution_id')] = $institutionId;
        $positionConditions[$StaffTable->Positions->aliasField('academic_period_id')] = $academicPeriodId;
        if (!empty($activeStatusId)) {
            $positionConditions[$StaffTable->Positions->aliasField('status_id').' IN '] = $activeStatusId;
        }

        if ($selectedFTE > 0) {
            $staffPositionsOptions = $StaffTable->Positions
                ->find()
                ->innerJoinWith('StaffPositionTitles.SecurityRoles')
                ->where($positionConditions)
                ->select(['security_role_id' => 'SecurityRoles.id', 'type' => 'StaffPositionTitles.type'])
                ->order(['StaffPositionTitles.type' => 'DESC', 'StaffPositionTitles.order'])
                ->autoFields(true)
                ->toArray();
        } else {
            $staffPositionsOptions = [];
        }

        // Filter by role previlege
        $SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
        $roleOptions = $SecurityRolesTable->getRolesOptions($userId, $roles);
        $roleOptions = array_keys($roleOptions);
        $staffPositionRoles = $this->array_column($staffPositionsOptions, 'security_role_id');
        $staffPositionsOptions = array_intersect_key($staffPositionsOptions, array_intersect($staffPositionRoles, $roleOptions));

        // Adding the opt group
        $types = $this->getSelectOptions('Staff.position_types');
        $options = [];
        $excludePositions = array_column($excludePositions->toArray(), 'position_id');
        foreach ($staffPositionsOptions as $position) {
            $type = __($types[$position->type]);
            $options[] = ['value' => $position->id, 'group' => $type, 'name' => $position->name, 'disabled' => in_array($position->id, $excludePositions)];
        }

        echo json_encode($options);
    }
}
