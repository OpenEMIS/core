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
        'StudentUser'
    ];

    public function initialize(){
        parent::initialize();
    }

    public function GuardianNavs() {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'GuardianNav.Students']);
    }

    public function StudentUser()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'GuardianNav.StudentUser']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = 'Guardian';    
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
                    /*$this->Navigation->addCrumb('Students', ['plugin' => $this->plugin, 'controller' => 'GuardianNavs', 'action' => 'Students', 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])]);*/
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
        $this->Navigation->addCrumb('GuardianNavs', ['plugin' => 'GuardianNav', 'controller' => 'GuardianNavs', 'action' => 'GuardianNavs', 'index']);
        $action = $this->request->params['action'];
        $header = __('Guardian');

        if (($action == 'StudentUser') && (empty($this->ControllerAction->paramsPass()) || $this->ControllerAction->paramsPass()[0] == 'view' )) {
            $session->delete('Guardian.Guardians.id');
            $session->delete('Guardian.Guardians.name');
        }
        // this is to cater for back links
        $query = $this->request->query;
        if ($action == 'StudentUser') {
            $session->write('Student.Students.id', $this->ControllerAction->paramsDecode($this->request->pass[1])['id']);
        } 
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
            $this->name => ['text' => __('Overview')],
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
            if ($key == $this->name) {
                $tabElements[$key]['url']['action'] = 'Directories';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $id]);
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
}
