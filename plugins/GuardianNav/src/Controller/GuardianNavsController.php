<?php
namespace GuardianNav\Controller;

use ArrayObject;
use Exception;

use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use Cake\I18n\Date;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use App\Model\Traits\OptionsTrait;
use GuardianNav\Controller\AppController;
use ControllerAction\Model\Traits\UtilityTrait;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Session;
use Cake\Http\ServerRequest;

class GuardianNavsController extends AppController
{
    use OptionsTrait;
    use UtilityTrait;
    private $features = [
        'Students',
        'StudentUser'
    ];

    private $redirectedViewFeature = [
        // student academic
        'Programmes',
        'StudentClasses',
        'StudentSubjects',
        'Textbooks',
        'StudentRisks',
        'StudentBehaviours',
        'StudentExtracurriculars',
        'StudentAbsences',
        'Absences'
    ];

    private $studentViewFeature = [
        'Students'
    ];

    public function initialize(): void {
        parent::initialize();
        $this->ControllerAction->models = [
            // Student
            //'StudentAbsences'       => ['className' => 'Student.Absences', 'actions' => ['index']],
           // 'StudentBehaviours'     => ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']], //POCOR-8596 comment this
            'StudentExtracurriculars' => ['className' => 'Student.GuardianExtracurriculars'],
        ];
        $this->loadComponent('Training.Training');
        $this->loadComponent('User.Image');
        $this->loadComponent('TabPermission');
        $this->attachAngularModules();

        $this->set('contentHeader', 'Guardian');
    }

