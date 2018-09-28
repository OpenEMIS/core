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
   
private function getGuardianTabElements($options = [])
{
    $id = $this->request->session()->read('Guardian.Guardians.id');
    $plugin = 'Directory';
    $name = 'Directories';

    $tabElements = [
        'Accounts' => ['text' => __('Account')],
        'Identities' => ['text' => __('Identities')],
        'UserNationalities' => ['text' => __('Nationalities')], //UserNationalities is following the filename(alias) to maintain "selectedAction" select tab accordingly.
        'Contacts' => ['text' => __('Contacts')],
        'Languages' => ['text' => __('Languages')],
        'SpecialNeeds' => ['text' => __('Special Needs')],
        'Attachments' => ['text' => __('Attachments')],
        'Comments' => ['text' => __('Comments')]
    ];
    foreach ($tabElements as $key => $value) {
        if ($key == 'Accounts') {
            $tabElements[$key]['url']['action'] = 'Accounts';
            $tabElements[$key]['url'][] = 'view';
            $tabElements[$key]['url'][] = $this->paramsEncode(['id' => $id, 'guardian' => 'yes']);
        } else if ($key == 'Comments') {
            $url = [
                'plugin' => $plugin,
                'controller' => 'DirectoryComments',
                'action' => 'index'
            ];
            $tabElements[$key]['url'] = $this->setQueryString($url, ['security_user_id' => $id, 'guardian' => 'yes']);
        } else {
            $actionURL = $key;
            if ($key == 'UserNationalities') {
                $actionURL = 'Nationalities';
            }
            $tabElements[$key]['url'] = $this->setQueryString([
                                            'plugin' => $plugin,
                                            'controller' => $name,
                                            'action' => $actionURL,
                                            'index'],
                                            ['security_user_id' => $id, 'guardian' => 'yes']
                                        );
        }
    }
    return $this->TabPermission->checkTabPermission($tabElements);
}

public function setupTabElements($options)
{
    $page = $this->Page;
    $session = $this->request->session();
    $guardianID = $session->read('Guardian.Guardians.id');
    $studentID = $session->read('Guardian.Students.id');
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
        'SpecialNeeds' =>['text' =>  __('Special Needs')],
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
        $userId = $guardianID;
        $StudentGuardianID=$this->request->session()->read('Student.Guardians.primaryKey');
         $newStudentGuardianID=$StudentGuardianID['id'];
        $url = ['plugin' => 'Directory', 'controller' => 'Directories'];
        $guardianstabElements = [
            'Guardians' => ['text' => __('Relation')],
            'GuardianUser' => ['text' => __('Overview')]
         ];
        $action = 'StudentGuardians';
        $actionUser = 'StudentGuardianUser';
        $guardianstabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $this->paramsEncode(['id' => $newStudentGuardianID])]);
        $guardianstabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $this->paramsEncode(['id' => $userId, 'StudentGuardians.id' => $newStudentGuardianID])]);
        $guardianId = $userId;
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
