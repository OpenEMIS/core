<?php
namespace Profile\Controller;

use Cake\Event\EventInterface;
use Profile\Controller\CommentsController as BaseController;

class ProfileCommentsController extends BaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->Page->disable(['delete']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(EventInterface $event, $action)
    {
        return true;
    }

    public function beforeFilter(EventInterface $event)
    {
        $page = $this->Page;
        $userId = $this->Auth->user('id');
        $userName = $this->Auth->user('name');

        parent::beforeFilter($event);

        // setup
        $page->setHeader($userName . ' - Comments');
        $page->setQueryString('security_user_id', $userId);
        $this->setBreadCrumb(['userId' => $userId, 'userName' => $userName]);
//        $this->setupUserTabElements(['userId' => $userId, 'userName' => $userName]);
    }
}
