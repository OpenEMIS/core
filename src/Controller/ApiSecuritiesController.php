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

        $this->loadModel('ApiSecuritiesCredentials');
        $this->loadModel('ApiCredentials');
        $this->loadModel('ApiSecurities');
        $this->Page->loadElementsFromTable($this->ApiSecuritiesCredentials);
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

        $page->get('api_credential_id')->setLabel(__('API Credentials'));
        $page->get('api_security_id')->setLabel(__('API Securities'));

        // set header
        $page->setHeader(__('API Securities'));
    }

    public function index()
    {
        parent::index();

        $page = $this->Page;

        $allCredentials = $this->ApiCredentials
            ->find('optionList', ['defaultOption' => false])
            ->toArray();

        $credentialOptions = [null => 'All Credentials'] + $allCredentials;
        $page
            ->addFilter('api_credential_id')
            ->setOptions($credentialOptions);
    }

    public function add()
    {
        parent::add();
        $page = $this->Page;

        $page
            ->get('api_credential_id')
            ->setId('api_credential_id')
            ->setControlType('select');

        $page
            ->get('api_security_id')
            ->setControlType('select')
            ->setDependentOn('api_credential_id')
            ->setParams('ApiSecurities');

        $this->addEdit();

        // if ($this->request->is('post')) {
        //     $data = $page->getData();

        //     // pr($data);
        //     // die;
        //     // if (!empty($data->errors('add'))) {
        //     //     $this->Alert->success('general.edit.success');
        //     // }
        // }
    }

    public function edit($id)
    {
        $page = $this->Page;
        //$entity = $page->getData();

        $page
            ->get('api_credential_id')
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
