<?php
namespace Guardian\Controller;

use Cake\Event\Event;
use Cake\Utility\Inflector;

use Profile\Controller\CommentsController as BaseController;

class GuardianCommentsController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        $session = $this->request->session();
        $institutionName = $session->read('Institution.Institutions.name');
        $guardianName = $session->read('Guardian.Guardians.name');
        $guardianId = $session->read('Guardian.Guardians.id');

        parent::beforeFilter($event);

        // set Header
        $page->setHeader($guardianName . ' - Comments');

        // set QueryString (for findIndex)
        $page->setQueryString('security_user_id', $guardianId);

        // set Breadcrumb
        $this->setBreadCrumb(['userId' => $guardianId, 'userName' => $guardianName]);

        $this->setupTabElements(['userId' => $guardianId, 'userName' => $guardianName]);
    }

    public function add()
    {
        $page = $this->Page;

        $session = $this->request->session();   

        $guardianId = $session->read('Guardian.Guardians.id');
        $page->get('security_user_id')->setValue($guardianId);

        parent::add();
    }

    public function setupTabElements($options)
    {
        $session = $this->request->session();
        $guardianID = $session->read('Guardian.Guardians.id');

        $page = $this->Page;
        $plugin = $this->plugin;

        $nationalityId = $this->Users->get($guardianID)->nationality_id;
        $encodedUserId = $this->paramsEncode(['security_user_id' => $guardianID]);
        $encodedUserAndNationalityId = $this->paramsEncode(['security_user_id' => $guardianID,'nationality_id' => $nationalityId]);
        $pluralPlugin = Inflector::pluralize($plugin);

        $tabElements = [
            'Accounts' => ['text' => __('Account')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' =>['text' =>  __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')]
        ];

        foreach ($tabElements as $action => $obj) {
            if (in_array($action, ['Accounts'])) {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $pluralPlugin,
                    'action' => $action,
                    'view',
                    $this->paramsEncode(['id' => $guardianID])
                ];
            } elseif ($action == 'Comments') {
                $url = [
                        'plugin' => 'Guardian',
                        'controller' => 'GuardianComments',
                        'action' => 'index'
                ];
            } else {
                $url = [
                    'plugin' => 'Guardian',
                    'controller' => 'Guardians',
                    'action' => $action,
                    'index',
                    'queryString' => $encodedUserId
                ];

                // exceptions
                if ($action == 'UserNationalities') {
                    $url['action'] = 'Nationalities';
                    $url['queryString'] = $encodedUserAndNationalityId;
                }
            }
            $tabElements[$action]['url'] = $url;
        }

        $StudentGuardianID = $this->request->session()->read('Student.Guardians.primaryKey')['id'];
        $url = ['plugin' => 'Student', 'controller' => 'Students'];
        $guardianstabElements = [
            'Guardians' => ['text' => __('Relation')],
            'GuardianUser' => ['text' => __('Overview')]
         ];
        $action = 'Guardians';
        $actionUser = 'GuardianUser';
        $guardianstabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $this->paramsEncode(['id' => $StudentGuardianID])]);
        $guardianstabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $this->paramsEncode(['id' => $guardianID, 'StudentGuardians.id' => $StudentGuardianID])]);
        $tabElements = array_merge($guardianstabElements, $tabElements);

        foreach ($tabElements as $action => $obj) {
            $page->addTab($action)
                ->setTitle($obj['text'])
                ->setUrl($obj['url']);
        }

        // set active tab
        $page->getTab('Comments')->setActive('true');
    }
}
