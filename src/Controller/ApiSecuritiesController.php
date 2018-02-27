<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class ApiSecuritiesController extends PageController
{
    const DENY = 0;
    const ALLOW = 1;
    const ACTION_LIST = ['index', 'view', 'add', 'edit', 'delete', 'execute'];

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('ApiSecuritiesScopes');
        $this->loadModel('ApiScopes');
        $this->loadModel('ApiSecurities');

        $this->Page->disable(['add', 'delete']);
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderIndex'] = 'onRenderIcon';
        $event['Controller.Page.onRenderView'] = 'onRenderIcon';
        $event['Controller.Page.onRenderAdd'] = 'onRenderIcon';
        $event['Controller.Page.onRenderEdit'] = 'onRenderIcon';
        $event['Controller.Page.onRenderDelete'] = 'onRenderIcon';
        $event['Controller.Page.onRenderExecute'] = 'onRenderIcon';

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
        
        // set header
        $page->setHeader(__('API Securities'));

        $page->get('index')->setLabel(__('List'));
        $page->exclude('model');
    }

    public function index()
    {
        $page = $this->Page;

        // disable sorting for all actions
        foreach (self::ACTION_LIST as $action) {
            $page->get($action)->setSortable(false);
        }

        $scopeOptions = $this->ApiScopes
            ->find('optionList', ['defaultOption' => false])
            ->toArray();

        $queryString = $page->getQueryString();
        if (!array_key_exists('api_scope_id', $queryString)) {
            $firstScopeOption = $scopeOptions[0]['value'];
            $page->setQueryString('api_scope_id', $firstScopeOption);
        }

        $page->addFilter('api_scope_id')->setOptions($scopeOptions);

        parent::index();
    }

    public function view($id)
    {
        $page = $this->Page;
        $apiScopeId = $this->getApiScopeId();
        
        $page->addNew('api_scope')->setLabel('API Scope');
        $page->move('api_scope')->after('name');

        $scopeEntity = $this->ApiScopes->get($apiScopeId);
        $scopeName = $scopeEntity->name;

        $page->get('api_scope')->setValue($scopeName);

        parent::view($id);
    }

    public function edit($id)
    {
        $page = $this->Page;
        parent::edit($id);

        $entity = $page->getData();
        $apiSecurityId = $entity->id;
        $apiScopeId = $this->getApiScopeId();

        $apiScopeName = $this->ApiScopes->get($apiScopeId)->name;
        $page->addNew('api_scope_id')
            ->setLabel(__('API Scope'))
            ->setDisabled(true)
            ->setRequired(true)
            ->setValue($apiScopeName);

        $page->move('api_scope_id')->after('name');
        $page->get('name')
            ->setDisabled(true)
            ->setRequired(true);

        $tempScopeName = 'scopes';
        if ($this->request->is(['get'])) {
            // default value retrieving is from the default action
            $scopeData = $entity;

            if (!empty($entity->api_scopes)) {
                foreach ($entity->api_scopes as $key => $value) {
                    if ($value->id == $apiScopeId) {
                        // if record for the security id and the scope id is found,
                        // data will be used from the record
                        $scopeData = $value->_joinData;
                        break;
                    }
                }
            }

            $entity->{$tempScopeName} = [
                'index' => $scopeData->index,
                'view' => $scopeData->view,
                'add' => $scopeData->add,
                'edit' => $scopeData->edit,
                'delete' => $scopeData->delete,
                'execute' => $scopeData->execute
            ];
        }

        // scope id for api_securities_scopes id
        $page->addNew("$tempScopeName.api_scope_id")
            ->setControlType('hidden')
            ->setValue($apiScopeId);

        foreach (self::ACTION_LIST as $action) {
            // set original actions as hidden
            $page->get($action)->setControlType('hidden');

            // create new action list to save to api_securities_scopes
            $isEnabled = $entity->{$action};
            $scopeName = "$tempScopeName.$action";

            // set disabled and dropdown field based on default actions
            if ($isEnabled) {
                $page->addNew($scopeName)
                    ->setLabel(Inflector::humanize($action))
                    ->setControlType('select')
                    ->setRequired(true)
                    ->setOptions($this->getSelectOptions(), false);
            } else {
                $page->addNew($scopeName . '_view')
                    ->setLabel(Inflector::humanize($action))
                    ->setControlType('string')
                    ->setDisabled(true)
                    ->setRequired(true)
                    ->setValue(__('Deny'));

                $page->addNew($scopeName)
                    ->setControlType('hidden')
                    ->setValue($isEnabled);
            }
        }
    }

    public function onRenderIcon(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        $key = $element->getKey();
        $keyValue = $entity->{$key};
        $apiScopeId = $this->getApiScopeId();

        if ($page->is(['index', 'view'])) {
            if ($keyValue == 0) {
                return "<i class='fa fa-close'></i>";
            } else {
                if (empty($entity->api_scopes)) {
                    return "<i class='fa fa-check'></i>";
                } else {
                    foreach ($entity->api_scopes as $obj) {
                        if ($obj->id == $apiScopeId) {
                            $actionValue = $obj->_joinData->{$key};

                            if ($actionValue == 0) {
                                return "<i class='fa fa-close'></i>";
                            }
                        }
                    }

                    return "<i class='fa fa-check'></i>";
                }
            }
        }
    }

    private function getApiScopeId()
    {
        $page = $this->Page;
        $queryString = $page->getQueryString();

        if (!array_key_exists('api_scope_id', $queryString)) {
            pr('Query String Error');
            die;
        }

        return $queryString['api_scope_id'];
    }

    private function getSelectOptions()
    {
        return [
            self::DENY => __('Deny'),
            self::ALLOW => __('Allow')
        ];
    }
}
