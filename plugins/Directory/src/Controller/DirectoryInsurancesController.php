<?php
namespace Directory\Controller;

use Cake\Event\Event;
use Profile\Controller\InsurancesController as BaseController;

class DirectoryInsurancesController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $page = $this->Page;

        $session = $this->request->session();
        $userId = $session->read('Directory.Directories.id');
        $userName = $session->read('Directory.Directories.name');
       
        // set header
        $page->setHeader($userName . ' - ' . __('Insurances'));
        
        // set queryString
        $page->setQueryString('security_user_id', $userId);
        
        $this->setBreadCrumb(['userId' => $userId, 'userName' => $userName]);
        
        // set Tabs
        $this->setupTabElements(['userId' => $userId, 'userName' => $userName]);

        $page->get('security_user_id')->setControlType('hidden')->setValue($userId); // set value and hide the user_id
    }
}