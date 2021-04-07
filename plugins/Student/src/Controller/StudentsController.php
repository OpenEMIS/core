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

        // special needs
        'SpecialNeedsReferrals',
        'SpecialNeedsAssessments',
        'SpecialNeedsServices',
        'SpecialNeedsDevices',
        'SpecialNeedsPlans'
    ];

    public function initialize()
    {
        parent::initialize();

        $this->ControllerAction->model('Institution.StudentUser');
        $this->ControllerAction->models = [
            'Accounts'          => ['className' => 'Student.Accounts', 'actions' => ['view', 'edit']],
            'Nationalities'     => ['className' => 'User.Nationalities'],
            'Absences'          => ['className' => 'Student.Absences', 'actions' => ['index', 'view','remove']],
            'Behaviours'        => ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']],
            'Extracurriculars'  => ['className' => 'Student.Extracurriculars',  'actions' => ['index', 'add', 'edit', 'remove','view']],
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
    public function Demographic()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Demographic']);
    }
    public function StudentTransport()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentTransport']);
    }
    public function Outcomes()
    {
        $comment = $this->request->query['comment'];
        if(!empty($comment) && $comment == 1){ 
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomeComments']);
        
        }else{
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomes']);
        }        
    }

    public function Meals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.InstitutionMealStudents']);
    }
	
	public function Profiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Profiles']);
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

    // Special Needs
    public function SpecialNeedsReferrals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsReferrals']);
    }
    public function SpecialNeedsAssessments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsAssessments']);
    }
    public function SpecialNeedsServices()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsServices']);
    }
    public function SpecialNeedsDevices()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsDevices']);
    }
    public function SpecialNeedsPlans()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsPlans']);
    }
    // Special Needs - End

    // Visits
    public function StudentVisitRequests()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentVisitRequests']);
    }
    public function StudentVisits()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentVisits']);
    }
    // Visits - END
    
    public function Competencies()
    {
        $session = $this->request->session();

        if ($session->check('Student.Students.id')) {
            $studentId = $session->read('Student.Students.id');
            $session->write('Student.Competencies.student_id', $studentId);

            // tabs
            $options = ['type' => 'student'];
            $tabElements = $this->getAcademicTabElements($options);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'Competencies');
            // End

            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentCompetencies']);
        }
        
    }
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
            case 'StudentScheduleTimetable':
                
                $this->Angular->addModules([
                    'studenttimetable.ctrl',
                    'studenttimetable.svc'
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

            //POCOR-5890 starts
            if($model->getHeader($alias) == 'Immunizations'){
                $alias = __('Vaccinations');     
            }
            //POCOR-5890 ends
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

    //Related getGuardianTabElements function in GuardiansController
    public function getGuardianTabElements($options = [])
    {
        if (array_key_exists('userRole', $options) && $options['userRole'] == 'Guardians' && array_key_exists('entity', $options)) {
            $session = $this->request->session();
            $session->write('Guardian.Guardians.name', $options['entity']->user->name);
            $session->write('Guardian.Guardians.id', $options['entity']->user->id);
        }

        $session = $this->request->session();
        $StudentGuardianId = $session->read('Student.Guardians.primaryKey')['id'];
        $guardianId = $session->read('Guardian.Guardians.id');
        if (!empty($guardianId)) {
            $id = $guardianId;
        }

        $tabElements = [
            'Guardians' => ['text' => __('Relation')],
            'GuardianUser' => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Demographic' => ['text' => __('Demographic')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' => ['text' => __('Nationalities')], //UserNationalities is following the filename(alias) to maintain "selectedAction" select tab accordingly.
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')]
        ];

        foreach ($tabElements as $key => $value) {
            if ($key == 'Guardians') {
                $tabElements[$key]['url'] = ['plugin' => 'Student',
                    'controller' => 'Students',
                    'action' => 'Guardians',
                    'view',
                    $this->paramsEncode(['id' => $StudentGuardianId])
                    ];
            } elseif ($key == 'GuardianUser') {
                $tabElements[$key]['url'] = ['plugin' => 'Student',
                    'controller' => 'Students',
                    'action' => 'GuardianUser',
                    'view',
                    $this->paramsEncode(['id' => $id, 'StudentGuardians.id' => $StudentGuardianId])
                    ];
            } elseif ($key == 'Accounts') {
                $tabElements[$key]['url']['plugin'] = 'Guardian';
                $tabElements[$key]['url']['controller'] = 'Guardians';
                $tabElements[$key]['url']['action'] = 'Accounts';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $id]);
            } else if ($key == 'Comments') {
                $url = [
                        'plugin' => 'Guardian',
                        'controller' => 'GuardianComments',
                        'action' => 'index'
                ];
                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString($url, ['security_user_id' => $id]);
            } elseif ($key == 'UserNationalities') {
                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString(
                    [
                        'plugin' => 'Guardian',
                        'controller' => 'Guardians',
                        'action' => 'Nationalities',
                        'index'
                    ],
                    ['security_user_id' => $id]
                );
            } else {
                $actionURL = $key;
                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString(
                    [
                        'plugin' => 'Guardian',
                        'controller' => 'Guardians',
                        'action' => $actionURL,
                        'index'
                    ],
                    ['security_user_id' => $id]
                );
            }
        };

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

    public function getCompetencyTabElements($options = [])
    {
        $queryString = $this->request->query('queryString');
        $tabElements = [
            'Competencies' => [
                'url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'StudentCompetencies', 'view', 'queryString' => $queryString],
                'text' => __('Items')
            ]
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }
    
    public function StudentScheduleTimetable()
    {
        $session = $this->request->session();

        if ($session->check('Student.Students.id')) {
            $userId = $session->read('Student.Students.id');
            
        }else{
            $userId = $this->Auth->user('id');
        }
        
        $InstitutionStudents =
            TableRegistry::get('Institution.InstitutionStudents')
            ->find()
            ->where([
                'InstitutionStudents.student_id' => $userId
            ])
            ->hydrate(false)
            ->first();
        
        $institutionId = $InstitutionStudents['institution_id'];
        $academicPeriodId = TableRegistry::get('AcademicPeriod.AcademicPeriods')
                ->getCurrent();
        
        $InstitutionClassStudentsResult = 
                TableRegistry::get('Institution.InstitutionClassStudents')
                    ->find()
                    ->where([
                        'academic_period_id'=>$academicPeriodId,
                        'student_id' => $userId,
                        'institution_id' => $institutionId
                    ])
                    ->hydrate(false)
                    ->first();
        
        $institutionClassId = $InstitutionClassStudentsResult['institution_class_id'];
        $ScheduleTimetables = TableRegistry::get('Schedule.ScheduleTimetables')
                ->find()
                ->where([
                        'academic_period_id'=>$academicPeriodId,
                        'institution_class_id' => $institutionClassId,
                        'institution_id' => $institutionId,
                        'status' => 2
                    ])
                ->hydrate(false)
                ->first();
        
        $this->set('userId', $userId);
        $timetable_id = (isset($ScheduleTimetables['id']))?$ScheduleTimetables['id']:0;
        $this->set('timetable_id', $timetable_id);  
        $this->set('academicPeriodId', $academicPeriodId);
        $this->set('institutionDefaultId', $institutionId);
        $this->set('ngController', 'StudentTimetableCtrl as $ctrl');

    }
}
