<?php

namespace Angular\Controller;

use Angular\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class AngularController extends AppController
{
    public $helpers = ['ControllerAction.HtmlField'];
    public function initialize()
    {
        parent::initialize();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $this->Auth->allow(['app', 'inputs']);
    }

    public function app()
    {
        $this->viewBuilder()->layout(false);
    }

    public function inputs()
    {
        $this->viewBuilder()->layout(false);
        $requestAttr = json_decode($this->request->query['attributes'], true);
        if (is_array($requestAttr)) {
            $table = TableRegistry::get($requestAttr['className']);
            $fields = array_fill_keys(array_keys($table->fields), '');
            $data = $table->newEntity($fields, ['validate' => false]);

            if (isset($requestAttr['fieldName'])) {
                $requestAttr['attr']['name'] = $requestAttr['fieldName'];
            }
            if (isset($requestAttr['label'])) {
                $requestAttr['attr']['label'] = $requestAttr['label'];
            }
            if (isset($requestAttr['required']) && $requestAttr['required']) {
                $requestAttr['attr']['required'] = $requestAttr['required'];
            }
            $requestAttr['attr']['type'] = $requestAttr['type'];
            $requestAttr['label'] = false;

            $this->set('data', $data);
            $this->set('_fieldAttr', $requestAttr);
            $this->set('_type', $requestAttr['type']);
            $this->set('options', $requestAttr['attr']);
            $this->set('request', $this->request);

            $context = [
                    'schema' => $table->schema(),
                    'errors' => '{{errors.'.$requestAttr['model'].'[key].'.$requestAttr['field'].'}}'
                ];
            $this->set('context', $context);

            if ($requestAttr['type']=='date') {
                $this->set('datepicker', $requestAttr);
            }
        } else {
            $this->set('_type', null);
        }
    }
}
