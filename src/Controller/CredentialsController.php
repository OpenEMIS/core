<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\I18n\Time;
use App\Controller\PageController;

class CredentialsController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('ApiCredentials');
        $this->loadModel('ApiScopes');
        $this->loadModel('ApiCredentialsScopes');
        $this->Page->loadElementsFromTable($this->ApiCredentials);
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        parent::beforeFilter($event);
        // $page->exclude(['scope']);
        $page->addCrumb('Credentials', ['plugin' => false, 'controller' => 'Credentials', 'action' => 'index']);
    }

    public function add()
    {
        $page = $this->Page;
        parent::add();

        $cStrong = false;
        $randomString = '';
        $timeStamp = new Time();
        $timeStamp = $timeStamp->toUnixString();
        while (!$cStrong) {
            $randomString = bin2hex(openssl_random_pseudo_bytes(8, $cStrong));
        }
        $clientId = $timeStamp.'-'.$randomString.'.app';
        if ($this->request->data('ApiCredentials.client_id')) {
            $clientId = $this->request->data('ApiCredentials.client_id');
        }
        $page->addNew('client')->setControlType('string')->setLabel('Client ID')->setValue($clientId)->setDisabled(true);
        $page->move('client')->first();
        $page->get('client_id')->setControlType('hidden')->setValue($clientId);

        $scopeOptions = $this->ApiScopes
            ->find('optionList', ['defaultOption' => false])
            ->toArray();

        $page->addNew('api_scopes')
            ->setControlType('select')
            ->setLabel('API Scope')
            ->setOptions($scopeOptions)
            ->setRequired(true);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['public_key']);
        $page->get('client_id')->setLabel(__('Client ID'));
        parent::index();
    }

    public function view($id)
    {
        parent::view($id);
    }

    public function edit($id)
    {
        $page = $this->Page;
        $page->get('name')->setDisabled(true);
        $page->get('client_id')->setDisabled(true);

        $scopeOptions = $this->ApiScopes
            ->find('optionList', ['defaultOption' => false])
            ->toArray();

        $page->addNew('api_scopes')
            ->setControlType('select')
            ->setLabel('API Scope')
            ->setOptions($scopeOptions)
            ->setRequired(true);

        parent::edit($id);
    }
}
