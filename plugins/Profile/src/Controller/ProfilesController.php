<?php
namespace Profile\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use App\Controller\AppController;

class ProfilesController extends AppController
{
    public $activeObj = null;
    const APPROVED = 1;

    private $redirectedViewFeature = [
        // student academic
        'Programmes',
        'StudentClasses',
        'StudentSubjects',
        'StudentReportCards',

        // staff career
        'Positions',
        'StaffClasses',
        'StaffSubjects',
        'StaffBehaviours',
        'Licenses',
        'StaffAttendances',
    ];

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Configuration.ConfigItems');

        // get the configuration for change_password
        $changePasswordAllowed = $this->ConfigItems->value('change_password');
        // check if the current logged in user is a super admin
        $isSuperAdmin = $this->Auth->user('super_admin');

        // if user is super admin and change_password is not allowed, then remove the edit action from account page
        $accountPermissions = (!$changePasswordAllowed && $isSuperAdmin) ? ['view'] : ['view', 'edit'];

        $this->ControllerAction->models = [
            // Users
            'Accounts'              => ['className' => 'Profile.Accounts', 'actions' => $accountPermissions],
            'History'               => ['className' => 'User.UserActivities', 'actions' => ['index']],

            // Student
            'StudentAbsences'       => ['className' => 'Student.Absences', 'actions' => ['index', 'view']],
            'StudentBehaviours'     => ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']],
            'StudentExtracurriculars' => ['className' => 'Student.Extracurriculars'],

            // Staff
            'StaffPositions'        => ['className' => 'Staff.Positions', 'actions' => ['index', 'view']],
            'StaffSections'         => ['className' => 'Staff.StaffSections', 'actions' => ['index', 'view']],
            'StaffClasses'          => ['className' => 'Staff.StaffClasses', 'actions' => ['index', 'view']],
            'StaffQualifications'   => ['className' => 'Staff.Qualifications'],
            'StaffExtracurriculars' => ['className' => 'Staff.Extracurriculars'],
            'TrainingResults'       => ['className' => 'Staff.TrainingResults', 'actions' => ['index', 'view']],

            'ImportUsers'           => ['className' => 'Directory.ImportUsers', 'actions' => ['add']],
            'ImportSalaries'        => ['className' => 'Staff.ImportSalaries', 'actions' => ['add']]
        ];

        $this->loadComponent('Training.Training');
        $this->loadComponent('User.Image');
        $this->loadComponent('Scholarship.ScholarshipTabs');
        $this->attachAngularModules();

