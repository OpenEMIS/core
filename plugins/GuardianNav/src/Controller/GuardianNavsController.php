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
        'StudentUser',
        'StudentAccount'
    ];

    public function initialize(){
        parent::initialize();
        $this->ControllerAction->models = [
            // Users
            'StudentAccount'    => ['className' => 'GuardianNav.StudentAccount', 'actions' => ['view', 'edit']],
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

    public function Demographic()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Demographic']);
    }

    public function Identities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Identities']);
    }

    public function Nationalities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserNationalities']);
    }

    public function Contacts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Contacts']);
    }
    public function Languages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserLanguages']);
    }
    public function Attachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Attachments']);
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

        $studentModels = [
                'StudentProgrammes' => __('Programmes'),
                'StudentRisks' => __('Risks'),
                'StudentTextbooks' => __('Textbox'),
                'StudentAssociations' => __('Associations')
            ];
            if (array_key_exists($alias, $studentModels)) {
                // add Students and student name
                if ($session->check('Student.Students.name')) {
                    $studentName = $session->read('Student.Students.name');
                    $studentId = $session->read('Student.Students.id');

                    // Breadcrumb
                    $this->Navigation->addCrumb('GuardianNavs', ['plugin' => $this->plugin, 'controller' => 'GuardianNavs', 'action' => 'Students']);
                    $this->Navigation->addCrumb($studentName, ['plugin' => $this->plugin, 'controller' => 'GuardianNavs', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $studentId])]);
                    $this->Navigation->addCrumb($studentModels[$alias]);

                    // header name
                    $header = $studentName;
                }
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
        /*if ($action == 'StudentUser') {
            $session->write('Student.Students.id', $this->ControllerAction->paramsDecode($this->request->pass[1])['id']);
        } */
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
            'Guardians' => ['text' => __('Guardians')],
            'StudentTransport' => ['text' => __('Transport')]
        ];

        foreach ($tabElements as $key => $value) {
            if ($key == $this->name) {
                $tabElements[$key]['url']['action'] = 'GuardianNavs';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $id]);
            } else if ($key == 'Accounts') {
                $tabElements[$key]['url']['plugin'] = $plugin;
                $tabElements[$key]['url']['controller'] = 'GuardianNavs';
                $tabElements[$key]['url']['action'] = 'StudentAccount';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $id]);
            } else if ($key == 'Comments') {
                $url = [
                    'plugin' => $plugin,
                    'controller' => 'GuardianNavComments',
                    'action' => 'index'
                ];
                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString($url, ['security_user_id' => $id]);
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
}
