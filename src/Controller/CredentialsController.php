<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
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

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderApiScopes'] = 'onRenderApiScopes';

        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        parent::beforeFilter($event);
        $page->addCrumb('Credentials', ['plugin' => false, 'controller' => 'Credentials', 'action' => 'index']);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['public_key']);
        $page->get('client_id')->setLabel(__('Client ID'));

        $page->addNew('api_scopes')
            ->setLabel('API Scopes')
            ->setControlType('select')
            ->setAttributes('multiple', true);

        parent::index();
    }

    public function view($id)
    {
        $page = $this->Page;
        parent::view($id);

        $page->addNew('api_scopes')
            ->setLabel('API Scopes')
            ->setControlType('select')
            ->setAttributes('multiple', true);
        $page->move('api_scopes')->after('public_key');
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
        $page->addNew('client')
            ->setControlType('string')
            ->setLabel('Client ID')
            ->setValue($clientId)
            ->setDisabled(true);

        $page->move('client')->first();
        $page->get('client_id')
            ->setControlType('hidden')
            ->setValue($clientId);

        $this->addEdit();
    }

    public function edit($id)
    {
        $page = $this->Page;
        parent::edit($id);

        $page->get('name')->setDisabled(true);
        $page->get('client_id')->setDisabled(true);
        $this->addEdit($id);
    }

    public function onRenderApiScopes(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view'])) {
            $scopeList = [];

            if (!is_null($entity->api_scopes)) {
                foreach ($entity->api_scopes as $obj) {
                    $scopeList[] = $obj->name;
                }
            }

            $value = implode(', ', $scopeList);
            return $value;
        }
    }

    private function addEdit($id = 0)
    {
        $page = $this->Page;
        $entity = $page->getData();
        
        $scopeIds = [];
        if (!is_null($entity->api_scopes)) {
            foreach ($entity->api_scopes as $obj) {
                $scopeIds[] = $obj->id;
            }
        }

        $scopeOptions = $this->ApiScopes
            ->find('optionList', ['defaultOption' => false])
            ->toArray();

        $page->addNew('api_scopes')
            ->setControlType('select')
            ->setLabel(__('API Scopes'))
            ->setOptions($scopeOptions, false)
            ->setAttributes('multiple', true)
            ->setValue($scopeIds);
        // ->setValue($entity->api_scopes);
    }
}