    public function GuardianNavs() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'GuardianNav.Students']);
    }

    public function StudentUser()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'GuardianNav.StudentUser']);
    }

    public function StudentProgrammes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Programmes']);
    }
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
    public function StudentClasses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentClasses']);
    }
    public function StudentSubjects()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentSubjects']); }
    public function StudentOutcomes()         {
        $comment = $this->request->getQuery('comment');
        if(!empty($comment) && $comment == 1){
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomeComments']);

        }else{
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomes']);
        }

    }
    public function StudentCompetencies()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentCompetencies']); }
    public function StudentAwards()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Awards']); }
    public function StudentTextbooks()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Textbooks']); }
    public function StudentAssociations()    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.InstitutionAssociationStudent']);}
    public function StudentRisks() {  $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentRisks']);}
    public function StudentAbsences()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'GuardianNav.Absences']);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra) {
		$header = 'Students';
        $this->Navigation->addCrumb($header, ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'GuardianNavs']);
        $viewPermission = $this->AccessControl->check(['StudentUser']);
        if ($viewPermission == 0) {
            $this->redirectedViewFeature = array_merge($this->redirectedViewFeature, $this->studentViewFeature);
        }
        if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4
            $alias = $model->getAlias();
            // redirected view feature is to cater for the link that redirected to institution
            if (in_array($alias, $this->redirectedViewFeature)) {
                $model->toggle('view', false);
            }
        } elseif ($model instanceof \App\Model\Table\AppTable) {// CAv3
            // CAv3 hide button and redirect when user change the Url
                    $model->addBehavior('ControllerAction.HideButton');
        }
        // add Students and student name
        $session = $this->request->getSession();
        if ($session->check('Student.Students.name')) {
            if ($this->request->action== 'GuardianNavs') {
                $studentName = $session->read('Auth.User.name');
                $studentId = $session->read('Auth.User.id');
            } else {
                $studentName = $session->read('Student.Students.name');
                $studentId = $session->read('Student.Students.id');
            }

            // Breadcrumb
            $this->Navigation->addCrumb($studentName);

            // header name
            $header = $studentName;
        } else if($this->request->getParam('action') == 'StudentUser') { // POCOR-8293
            $request = $this->request;
            $studentId = $this->paramsDecode($request->getParam('pass')[1])['id'];
            $StudentsTable = TableRegistry::getTableLocator()->get('GuardianNav.StudentUser');
            $Student = $StudentsTable
                ->find('all')
                ->where([$StudentsTable->aliasField('id') => $studentId])
                ->first();
            $studentName = $Student->name;
            $this->Navigation->addCrumb($studentName);
            $header = $studentName;
        }
        $persona = false;
        if (is_object($persona) && get_class($persona)=='User\Model\Entity\User') {
                $header = $persona->name . ' - ' . $model->getHeader($alias);
                $model->addBehavior('Institution.InstitutionUserBreadcrumbs');
            }  elseif ($model->getAlias() == 'StudentRisks') {
                $header .= ' - '. __('Risks');
            } elseif ($model->getAlias() == 'InstitutionStudentRisks') {
                $header .= ' - '. __('Institution Student Risks');
                $this->Navigation->substituteCrumb($model->getHeader($alias), __('Institution Student Risks'));
            }elseif ($model->getAlias() == 'InstitutionAssociationStudent') {
                $header .= ' - '. __('Houses'); //POCOR-7938
            } else {
                $header .= ' - ' . $model->getHeader($alias);
        }
        $this->set('contentHeader', $header);
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $session = $this->request->getSession();
        $this->Navigation->addCrumb('Guardian', ['plugin' => 'GuardianNav', 'controller' => 'GuardianNavs', 'action' => 'GuardianNavs', 'index']);
        $action = $this->request->getAttribute('action');
        $header = __('Student');

        if (($action == 'StudentUser') && (empty($this->ControllerAction->paramsPass()) || $this->ControllerAction->getParam('Pass')[0] == 'view' )) {
            $session->delete('Guardian.Guardians.id');
            $session->delete('Guardian.Guardians.name');
        }
        if($session->check('Student.Students.name')){
            $name = $session->read('Student.Students.name');
        }
        $sub_header = '';
        //echo $action;die();
        if($action == 'StudentResults'){
            $sub_header = 'Assessments';
        } elseif ($action == 'StudentExaminationResults') {
            $sub_header = 'Overview';
        }
        $header = $name .' - '. $sub_header;
        // this is to cater for back links
        $query = $this->request->getQuery();
        $this->set('contentHeader', $header);
    }

    public function getUserTabElements($options = [])
    {
        if (array_key_exists('queryString', $this->request->getQuery())) { //to filter if the URL already contain querystring
            $id = $this->ControllerAction->getQueryString('security_user_id');
        }

        $plugin = $this->getPlugin();
        $name = $this->getName();

        $id = (isset($options['id']))? $options['id']: $this->request->getSession()->read($plugin.'.'.$name.'.id');

        if (isset($options['userRole']) && $options['userRole'] == 'Guardians' && isset($options['entity'])) {
            $session = $this->request->getSession();
            $session->write('Guardian.Guardians.name', $options['entity']->user->name);
            $session->write('Guardian.Guardians.id', $options['entity']->user->id);
            $session->write('Directory.Directories.studentToGuardian', 'studentToGuardian');
        } elseif (isset($options['userRole']) && $options['userRole'] == 'Students' && isset($options['entity'])) {
            $session = $this->request->getSession();
            $session->write('Student.Students.name', $options['entity']->user->name);
            $session->write('Student.Students.id', $options['entity']->user->id);
            $session->write('Directory.Directories.guardianToStudent', 'guardianToStudent');
        }

        $tabElements = [
            'StudentUser' => ['text' => __('Overview')]
        ];

        $studentTabElements = [
            'Guardians' => ['text' => __('Guardians')]
        ];
        $tabElements = array_merge($tabElements, $studentTabElements);
        $queryString = $this->request->getQuery('queryString');
        foreach ($tabElements as $key => $value) {
            $session = $this->request->getSession();
            $studentId = $session->read('Student.Students.id');

            if($key == 'StudentUser'){
                $key = 'GuardianNavs'; //this is done because of tab-active on Overview tab
            }

            if ($key == $this->name) {
                if($key == 'GuardianNavs'){
                    $key = 'StudentUser'; //this is done because of tab-active on Overview tab
                }
                $tabElements[$key]['url']['action'] = 'StudentUser';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $studentId]);
                $tabElements[$key]['url']['queryString'] = $queryString;
            } else {
                $actionURL = $key;
                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString([
                                                'plugin' => $plugin,
                                                'controller' => $name,
                                                'action' => $actionURL,
                                                'index'],
                                                ['security_user_id' => $studentId]
                                            );
            }
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getAcademicTabElements($options = [])
    {
        // POCOR-8415:start
        $queryString = $this->ControllerAction->getQueryString();
        if (empty($queryString)) {
            $queryString = $this->getQueryString();
        }
        $studentId = $queryString['student_id'] ?? null;
        $institutionStudentID = $queryString['institution_student_id'] ?? null;
        $encodedQueryString = !empty($queryString) ? $this->ControllerAction->paramsEncode($queryString) : null;
        // POCOR-8415:end
        $id = (isset($options['id']))? $options['id']: 0;
        $type = (isset($options['type']))? $options['type']: null;
        $period = (isset($options['academic_period']))? $options['academic_period']: null;
        $tabElements = [];
        $studentUrl = ['plugin' => 'GuardianNav', 'controller' => 'GuardianNavs'];
        //$session = $this->request->getSession();
        //$studentId = $session->read('Student.Students.id');
        $action = $this->request->getParam('action');
        if($action == 'StudentProgrammes' && isset($this->request->getQueryParams()['studentId'])) {
            //POCOR-8379 starts
            $session = $this->request->getSession();
            $studentId = $session->read('Student.Students.id');
            if(empty($studentId)){
                $studentId = $this->request->getQueryParams()['studentId'];
                $studentId = $this->ControllerAction->paramsDecode($studentId);
            }//POCOR-8379 ends
            if(empty($studentId)){
                $studentId = $queryString['student_id'];
            }//POCOR-8379 ends
            if(!empty($studentId)) {
                $StudentsTable = TableRegistry::getTableLocator()->get('Institution.Students');
                $Student = $StudentsTable
                    ->find('all')
                    ->where([$StudentsTable->aliasField('student_id') => $studentId])
                    ->first();
                if (!empty($Student)) {
                    $institutionId = $Student->institution_id;
                }
                $params = [
                    'student_id' => $studentId,
                    'user_id' => $studentId,
                    'institution_id' => $institutionId,
                    'type' => $type];
                $queryString = $this->ControllerAction->paramsEncode($params);
            }
        } else {
            $queryString = $this->request->getQuery('queryString');
        }
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
            //'Extracurriculars' => ['text' => __('Extracurriculars')],//POCOR-7648
            'Textbooks' => ['text' => __('Textbooks')],
            'Risks' => ['text' => __('Risks')],
            'Associations' => ['text' => __('Houses')], //POCOR-7938
            'Curriculars' => ['text' => __('Curriculars')]
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);
        // POCOR-8415:start
        if($encodedQueryString){
            $queryString = $encodedQueryString;
        }
        // POCOR-8415:end
        foreach ($studentTabElements as $key => $tab) {
            if(!empty($period) && $key == 'Absences') {
                $tabElements[$key]['url'] = array_merge($studentUrl, [
                    'action' =>'Student'.$key,
                    'queryString' => $queryString,
                    'academic_period' => $period]);
            } else {
                $tabElements[$key]['url'] = array_merge($studentUrl, [
                    'action' =>'Student'.$key,
                    '0' => 'index',
                    '1' => $encodedQueryString,
                'type' => $type]);
            }
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function StudentScheduleTimetable()
    {
        $userId = $this->Auth->user('id');

        $InstitutionStudents =
        TableRegistry::getTableLocator()->get('Institution.InstitutionStudents')
        ->find()
        ->where([
            'InstitutionStudents.student_id' => $userId
        ])
        ->disableHydration() // POCOR-8533
        ->first();

        $institutionId = $InstitutionStudents['institution_id'];
        $academicPeriodId = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')
        ->getCurrent();

        $InstitutionClassStudentsResult =
        TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents')
        ->find()
        ->where([
            'academic_period_id'=>$academicPeriodId,
            'student_id' => $userId,
            'institution_id' => $institutionId
        ])
        ->disableHydration() // POCOR-8533
        ->first();

        $institutionClassId = $InstitutionClassStudentsResult['institution_class_id'];
        $ScheduleTimetables = TableRegistry::getTableLocator()->get('Schedule.ScheduleTimetables')
        ->find()
        ->where([
            'academic_period_id'=>$academicPeriodId,
            'institution_class_id' => $institutionClassId,
            'institution_id' => $institutionId,
            'status' => 2
        ])
        ->disableHydration() // POCOR-8533
        ->first();

        $this->set('userId', $userId);
        $timetable_id = (isset($ScheduleTimetables['id']))?$ScheduleTimetables['id']:0;
        $this->set('timetable_id', $timetable_id);
        $this->set('academicPeriodId', $academicPeriodId);
        $this->set('institutionDefaultId', $institutionId);
        $this->set('ngController', 'StudentTimetableCtrl as $ctrl');
    }

    // AngularJS
    public function StudentResults()
    {
        $session = $this->request->getSession();
        //$studentId = $this->Auth->user('id');
        $sId = $this->request->getParam('pass')[1];

        // tabs
        $options['type'] = 'student';
        $tabElements = $this->getAcademicTabElements($options);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', 'Results');
        // End

        $this->set('ngController', 'StudentResultsCtrl as StudentResultsController');
    }

    private function attachAngularModules() {
        $action = $this->request->getParam('action');

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

    public function StudentExaminationResults()
    {
        $session = $this->request->getSession();
        $studentId = $session->read('Student.Students.id');
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

    /**POCOR-6845 - modified _FUNCTION_ to __FUNCTION__ as PHP function name is case sesitive and ealier it was not recognition function */
    public function StudentReportCards()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentReportCards']); }

    //POCOR-8293 Start
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

    public function HealthBodyMasses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.BodyMasses']);
    }

    public function HealthInsurances()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Insurances']);
    }
    //POCOR-8293 End Student Health tab in Guardian
    //POCOR-8596
    public
    function StudentBehaviours()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentBehaviours']);
    }

}
