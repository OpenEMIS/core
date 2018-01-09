<?php
namespace Student\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class StudentsController extends AppController
{
    private $features = [
        // General
        'Identities',
        'UserNationalities',
        'Contacts',
        'Guardians',
        'GuardianUser',
        'UserLanguages',
        'SpecialNeeds',
        'Attachments',
        'Comments',
        // 'UserActivities',
        // 'StudentSurveys',

        // academic
        // 'StudentClasses',
        // 'StudentSubjects',
        // 'Absences',
        // 'StudentBehaviours',
        'Awards',
        'Extracurriculars',

        // finance
        'BankAccounts',
        // 'StudentFees',

        // health
        'Healths',
        'Allergies',
        'Consultations',
        'Families',
        'Histories',
        'Immunizations',
        'Medications',
        'Tests',
    ];

    public function initialize()
    {
        parent::initialize();

        $this->ControllerAction->model('Institution.StudentUser');
        $this->ControllerAction->models = [
            'Accounts'          => ['className' => 'Student.Accounts', 'actions' => ['view', 'edit']],
            'Nationalities'     => ['className' => 'User.Nationalities'],
            'Absences'          => ['className' => 'Student.Absences', 'actions' => ['index', 'view']],
            'Behaviours'        => ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']],
            'Extracurriculars'  => ['className' => 'Student.Extracurriculars'],
            'History'           => ['className' => 'User.UserActivities', 'actions' => ['index']],
            'ImportStudents'    => ['className' => 'Student.ImportStudents', 'actions' => ['index', 'add']],
        ];

        $this->loadComponent('User.Image');
        $this->loadComponent('Institution.InstitutionAccessControl');
        $this->attachAngularModules();

        $this->set('contentHeader', 'Students');
    }

