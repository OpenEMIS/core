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
        $guardianName = $session->read('Guardian.Guardians.name');
        $guardianId = $session->read('Guardian.Guardians.id');

        parent::beforeFilter($event);

        // set Header
        $page->setHeader($guardianName . ' - ' . __('Comments'));        

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
        $guardianId = $session->read('Guardian.Guardians.id');
        $StudentGuardianId = $session->read('Student.Guardians.primaryKey')['id'];

        $page = $this->Page;
        $plugin = $this->plugin;

        $nationalityId = $this->Users->get($guardianId)->nationality_id;
        $encodedUserId = $this->paramsEncode(['security_user_id' => $guardianId]);
        $encodedUserAndNationalityId = $this->paramsEncode(['security_user_id' => $guardianId,'nationality_id' => $nationalityId]);
        $pluralPlugin = Inflector::pluralize($plugin);

        $tabElements = [
            'Guardians' => ['text' => __('Relation')],
            'GuardianUser' => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Demographic' => ['text' => __('Demographic')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' =>['text' =>  __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')]
        ];

        foreach ($tabElements as $action => $obj) {
            if ($action == 'Guardians') {
                $url = [
                        'plugin' => 'Student',
                        'controller' => 'Students',
                        'action' => 'Guardians',
                        'view',
                        $this->paramsEncode(['id' => $StudentGuardianId])
                ];
            } elseif ($action == 'GuardianUser') {
                $url = [
                        'plugin' => 'Student',
                        'controller' => 'Students',
                        'action' => 'GuardianUser',
                        'view',
                        $this->paramsEncode(['id' => $guardianId, 'StudentGuardians.id' => $StudentGuardianId])
                ];
            } elseif (in_array($action, ['Accounts'])) {
                $url = [
                    'plugin' => $plugin,
                    'controller' => $pluralPlugin,
                    'action' => $action,
                    'view',
                    $this->paramsEncode(['id' => $guardianId])
                ];
            } elseif ($action == 'Comments') {
                $url = [
                        'plugin' => 'Guardian',
                        'controller' => 'GuardianComments',
                        'action' => 'index'
                ];
            } elseif ($action == 'UserNationalities') {
                $url = [
                        'plugin' => 'Guardian',
                        'controller' => 'Guardians',
                        'action' => 'Nationalities',
                        'index',
                        'queryString' => $encodedUserAndNationalityId
                ];
            } else {
                $url = [
                    'plugin' => 'Guardian',
                    'controller' => 'Guardians',
                    'action' => $action,
                    'index',
                    'queryString' => $encodedUserId
                ];
            }
            $tabElements[$action]['url'] = $url;
        }

        foreach ($tabElements as $action => $obj) {
            $page->addTab($action)
                ->setTitle($obj['text'])
                ->setUrl($obj['url']);
        }

        // set active tab
        $page->getTab('Comments')->setActive('true');
    }
}
