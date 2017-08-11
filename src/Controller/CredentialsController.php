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
        $this->loadModel('OAuth.Credentials');
        $this->Page->loadElementsFromTable($this->Credentials);
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        parent::beforeFilter($event);
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
        while(!$cStrong) {
            $randomString = bin2hex(openssl_random_pseudo_bytes(8, $cStrong));
        }
        $clientId = $timeStamp.'-'.$randomString.'.app';
        if ($this->request->data('Credentials.client_id')) {
            $clientId = $this->request->data('Credentials.client_id');
        }
        $page->addNew('client')->setControlType('string')->setLabel('Client ID')->setValue($clientId)->setDisabled(true);
        $page->move('client')->first();
        $page->get('client_id')->setControlType('hidden')->setValue($clientId);

    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['public_key']);
        parent::index();
    }

    public function edit($id)
    {
        $page = $this->Page;
        $page->get('name')->setDisabled(true);
        $page->get('client_id')->setDisabled(true);
        parent::edit($id);
    }
}