        $this->set('contentHeader', 'Profiles');
    }

    public function Profiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Profile.Profiles']); }

    // CAv4
    public function StudentFees()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentFees']); }
    public function StaffEmployments() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Employments']); }
    public function StaffQualifications()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Qualifications']); }
    public function StaffPositions()          { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Positions']); }
    public function StaffClasses()            { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffClasses']); }
    public function StaffSubjects()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffSubjects']); }
    public function StaffEmploymentStatuses() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.EmploymentStatuses']); }
    public function StaffLeave()              { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Leave']); }
    public function StudentClasses()          { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentClasses']); }
    public function StudentSubjects()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentSubjects']); }
    public function Nationalities()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserNationalities']); }
    public function Languages()               { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserLanguages']); }
    public function StaffMemberships()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Memberships']); }
    public function StaffLicenses()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Licenses']); }
    public function Contacts()                { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Contacts']); }
    public function StudentBankAccounts()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.BankAccounts']); }
    public function StaffBankAccounts()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.BankAccounts']); }
    public function StudentProgrammes()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Programmes']); }
    public function Identities()              { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Identities']); }
    public function StudentAwards()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Awards']); }
    public function StaffAwards()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Awards']); }
    public function TrainingNeeds()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.TrainingNeeds']); }
    public function StaffAppraisals()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Appraisals']); }
    public function StaffDuties()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Duties']); }
    public function StaffAssociations()            { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.InstitutionAssociationStaff']);}
    public function StudentTextbooks()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Textbooks']); }
    public function StudentAssociations()    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.InstitutionAssociationStudent']);}
    public function ProfileGuardians()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Profile.Guardians']); }
    public function ProfileGuardianUser()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Profile.GuardianUser']); }
    public function StudentReportCards()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentReportCards']); }
    public function Attachments()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Attachments']); }
    public function Courses()                 { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffTrainings']); }
    public function StaffSalaries()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Salaries']); }
    public function StaffPayslips()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Payslips']); }
    public function StaffBehaviours()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffBehaviours']); }
    public function StudentOutcomes()         { 
        $comment = $this->request->query['comment'];
        if(!empty($comment) && $comment == 1){ 
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomeComments']);

        }else{
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomes']);
        }         
        
    }
    public function StudentCompetencies()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentCompetencies']); }
    public function StudentRisks() {  $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentRisks']);}
    public function ScholarshipApplications() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Profile.ScholarshipApplications']); }
    public function Demographic()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Demographic']); }
    public function ProfileStudents()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Profile.Students']); }
    public function ProfileStudentUser()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Profile.StudentUser']); }

    // health
    public function Healths()               { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Healths']); }
    public function HealthAllergies()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Allergies']); }
    public function HealthConsultations()   { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Consultations']); }
    public function HealthFamilies()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Families']); }
    public function HealthHistories()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Histories']); }
    public function HealthImmunizations()   { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Immunizations']); }
    public function HealthMedications()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Medications']); }
    public function HealthTests()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Tests']); }
    // End Health

    // Special Needs
    public function SpecialNeedsReferrals()   { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsReferrals']); }
    public function SpecialNeedsAssessments() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsAssessments']); }
    public function SpecialNeedsServices()    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsServices']); }
    public function SpecialNeedsDevices()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsDevices']); }
    public function SpecialNeedsPlans()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsPlans']); }
    // Special Needs - End

    public function Employments()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserEmployments']); }

    public function HistoricalStaffLeave()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Historical.HistoricalStaffLeave']);
    }
    // AngularJS
    public function StaffAttendances()
    {
        $institutionId = null;
        $staffId = $this->Auth->user('id');
        $tabElements = $this->getCareerTabElements();

        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
        $this->Navigation->addCrumb($crumbTitle);
        $this->set('institution_id', $institutionId);
        $this->set('staff_id', $staffId);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', 'Attendances');
        $this->set('ngController', 'StaffAttendancesCtrl as $ctrl');
    }
    // End

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        return true;
    }

    // AngularJS
    public function StudentResults()
    {
        $session = $this->request->session();
        //$studentId = $this->Auth->user('id');
        $sId = $this->request->pass[1];

        // tabs
        $options['type'] = 'student';
        $tabElements = $this->getAcademicTabElements($options);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', 'Results');
        // End

        $this->set('ngController', 'StudentResultsCtrl as StudentResultsController');
    }

    public function StudentExaminationResults()
    {
        $session = $this->request->session();
        /*$studentId = $this->Auth->user('id');*/
        $studentId  = $this->request->pass[1];

        $session->write('Student.ExaminationResults.student_id', $studentId);

        // tabs
        $options['type'] = 'student';
        $tabElements = $this->getAcademicTabElements($options);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', 'ExaminationResults');
        // End

        $this->set('ngController', 'StudentExaminationResultsCtrl as StudentExaminationResultsController');
    }
    // End

    private function attachAngularModules() {
        $action = $this->request->action;

        switch ($action) {
            case 'StudentResults':
            $this->Angular->addModules([
                'alert.svc',
                'student.results.ctrl',
                'student.results.svc'
            ]);
            break;
            case 'StudentExaminationResults':
            $this->Angular->addModules([
                'alert.svc',
                'student.examination_results.ctrl',
                'student.examination_results.svc'
            ]);
            break;
            case 'StaffAttendances':
            $this->Angular->addModules([
                'staff.attendances.ctrl',
                'staff.attendances.svc'
            ]);
            break;
            case 'ScheduleTimetable':
            $this->Angular->addModules([
                'timetable.ctrl',
                'timetable.svc'
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

        $session = $this->request->session();
        $action = $this->request->params['action'];

        $loginUserId = $this->Auth->user('id'); // login user

        $this->Navigation->addCrumb('Profile', ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Profiles', 'view', $this->ControllerAction->paramsEncode(['id' => $loginUserId])]);
        
        $header = '';
        if ($this->Profiles->exists([$this->Profiles->primaryKey() => $loginUserId])) {
            $studentId = $this->request->pass[1];
            if (!empty($studentId)) {
                $sId = $this->ControllerAction->paramsDecode($studentId);
                $student_id = $sId['id'];
                
                if ($action == 'StudentReportCards') {
                    $student_id = $sId['student_id'];
                }
                if ($action == 'StudentRisks') {
                    $student_id = $loginUserId;
                }
                $entity = $this->Profiles->get($student_id);
                $name = $entity->name;
            } else {
                $entity = $this->Profiles->get($loginUserId);
                $name = $entity->name;
            }
            $header = $action == 'StudentResults' ? $name . ' - ' . __('Assessments') : $name . ' - ' . __('Overview');
            $this->Navigation->addCrumb($name);

            $this->activeObj = $entity;
        } else {
            //no record
        }

        $this->set('contentHeader', $header);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $session = $this->request->session();

        if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4
            // off the import action
            if ($model->behaviors()->has('ImportLink')) {
                $model->removeBehavior('ImportLink');
            }

            $alias = $model->alias();
            $excludedModel = ['ScholarshipApplications', 'Leave', 'StudentReportCards'];

            if (!in_array($alias, $excludedModel)) {
                $model->toggle('add', false);
                $model->toggle('edit', false);
                $model->toggle('remove', false);

                // redirected view feature is to cater for the link that redirected to institution
                if (in_array($alias, $this->redirectedViewFeature)) {
                    $model->toggle('view', false);
                }
            }
        } else if ($model instanceof \App\Model\Table\AppTable) { // CAv3
            $alias = $model->alias();
            $excludedModel = ['Accounts'];

            if (!in_array($alias, $excludedModel)) {
                $model->addBehavior('ControllerAction.HideButton');
            }
        } else if ($model instanceof \Staff\Model\Table\StaffClassesTable || $model instanceof \Staff\Model\Table\StaffSubjectsTable) {
            $model->toggle('add', false);
        } else if ($model->alias() == 'Guardians') {
            $model->editButtonAction('ProfileGuardianUser');
        }

        $header = '';
        $userId = $this->Auth->user('id'); // login user
        $header = $session->read('Auth.User.name');

        $alias = $model->alias;
        //POCOR-5890 starts
        if($alias == 'HealthImmunizations'){
           $alias = __('Vaccinations');     
        }
        //POCOR-5890 ends
        $this->Navigation->addCrumb($model->getHeader($alias));
        //POCOR-5675
        $action = $this->request->params['action'];
        $id = $session->read('Student.Students.id');
        if (!empty($id)) {
            if ($action == 'ProfileStudentUser' || $action == 'StudentProgrammes' || $action == 'StudentClasses' || $action == 'StudentSubjects' || $action == 'StudentAbsences' || $action == 'ComponentAction' || $action == 'StudentOutcomes'|| $action == 'StudentCompetencies' || $action == 'StudentExaminationResults'|| $action == 'StudentReportCards' || $action == 'StudentExtracurriculars' || $action == 'StudentTextbooks' || $action == 'StudentRisks' || $action == 'StudentAwards') {
                $studentId = $this->ControllerAction->paramsDecode($id)['id'];
                $entity = $this->Profiles->get($studentId);
                $name = $entity->name;
                $header = $name;
                $header = $header . ' - ' . $model->getHeader($alias);
            }
        } else {
            if ($alias == 'StudentAssociations') {
                $header = $header . ' - ' . 'Associations';
            } else {
                 $header = $header . ' - ' . $model->getHeader($alias);
            }        
     }
       //POCOR-5675
     $this->set('contentHeader', $header);

     if ($model->hasField('security_user_id')) { 
        $model->fields['security_user_id']['type'] = 'hidden';
        $model->fields['security_user_id']['value'] = $userId;

        if (count($this->request->pass) > 1) {
                $modelId = $this->request->pass[1]; // id of the sub model
                $ids = $this->ControllerAction->paramsDecode($modelId);
                $idKey = $this->ControllerAction->getIdKeys($model, $ids);
                $idKey[$model->aliasField('security_user_id')] = $userId;
                $exists = $model->exists($idKey);

                /**
                 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                 */
                if (!$exists) {
                    $this->Alert->warning('general.notExists');
                    return $this->redirect(['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => $alias]);
                }
            }
        } else if ($model->hasField('staff_id')) {
            $model->fields['staff_id']['type'] = 'hidden';
            $model->fields['staff_id']['value'] = $userId;

            if (count($this->request->pass) > 1) {
                $modelId = $this->request->pass[1]; // id of the sub model

                $ids = $this->ControllerAction->paramsDecode($modelId);
                $idKey = $this->ControllerAction->getIdKeys($model, $ids);
                $idKey[$model->aliasField('staff_id')] = $userId;
                $exists = $model->exists($idKey);

                /**
                 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                 */
                if (!$exists) {
                    $this->Alert->warning('general.notExists');
                    return $this->redirect(['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => $alias]);
                }
            }
        } else if ($model->hasField('student_id')) {
            $model->fields['student_id']['type'] = 'hidden';
            $model->fields['student_id']['value'] = $userId;

            //if (count($this->request->pass) > 1) {
                //$modelId = $this->request->pass[1]; // id of the sub model

                //$ids = $this->ControllerAction->paramsDecode($modelId);
                //$idKey = $this->ControllerAction->getIdKeys($model, $ids);
                //$idKey[$model->aliasField('student_id')] = $userId;
                //$exists = $model->exists($idKey);

               //if (in_array($model->alias(), ['Students'])) {
                    //$params[$model->aliasField('guardian_id')] = $userId;
                    //$exists = $model->exists($params);
                //}
                /**
                 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                 */
                //if (!$exists) {
                    //$this->Alert->warning('general.notExists');
                    //return $this->redirect(['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => $alias]);
                //}
            //}
            }
        }

        public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options)
        {
        $loginUserId = $this->Auth->user('id'); // login user
        $action = $this->request->params['action'];
        if ($model->hasField('security_user_id')) {
            $session = $this->request->session();
            $studentId = $session->read('Student.Students.id'); 
            if (!empty($studentId)) {
                $sId = $this->ControllerAction->paramsDecode($studentId)['id'];
                $query->where([$model->aliasField('security_user_id') => $sId]);
            } else {
                $query->where([$model->aliasField('security_user_id') => $loginUserId]);
            }
        } else if ($model->hasField('student_id')) {
            if ($action == 'ProfileStudentUser' || $action == 'StudentProgrammes' || $action == 'StudentTextbooks') {
                $session = $this->request->session();
                $studentId = $session->read('Student.Students.id'); 
                if (!empty($studentId)) {
                    $sId = $this->ControllerAction->paramsDecode($studentId)['id'];
                    $query->where([$model->aliasField('student_id') => $sId]);
                } else {
                    $query->where([$model->aliasField('student_id') => $loginUserId]);
                }
            }
        } else if ($model->hasField('staff_id') && $model->alias!='StudentCompetencies') {
            $query->where([$model->aliasField('staff_id') => $loginUserId]);
        } else if ($model->hasField('applicant_id')) {
            $query->where([$model->aliasField('applicant_id') => $loginUserId]);
        }
    }

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra) {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    public function getImage($id) {
        $this->autoRender = false;
        $this->ControllerAction->autoRender = false;
        $this->Image->getUserImage($id);
    }

    public function getUserTabElements($options = [])
    {
        if (array_key_exists('queryString', $this->request->query)) { //to filter if the URL already contain querystring
            $id = $this->ControllerAction->getQueryString('security_user_id');
        }

        $plugin = $this->plugin;
        $name = $this->name;

        $id = (array_key_exists('id', $options))? $options['id']: $this->Auth->user('id');

        $tabElements = [
            $this->name => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Demographic' => ['text' => __('Demographic')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' => ['text' => __('Nationalities')], //UserNationalities is following the filename(alias) to maintain "selectedAction" select tab accordingly.
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')],
            'History' => ['text' => __('History')]
        ];

        foreach ($tabElements as $key => $value) {
            if ($key == $this->name) {
                $tabElements[$key]['url']['action'] = 'Profiles';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $id]);
            } else if ($key == 'Comments') {
                $url = [
                    'plugin' => $plugin,
                    'controller' => 'ProfileComments',
                    'action' => 'index'
                ];
                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString($url, ['security_user_id' => $id]);
            } else if ($key == 'Accounts') {
                $tabElements[$key]['url']['action'] = 'Accounts';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $id]);
            } else {
                $actionURL = $key;
                if ($key == 'UserNationalities') {
                    $actionURL = 'Nationalities';
                }
                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString([
                    'plugin' => $plugin,
                    'controller' => $name,
                    'action' => $actionURL,
                    'index'],
                    ['security_user_id' => $id]
                );
            }
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getAcademicTabElements($options = [])
    {  
        $session = $this->request->session();
        $studentId = $session->read('Student.Students.id');
        $id = (array_key_exists('id', $options))? $options['id'] : 0;
        $type = (array_key_exists('type', $options))? $options['type']: null;
        $tabElements = [];
        $studentUrl = ['plugin' => 'Profile', 'controller' => 'Profiles'];
        $plugin = $this->plugin;
        $studentTabElements = [
            'Programmes' => ['text' => __('Programmes')],
            'Classes' => ['text' => __('Classes')],
            'Subjects' => ['text' => __('Subjects')],
            'Absences' => ['text' => __('Absences')],
            'Behaviours' => ['text' => __('Behaviours')],
            'Outcomes' => ['text' => __('Outcomes')],
            'Competencies' => ['text' => __('Competencies')],
            'Results' => ['text' => __('Assessments')],
            'ExaminationResults' => ['text' => __('Examinations')],
            'ReportCards' => ['text' => __('Report Cards')],
            'Awards' => ['text' => __('Awards')],
            'Extracurriculars' => ['text' => __('Extracurriculars')],
            'Textbooks' => ['text' => __('Textbooks')],
            'Risks' => ['text' => __('Risks')],
            'Associations' => ['text' => __('Associations')]
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', $studentId, 'type' => $type]);
        }
        
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getFinanceTabElements($options = [])
    {
        $type = (array_key_exists('type', $options))? $options['type']: null;
        $plugin = $this->plugin;
        $name = $this->name;
        $tabElements = [];
        $studentUrl = ['plugin' => 'Profile', 'controller' => 'Profiles'];
        $studentTabElements = [
            'BankAccounts' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentBankAccounts', 'type' => $type],
                'text' => __('Bank Accounts')
            ],
            'StudentFees' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentFees', 'type' => $type],
                'text' => __('Fees')
            ],
        ];

        foreach ($studentTabElements as $key => $tab) {
            $studentTabElements[$key]['url'] = array_merge($studentTabElements[$key]['url'], ['type' => $type]);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    // For staff
    public function getCareerTabElements($options = [])
    {
        $type = (array_key_exists('type', $options))? $options['type']: null;
        $tabElements = [];
        $staffUrl = ['plugin' => 'Profile', 'controller' => 'Profiles'];
        $studentTabElements = [
            'EmploymentStatuses' => ['text' => __('Statuses')],
            'Positions' => ['text' => __('Positions')],
            'Classes' => ['text' => __('Classes')],
            'Subjects' => ['text' => __('Subjects')],
            'Leave' => ['text' => __('Leave')],
            'Attendances' => ['text' => __('Attendances')],
            'Behaviours' => ['text' => __('Behaviours')],
            'Appraisals' => ['text' => __('Appraisals')],
            'Duties' => ['text' => __('Duties')],
            'Associations' => ['text' => __('Associations')]
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($staffUrl, ['action' => 'Staff'.$key, 'type' => 'staff']);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getProfessionalTabElements($options = [])
    {
        $session = $this->request->session();
        $isStudent = $session->read('Auth.User.is_student');
        $isStaff = $session->read('Auth.User.is_staff');

        $tabElements = [];
        $profileUrl = ['plugin' => 'Profile', 'controller' => 'Profiles'];

        if ($isStaff) {
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
                'Qualifications' => ['text' => __('Qualifications')],
                'Extracurriculars' => ['text' => __('Extracurriculars')],
                'Memberships' => ['text' => __('Memberships')],
                'Licenses' => ['text' => __('Licenses')],
                'Awards' => ['text' => __('Awards')],
            ];
        } else if ($isStudent) {
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
            ];
        } else {
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
            ];
        }
        $tabElements = array_merge($tabElements, $professionalTabElements);

        foreach ($professionalTabElements as $key => $tab) {
            if ($key != 'Employments') {
                $tabElements[$key]['url'] = array_merge($profileUrl, ['action' => 'Staff'.$key, 'index']);
            } else {
                $tabElements[$key]['url'] = array_merge($profileUrl, ['action' => $key, 'index']);
            }
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getStaffFinanceTabElements($options = [])
    {
        $type = (array_key_exists('type', $options))? $options['type']: null;
        $tabElements = [];
        $staffUrl = ['plugin' => 'Profile', 'controller' => 'Profiles'];
        $staffTabElements = [
            'BankAccounts' => ['text' => __('Bank Accounts')],
            'Salaries' => ['text' => __('Salaries')],
            'Payslips' => ['text' => __('Payslips')],
        ];

        $tabElements = array_merge($tabElements, $staffTabElements);

        foreach ($staffTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($staffUrl, ['action' => 'Staff'.$key, 'type' => $type]);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getTrainingTabElements($options = [])
    {
        $tabElements = [];
        $staffUrl = ['plugin' => 'Profile', 'controller' => 'Profiles'];
        $studentTabElements = [
            'TrainingNeeds' => ['text' => __('Training Needs')],
            'TrainingResults' => ['text' => __('Training Results')],
            'Courses' => ['text' => __('Courses')],
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($staffUrl, ['action' => $key, 'index']);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
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
    
    public function ScheduleTimetable()
    {
        $userId = $this->Auth->user('id');

        $InstitutionStaff = TableRegistry::get('Institution.InstitutionStaff');
        $Institutions = TableRegistry::get('Institution.Institutions');
        
        
        $InstitutionStaff = $InstitutionStaff
        ->find()
        ->where([
            'InstitutionStaff.staff_id' => $userId,
            'InstitutionStaff.staff_status_id' => self::APPROVED
        ])
        ->hydrate(false)
        ->first();
        
        $institutionId = $InstitutionStaff['institution_id'];
        
        $selectedInstitutionOptions = $Institutions
        ->find('list', [
            'keyField' => 'id',
            'valueField' => 'name'
        ])
        ->select([
            'id' => $Institutions->aliasField('id'),
            'name' => $Institutions->aliasField('name'),
        ])
        ->where([
            $Institutions->aliasField('id') => $institutionId,
        ])
        ->hydrate(false)
        ->toArray();       
        
        $academicPeriodId = TableRegistry::get('AcademicPeriod.AcademicPeriods')
        ->getCurrent();
        $academicPeriodOptions =TableRegistry::get('AcademicPeriod.AcademicPeriods')
        ->getYearList();
        
        $shiftOptions = TableRegistry::get('Schedule.ScheduleIntervals')
        ->getStaffShiftOptions($academicPeriodId, false, $institutionId);
        $intervals = TableRegistry::get('Schedule.ScheduleIntervals');
        $scheduleIntervals = $intervals->find('list')
        ->where([
            $intervals->aliasField('academic_period_id') => $academicPeriodId,
            $intervals->aliasField('institution_id') => $institutionId
        ])
        ->toArray();
        
        $this->set('userId', $userId);
        $this->set('selectedInstitutionOptions', $selectedInstitutionOptions); 
        $this->set('scheduleIntervals', $scheduleIntervals) ;  
        $scheduleIntervalDefaultId = (isset($this->request->query['schedule_interval_id']))?$this->request->query['schedule_interval_id']:key($scheduleIntervals);
        $this->set('scheduleIntervalDefaultId', $scheduleIntervalDefaultId);
        $this->set('shiftOptions', $shiftOptions);        
        $shiftDefaultId = (isset($this->request->query['shift']))?$this->request->query['shift']:key($shiftOptions);
        $this->set('academicPeriodId', $academicPeriodId);
        $this->set('academicPeriodName', $academicPeriodOptions[$academicPeriodId]);
        $this->set('shiftDefaultId', $shiftDefaultId);
        $this->set('institutionDefaultId', key($selectedInstitutionOptions));
        $this->set('ngController', 'TimetableCtrl as $ctrl');
    }
    
    public function StudentScheduleTimetable()
    {
        $userId = $this->Auth->user('id');
        
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
