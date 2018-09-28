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
}
