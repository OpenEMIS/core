<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Profile\Controller\CommentsController as BaseController;

class ProfileCommentsController extends BaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->Page->disable(['add', 'delete', 'edit']);
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        $userId = $this->Auth->user('id');
        $userName = $this->Auth->user('name');
        $encodedUserId = $this->paramsEncode(['id' => $userId]);
        $nationalityId = $this->Users->get($userId)->nationality_id;

        parent::beforeFilter($event);

        // set Header
        $header = $page->getHeader();
        $page->setHeader($userName . ' - ' . $header);

        // set QueryString (for findIndex)
        $page->setQueryString('security_user_id', $userId);

        // set Breadcrumb
        $page->addCrumb('Profile', ['plugin' => $this->plugin, 'controller' => 'Profiles', 'action' => 'Profiles', 'view', $encodedUserId]);
        $page->addCrumb($userName);
        $page->addCrumb('Comments');

        // set Tabs url
        $tabs = $page->getTabs();
        foreach ($tabs as $name => $tab) {
            // subaction is set in BaseController
            $tempParam = ['plugin' => $this->plugin, 'controller' => 'Profiles', 'action' => $name, '1' => $encodedUserId];

            // exceptions
            if ($name == 'Overview') {
                $tempParam['action'] = 'Profiles';

            } else if ($name == 'Comments') {
                $tempParam['controller'] = 'ProfileComments';
                unset($tempParam['action']);

            } else if ($name == 'Nationalities') {
                $tempParam[1] = $this->paramsEncode(['security_user_id' => $userId,'nationality_id' => $nationalityId]);
            }

            $url = array_merge($tab['url'], $tempParam);
            $page->getTab($name)->setUrl($url);
        }
    }
}
