<?php
namespace GuardianNav\Controller;

use ArrayObject;
use Exception;

use Cake\Event\Event;
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

class GuardianNavsController extends AppController
{
    use OptionsTrait;
    use UtilityTrait;
    private $features = [
        'Students',
        'StudentUser'
    ];

    public function initialize(){
        parent::initialize();
        $this->ControllerAction->models = [
            // Users
            //'Accounts'              => ['className' => 'GuardianNav.Accounts', 'actions' => ['view', 'edit']],
        ];
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

    public function Classes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionClasses']);
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
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = 'Students';    
        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);

        // add Students and student name
        $session = $this->request->session();
        if ($session->check('Student.Students.name')) {
            if ($this->request->action== 'GuardianNavs') {
                $studentName = $session->read('Auth.User.name');
                $studentId = $session->read('Auth.User.id');
            } else {
                $studentName = $session->read('Student.Students.name');
                $studentId = $session->read('Student.Students.id');
            }
            
            // Breadcrumb
            $this->Navigation->addCrumb($studentName, ['plugin' => $this->plugin, 'controller' => 'GuardianNavs', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $studentId])]);
                
            // header name
            $header = $studentName;
        }

        $this->set('contentHeader', $header); 
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $session = $this->request->session();
        $this->Navigation->addCrumb('Guardian', ['plugin' => 'GuardianNav', 'controller' => 'GuardianNavs', 'action' => 'GuardianNavs', 'index']);
        $action = $this->request->params['action'];
        $header = __('Student');

        if (($action == 'StudentUser') && (empty($this->ControllerAction->paramsPass()) || $this->ControllerAction->paramsPass()[0] == 'view' )) {
            $session->delete('Guardian.Guardians.id');
            $session->delete('Guardian.Guardians.name');
        }
        // this is to cater for back links
        $query = $this->request->query;

        $this->set('contentHeader', $header);
    }

    public function getUserTabElements($options = [])
    { 
        if (array_key_exists('queryString', $this->request->query)) { //to filter if the URL already contain querystring
            $id = $this->ControllerAction->getQueryString('security_user_id');
        }

        $plugin = $this->plugin;
        $name = $this->name;

        $id = (array_key_exists('id', $options))? $options['id']: $this->request->session()->read($plugin.'.'.$name.'.id');

        if (array_key_exists('userRole', $options) && $options['userRole'] == 'Guardians' && array_key_exists('entity', $options)) {
            $session = $this->request->session();
            $session->write('Guardian.Guardians.name', $options['entity']->user->name);
            $session->write('Guardian.Guardians.id', $options['entity']->user->id);
            $session->write('Directory.Directories.studentToGuardian', 'studentToGuardian');
        } elseif (array_key_exists('userRole', $options) && $options['userRole'] == 'Students' && array_key_exists('entity', $options)) {
            $session = $this->request->session();
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
        //$userId = $session->read('Student.Students.id');

        foreach ($tabElements as $key => $value) {
            $session = $this->request->session();
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

            } else {
                /*if($key == 'GuardianNavs'){
                    $actionURL = 'GuardianNavs';
                }*/
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
        $id = (array_key_exists('id', $options))? $options['id']: 0;
        $type = (array_key_exists('type', $options))? $options['type']: null;

        $tabElements = [];
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

        // Programme will use institution controller, other will be still using student controller
        foreach ($studentTabElements as $key => $tab) {
            if (in_array($key, ['Programmes', 'Textbooks', 'Risks','Associations'])) {
                $studentUrl = ['plugin' => 'GuardianNav', 'controller' => 'GuardianNavs'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', 'type' => $type]);
            } else {
                $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
            }
        }
        //echo '<pre>';print_r($tabElements);die;
        return $this->TabPermission->checkTabPermission($tabElements);
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
