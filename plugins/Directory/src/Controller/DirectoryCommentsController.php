<?php
namespace Directory\Controller;

use Cake\Event\Event;
use Profile\Controller\CommentsController as BaseController;

class DirectoryCommentsController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        $requestQuery = $this->request->query;
        $userId = $this->paramsDecode($requestQuery['queryString'])['security_user_id'];
        $userName = $this->Users->get($userId)->name;

        parent::beforeFilter($event);

        // setup
        $page->setHeader($userName . ' - ' . __('Comments'));
        $page->setQueryString('security_user_id', $userId);
        $this->setBreadCrumb(['userId' => $userId, 'userName' => $userName]);
        $this->setupTabElements(['userId' => $userId, 'userName' => $userName]);
    }

    public function add()
    {
        $page = $this->Page;
        $requestQuery = $this->request->query;

        $userId = $this->paramsDecode($requestQuery['queryString'])['security_user_id'];
        $page->get('security_user_id')->setValue($userId);

        parent::add();
    }
    
    public function setupTabElements($options)
    {
        $page = $this->Page;
        $session = $this->request->session();
        $guardianID = $session->read('Guardian.Guardians.id');
        $userId = array_key_exists('userId', $options) ? $options['userId'] : 0;
        $encodedUserId = $this->paramsEncode(['security_user_id' => $userId]);
        $nationalityId = $this->Users->get($userId)->nationality_id;
        $encodedUserAndNationalityId = $this->paramsEncode(['security_user_id' => $userId,'nationality_id' => $nationalityId]);

        $tabElements = [
            'Directories' => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' =>['text' =>  __('Nationalities')],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')]
        ];
        foreach ($tabElements as $action => $obj) {
            if (in_array($action, ['Directories', 'Accounts'])) {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => $action,
                    'view',
                    $this->paramsEncode(['id' => $userId])
                ];
            } elseif ($action == 'Comments') {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'DirectoryComments',
                    'action' => 'index',
                    'queryString' => $encodedUserId
                ];
            } else {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
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
        if (!empty($guardianID)) {
            $StudentGuardianID = $this->request->session()->read('Student.Guardians.primaryKey')['id'];
            $url = ['plugin' => 'Directory', 'controller' => 'Directories'];
            $guardianstabElements = [
                'Guardians' => ['text' => __('Relation')],
                'GuardianUser' => ['text' => __('Overview')]
             ];
            $guardianstabElements['Guardians']['url'] = array_merge($url, ['action' => 'StudentGuardians', 'view', $this->paramsEncode(['id' => $StudentGuardianID])]);
            $guardianstabElements['GuardianUser']['url'] = array_merge($url, ['action' => 'StudentGuardianUser', 'view', $this->paramsEncode(['id' => $guardianID, 'StudentGuardians.id' => $StudentGuardianID])]);
            $tabElements = array_merge($guardianstabElements, $tabElements);
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
