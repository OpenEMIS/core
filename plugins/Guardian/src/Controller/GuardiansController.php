<?php
namespace Guardian\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use App\Controller\AppController;

class GuardiansController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->ControllerAction->models = [
            'Accounts' => ['className' => 'Guardian.Accounts', 'actions' => ['view', 'edit']],
        ];

        $this->set('contentHeader', 'Guardians');
    }

    // CAv4
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

    public function SpecialNeeds()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.SpecialNeeds']);
    }

    public function Attachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Attachments']);
    }

    public function Guardians()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Guardians']);
    }
    public function GuardianUser()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.GuardianUser']);
    }
    public function Demographic()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Demographic']);
    }

    public function Students()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Guardian.Students']);
    }    

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $User = TableRegistry::get('User.Users');

        $session = $this->request->session();
        $institutionName = $session->read('Institution.Institutions.name');
        $institutionId = $session->read('Institution.Institutions.id');
        $studentId = $session->read('Student.Students.id');
        if (!empty($studentId)) {
            $entity = $User->get($studentId);
        } else {
            $this->Navigation->addCrumb('Guardian', ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'Guardians']);
        }
        
        $name = $entity->name;  

        $this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $this->Navigation->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', $this->ControllerAction->paramsEncode(['id' => $institutionId])]);
        $this->Navigation->addCrumb('Students', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students']);
        $this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $studentId])]);
    } 

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    { 
        $session = $this->request->session();
        $guardianName = $session->read('Guardian.Guardians.name');
        $alias = $model->alias;
        $header = $guardianName .' - '.$alias;
        $this->Navigation->addCrumb($model->getHeader('Guardian'.$alias)); 
        $this->set('contentHeader', $header);

        $session = $this->request->session();
        $userId = $session->read('Guardian.Guardians.id');
        if ($model->hasField('security_user_id')) {
            $model->fields['security_user_id']['type'] = 'hidden';
            $model->fields['security_user_id']['value'] = $userId;
        }
    }

    public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options)
    {
        $session = $this->request->session();

            if ($session->check('Guardian.Guardians.id')) {
                if ($model->hasField('security_user_id')) {
                    $userId = $session->read('Guardian.Guardians.id');
                    $query->where([$model->aliasField('security_user_id') => $userId]);
                }
            } else {
                $this->Alert->warning('general.noData');
                $event->stopPropagation();
                return $this->redirect(['action' => 'index']);
            }
    }
  
    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    //Related getGuardianTabElements function in StudentsController
    public function getGuardianTabElements( $options = [])
    {
        $session = $this->request->session();
        $StudentGuardianId = $session->read('Student.Guardians.primaryKey')['id'];
        $id = (array_key_exists('queryString', $this->request->query)) ? $id = $this->ControllerAction->getQueryString('security_user_id') : $session->read('Guardian.Guardians.id');

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
        }

        return $this->TabPermission->checkTabPermission($tabElements);
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
            } else if ($key == 'Comments') {
                $url = [
                    'plugin' => $plugin,
                    'controller' => 'DirectoryComments',
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

        if (array_key_exists('userRole', $options) && $options['userRole'] == 'Guardians') {
            $session = $this->request->session();
            $StudentGuardianId = $session->read('Student.Guardians.primaryKey')['id'];
            $relationTabElements = [
                'Guardians' => ['text' => __('Relation')],
                'GuardianUser' => ['text' => __('Overview')]
            ];
            $url = ['plugin' => 'Directory', 'controller' => 'Directories'];
            $relationTabElements['Guardians']['url'] = array_merge($url, ['action' => 'StudentGuardians', 'view', $this->paramsEncode(['id' => $StudentGuardianId])]);
            $relationTabElements['GuardianUser']['url'] = array_merge($url, ['action' => 'StudentGuardianUser', 'view', $this->paramsEncode(['id' => $id, 'StudentGuardians.id' => $StudentGuardianId])]);
            $tabElements = array_merge($relationTabElements, $tabElements);
            unset($tabElements[$this->name]);
        } elseif (array_key_exists('userRole', $options) && $options['userRole'] == 'Students') {
            $session = $this->request->session();
            $StudentGuardianId = $session->read('Student.Guardians.primaryKey')['id'];
            $relationTabElements = [
                'Students' => ['text' => __('Relation')],
                'StudentUser' => ['text' => __('Overview')]
            ];
            $url = ['plugin' => 'Directory', 'controller' => 'Directories'];
            $relationTabElements['Students']['url'] = array_merge($url, ['action' => 'GuardianStudents', 'view', $this->paramsEncode(['id' => $StudentGuardianId])]);
            $relationTabElements['StudentUser']['url'] = array_merge($url, ['action' => 'GuardianStudentUser', 'view', $this->paramsEncode(['id' => $id, 'StudentGuardians.id' => $StudentGuardianId])]);
            $tabElements = array_merge($relationTabElements, $tabElements);
            unset($tabElements[$this->name]);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }
}