    // CAv4
    public function Employments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserEmployments']);
    }
    public function StudentFees()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentFees']);
    }
    public function Classes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentClasses']);
    }
    public function Subjects()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentSubjects']);
    }
    public function Nationalities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserNationalities']);
    }
    public function Languages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserLanguages']);
    }
    public function SpecialNeeds()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.SpecialNeeds']);
    }
    public function Contacts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Contacts']);
    }
    public function BankAccounts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.BankAccounts']);
    }
    public function Identities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Identities']);
    }
    public function Awards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Awards']);
    }
    public function Guardians()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Guardians']);
    }
    public function GuardianUser()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.GuardianUser']);
    }
    public function Attachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Attachments']);
    }
    public function ReportCards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentReportCards']);
    }
    public function StudentSurveys()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentSurveys']);
    }
    public function Outcomes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomes']);
    }

    // health
    public function Healths()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Healths']);
    }
    public function HealthAllergies()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Allergies']);
    }
    public function HealthConsultations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Consultations']);
    }
    public function HealthFamilies()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Families']);
    }
    public function HealthHistories()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Histories']);
    }
    public function HealthImmunizations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Immunizations']);
    }
    public function HealthMedications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Medications']);
    }
    public function HealthTests()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Tests']);
    }
    // End Health
    // End

    // AngularJS
    public function Results()
    {
        $session = $this->request->session();

        if ($session->check('Student.Students.id')) {
            $studentId = $session->read('Student.Students.id');
            $session->write('Student.Results.student_id', $studentId);

            // tabs
            $options = ['type' => 'student'];
            $tabElements = $this->getAcademicTabElements($options);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'Results');
            // End

            $this->set('ngController', 'StudentResultsCtrl as StudentResultsController');
        }
    }

    public function ExaminationResults()
    {
        $session = $this->request->session();

        if ($session->check('Student.Students.id')) {
            $studentId = $session->read('Student.Students.id');
            $session->write('Student.ExaminationResults.student_id', $studentId);

            // tabs
            $options = ['type' => 'student'];
            $tabElements = $this->getAcademicTabElements($options);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'ExaminationResults');
            // End

            $this->set('ngController', 'StudentExaminationResultsCtrl as StudentExaminationResultsController');
        }
    }
    // End

    private function attachAngularModules()
    {
        $action = $this->request->action;

        switch ($action) {
            case 'Results':
                $this->Angular->addModules([
                    'alert.svc',
                    'student.results.ctrl',
                    'student.results.svc'
                ]);
                break;

            case 'ExaminationResults':
                $this->Angular->addModules([
                    'alert.svc',
                    'student.examination_results.ctrl',
                    'student.examination_results.svc'
                ]);
                break;
        }
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $session = $this->request->session();
        $action = $this->request->params['action'];
        $institutionName = $session->read('Institution.Institutions.name');
        $institutionId = $session->read('Institution.Institutions.id');
        $this->Navigation->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', $this->ControllerAction->paramsEncode(['id' => $institutionId])]);
        $this->Navigation->addCrumb('Students', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students']);
        $header = __('Students');

        if ($action == 'index') {
        } else if ($session->check('Student.Students.id') || $action == 'view' || $action == 'edit' || $action == 'Results') {
            // add the student name to the header
            $id = 0;
            if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
                $id = $this->request->pass[0];
            } else if ($session->check('Student.Students.id')) {
                $id = $session->read('Student.Students.id');
            }

            if ($this->StudentUser->exists([$this->StudentUser->primaryKey() => $id])) {
                $entity = $this->StudentUser->get($id);
                $name = $entity->name;
                $header = $action == 'Results' ? $name . ' - ' . __('Assessments') : $name . ' - ' . __('Overview');
                $this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $id])]);
            } else {
                $indexPage = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index'];
                return $this->redirect($indexPage);
            }
        }

        $this->set('contentHeader', $header);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        /**
         * if student object is null, it means that students.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
         */

        $session = $this->request->session();
        if ($session->check('Student.Students.id')) {
            $header = '';
            $userId = $session->read('Student.Students.id');

            if (!$this->AccessControl->isAdmin()) {
                $institutionIds = $session->read('AccessControl.Institutions.ids');
                $studentId = $session->read('Student.Students.id');
                $enrolledStatus = false;
                $InstitutionStudentsTable = TableRegistry::get('Institution.Students');
                foreach ($institutionIds as $id) {
                    $enrolledStatus = $InstitutionStudentsTable->checkEnrolledInInstitution($studentId, $id);
                    if ($enrolledStatus) {
                        break;
                    }
                }
                if (! $enrolledStatus) {
                    if ($model->alias() != 'BankAccounts' && $model->alias() != 'StudentFees') {
                        $this->ControllerAction->removeDefaultActions(['add', 'edit', 'remove']);
                    }
                }
            }

            // POCOR-3983 to disable add/edit/remove action on the model when institution status is inactive
            $this->getStatusPermission($model);

            if ($session->check('Student.Students.name')) {
                $header = $session->read('Student.Students.name');
            }

            $idKey = $this->ControllerAction->getPrimaryKey($model);
            $primaryKey = $model->primaryKey();

            $alias = $model->alias;
            $this->Navigation->addCrumb($model->getHeader($alias));
            $header = $header . ' - ' . $model->getHeader($alias);

            // $params = $this->request->params;
            $this->set('contentHeader', $header);

            if ($model->hasField('security_user_id')) {
                $model->fields['security_user_id']['type'] = 'hidden';
                $model->fields['security_user_id']['value'] = $userId;

                if (count($this->request->pass) > 1) {
                    $modelId = $this->request->pass[1]; // id of the sub model

                    $ids = $this->ControllerAction->paramsDecode($modelId);
                    $idKey = $this->ControllerAction->getIdKeys($model, $ids);
                    $idKey[$model->aliasField('security_user_id')] = $userId;

                    /**
                     * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                     */
                    if (!$model->exists($idKey)) {
                        $this->Alert->warning('general.notExists');
                        return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => $alias]);
                    }
                }
            } else if ($model->hasField('student_id')) {
                $model->fields['student_id']['type'] = 'hidden';
                $model->fields['student_id']['value'] = $userId;

                if (count($this->request->pass) > 1) {
                    $modelId = $this->request->pass[1]; // id of the sub model

                    $ids = $this->ControllerAction->paramsDecode($modelId);
                    $idKey = $this->ControllerAction->getIdKeys($model, $ids);
                    $idKey[$model->aliasField('student_id')] = $userId;

                    /**
                     * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                     */
                    if (!$model->exists($idKey)) {
                        $this->Alert->warning('general.notExists');
                        return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => $alias]);
                    }
                }
            }
        } else {
            if ($model->alias() == 'ImportStudents') {
                $this->Navigation->addCrumb($model->getHeader($model->alias()));
                $header = __('Students') . ' - ' . $model->getHeader($model->alias());
                $this->set('contentHeader', $header);
            } else {
                $this->Alert->warning('general.notExists');
                $event->stopPropagation();
                return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']);
            }
        }
    }

    public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options)
    {
        $session = $this->request->session();

        if ($model->alias() != 'Students') {
            if ($session->check('Student.Students.id')) {
                if ($model->hasField('security_user_id')) {
                    $userId = $session->read('Student.Students.id');
                    $query->where([$model->aliasField('security_user_id') => $userId]);
                } else if ($model->hasField('student_id')) {
                    $userId = $session->read('Student.Students.id');
                    $query->where([$model->aliasField('student_id') => $userId]);
                }
            } else {
                $this->Alert->warning('general.noData');
                $event->stopPropagation();
                return $this->redirect(['action' => 'index']);
            }
        }
    }

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    public function excel($id = 0)
    {
        $this->Students->excel($id);
        $this->autoRender = false;
    }

    // public function getUserTabElements($options = []) {
    // 	$plugin = $this->plugin;
    // 	$name = $this->name;

    // 	$id = (array_key_exists('id', $options))? $options['id']: $this->request->session()->read($name.'.id');

    // 	$tabElements = [
    // 		$this->name => [
    // 			'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'view', $id],
    // 			'text' => __('Details')
    // 		],
    // 		'Accounts' => [
    // 			'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $id],
    // 			'text' => __('Account')
    // 		]
    // 	];

    // 	return $tabElements;
    // }

    public function getUserTabElements($options = [])
    {
        $session = $this->request->session();
        $tabElements = $session->read('Institution.Students.tabElements');

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getAcademicTabElements($options = [])
    {
        $tabElements = TableRegistry::get('Institution.StudentUser')->getAcademicTabElements($options);
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getProfessionalTabElements($options = [])
    {
        $tabElements = [];
        $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
        $professionalTabElements = [
            'Employments' => ['text' => __('Employments')],
        ];

        $tabElements = array_merge($tabElements, $professionalTabElements);

        foreach ($tabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>$key, 'index']);
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getFinanceTabElements($options = [])
    {
        $tabElements = [];
        $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
        $studentTabElements = [
            'BankAccounts' => ['text' => __('Bank Accounts')],
            'StudentFees' => ['text' => __('Fees')],
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>$key, 'index']);
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getImage($id)
    {
        $this->autoRender = false;
        $this->ControllerAction->autoRender = false;
        $this->Image->getUserImage($id);
    }

    public function getStudentGuardianTabElements($options = [])
    {
        $type = (array_key_exists('type', $options))? $options['type']: null;
        $plugin = $this->plugin;
        $name = $this->name;
        $tabElements = [
            'Guardians' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Guardians', 'type' => $type],
                'text' => __('Guardians')
            ],
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getStatusPermission($model)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');

        $Institutions = TableRegistry::get('Institution.Institutions');
        $isActive = $Institutions->isActive($institutionId);

        // institution status is INACTIVE
        if (!$isActive) {
            if (in_array($model->alias(), $this->features)) { // check the feature list
                if ($model instanceof \App\Model\Table\ControllerActionTable) {
                    // CAv4 off the add/edit/remove action
                    $model->toggle('add', false);
                    $model->toggle('edit', false);
                    $model->toggle('remove', false);
                } else if ($model instanceof \App\Model\Table\AppTable) {
                    // CAv3 hide button and redirect when user change the Url
                    $model->addBehavior('ControllerAction.HideButton');
                }
            }
        }
    }
}
