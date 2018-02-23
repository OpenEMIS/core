<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class ApiSecuritiesController extends PageController
{
    const DENY = 0;
    const ALLOW = 1;

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('ApiSecuritiesScopes');
        $this->loadModel('ApiScopes');
        $this->loadModel('ApiSecurities');
        $this->Page->loadElementsFromTable($this->ApiSecuritiesScopes);
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderIndex'] = 'onRenderIndex';
        $event['Controller.Page.onRenderView'] = 'onRenderView';
        $event['Controller.Page.onRenderAdd'] = 'onRenderAdd';
        $event['Controller.Page.onRenderEdit'] = 'onRenderEdit';
        $event['Controller.Page.onRenderDelete'] = 'onRenderDelete';
        $event['Controller.Page.onRenderExecute'] = 'onRenderExecute';

        return $event;
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $page = $this->Page;
        $page->addCrumb('API Securities', [
            'plugin' => false,
            'controller' => 'ApiSecurities',
            'action' => 'index'
        ]);

        $page->get('index')->setLabel(__('List'));
        $page->get('api_scope_id')->setLabel(__('API Scopes'));
        $page->get('api_security_id')->setLabel(__('API Securities'));

        // set header
        $page->setHeader(__('API Securities'));
    }

    public function index()
    {
        parent::index();

        $page = $this->Page;

        $allScopes = $this->ApiScopes
            ->find('optionList', ['defaultOption' => false])
            ->toArray();

        $scopeOptions = [null => __('All Scopes')] + $allScopes;
        $page
            ->addFilter('api_scope_id')
            ->setOptions($scopeOptions);

        $this->disableSort();
    }

    public function add()
    {
        parent::add();
        $page = $this->Page;

        $page
            ->get('api_scope_id')
            ->setId('api_scope_id')
            ->setControlType('select');

        $page
            ->get('api_security_id')
            ->setControlType('select')
            ->setDependentOn('api_scope_id')
            ->setParams('ApiSecurities');

        $this->addEdit();
    }

    public function edit($id)
    {
        $page = $this->Page;

        $page
            ->get('api_scope_id')
            ->setControlType('string')
            ->setReadonly(true);

        $page
            ->get('api_security_id')
            ->setControlType('string')
            ->setReadonly(true);
           
        $this->addEdit();
        parent::edit($id);
    }

    public function delete($id)
    {
        $page = $this->Page;
        parent::delete($id);

        // $this->setDelete();
    }

    public function onRenderIndex(Event $event, Entity $entity, PageElement $element)
    {
        return $this->renderCheckCross($entity->index);
    }

    public function onRenderView(Event $event, Entity $entity, PageElement $element)
    {
        return $this->renderCheckCross($entity->view);
    }

    public function onRenderAdd(Event $event, Entity $entity, PageElement $element)
    {
        return $this->renderCheckCross($entity->add);
    }

    public function onRenderEdit(Event $event, Entity $entity, PageElement $element)
    {
        return $this->renderCheckCross($entity->edit);
    }

    public function onRenderDelete(Event $event, Entity $entity, PageElement $element)
    {
        return $this->renderCheckCross($entity->delete);
    }

    public function onRenderExecute(Event $event, Entity $entity, PageElement $element)
    {
        return $this->renderCheckCross($entity->execute);
    }

    private function renderCheckCross($value)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view'])) {
            if ($value == 1) {
                return "<i class='fa fa-check'></i>";
            }

            return "<i class='fa fa-close'></i>";
        }
    }

    private function disableSort()
    {
        $page = $this->Page;

        $actions = ['add', 'view', 'edit', 'delete', 'index', 'execute'];
        foreach ($actions as $action) {
            $page
                ->get($action)
                ->setSortable(false);
        }
    }

    private function setDelete()
    {
        $page = $this->Page;

        $actions = ['add', 'view', 'edit', 'delete', 'index', 'execute'];
        foreach ($actions as $action) {
            $page
                ->get($action)
                ->setControlType('select')
                ->setOptions($this->getSelectOptions(), false)
                ->setDisabled(true);
        }
    }

    private function addEdit()
    {
        $page = $this->Page;
        $page->move('api_scope_id')->first();

        $actions = ['add', 'view', 'edit', 'delete', 'index', 'execute'];

        foreach ($actions as $action) {
            $page
                ->get($action)
                ->setControlType('select')
                ->setOptions($this->getSelectOptions(), false);
        }
    }

    private function getSelectOptions()
    {
        return [
            self::DENY => __('Deny'),
            self::ALLOW => __('Allow')
        ];
    }
}
