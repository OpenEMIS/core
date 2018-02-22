<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\I18n\Time;
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

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $page = $this->Page;
        $page->addCrumb('API Securities', [
            'plugin' => false,
            'controller' => 'ApiSecurities',
            'action' => 'index'
        ]);

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
        //$entity = $page->getData();

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

    private function addEdit()
    {
        $page = $this->Page;
        $page->move('api_scope_id')->first();

        $actions = ['add', 'view', 'edit', 'delete', 'list', 'execute'];

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
