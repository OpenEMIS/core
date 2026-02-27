<?php
namespace Profile\Controller;

use Cake\Event\EventInterface;
use Profile\Controller\BodyMassesController as BaseController;

class ProfileBodyMassesController extends BaseController
{
    public function beforeFilter(EventInterface $event)
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
        $this->setupHealthTabElements(['userId' => $userId]);

        $page->get('security_user_id')->setControlType('hidden')->setValue($userId); // set value and hide the user_id

        $this->setTooltip();

        //disable add, edit and delete
        $page->Disable(['add', 'edit', 'delete']);
    }

    public function beforeRender(EventInterface $event)
    {
        // if (!array_key_exists('_serialize', $this->viewVars) &&
        //     in_array($this->response->type(), ['application/json', 'application/xml'])
        // ) {
        //     $this->set('_serialize', true);
        // }
        $this->set('_serialize', true);
        $this->viewBuilder()->addHelper('Label');
        $this->viewBuilder()->addHelper('Text');
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
        $this->viewBuilder()->addHelper('ControllerAction.HtmlField');
        $this->viewBuilder()->addHelper('OpenEmis.Navigation');
        $this->viewBuilder()->addHelper('OpenEmis.Resource');
        $this->viewBuilder()->addHelpers(['Html', 'Form', 'Paginator', 'Label', 'Url']);

    }
}
