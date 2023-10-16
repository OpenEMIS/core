<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Profile\Controller\BodyMassesController as BaseController;

class ProfileBodyMassesController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        $userId = $this->Auth->user('id');
        $userName = $this->Auth->user('name');

        parent::beforeFilter($event);

        // set header
        $page->setHeader($userName . ' - ' . __('Body Mass'));

        // set queryString
        $page->setQueryString('security_user_id', $userId);
        
        $this->setBreadCrumb(['userId' => $userId, 'userName' => $userName]);

        // set Tabs
        $this->setupTabElements(['userId' => $userId, 'userName' => $userName]);

        $page->get('security_user_id')->setControlType('hidden')->setValue($userId); // set value and hide the user_id

        $this->setTooltip();

        //disable add, edit and delete
        $page->disable(['add', 'edit', 'delete']);
    }
}
